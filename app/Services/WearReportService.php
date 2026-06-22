<?php

namespace App\Services;

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

class WearReportService
{
    /**
     * Rule C — Wear by position.
     *
     * For each interval (consecutive placements of the same tire, ordered by odometer),
     * wear = prev.tread_center − this.tread_center, attributed to this.from_position,
     * normalized to per-1,000 miles using odometer delta.
     *
     * Returns a Collection keyed by TirePosition value with:
     *   position, intervals, avg_wear_per_1000mi, avg_tread_at_removal
     */
    public function wearByPosition(?Vehicle $vehicle): Collection
    {
        $intervals = $this->buildIntervals($vehicle);

        return collect(TirePosition::order())
            ->map(function (TirePosition $position) use ($intervals): array {
                $rows = $intervals->filter(
                    fn ($i) => $i['from_position'] === $position
                );

                $count = $rows->count();

                return [
                    'position' => $position,
                    'intervals' => $count,
                    'avg_wear_per_1000mi' => $count > 0
                        ? round($rows->avg('wear_per_1000mi'), 4)
                        : null,
                    'avg_tread_at_removal' => $count > 0
                        ? round($rows->avg('tread_at_removal'), 2)
                        : null,
                ];
            });
    }

    /**
     * The position wearing fastest, if it exceeds $factor times the average of the others.
     * Returns null when there isn't enough data or no position stands out.
     */
    public function unevenWearOutlier(?Vehicle $vehicle, float $factor): ?array
    {
        $rows = $this->wearByPosition($vehicle)->whereNotNull('avg_wear_per_1000mi');

        if ($rows->count() < 2) {
            return null;
        }

        $fastest = $rows->sortByDesc('avg_wear_per_1000mi')->first();
        $othersAvg = $rows
            ->filter(fn ($r) => $r['position'] !== $fastest['position'])
            ->avg('avg_wear_per_1000mi');

        if ($othersAvg > 0 && $fastest['avg_wear_per_1000mi'] >= $factor * $othersAvg) {
            return $fastest;
        }

        return null;
    }

    /**
     * Rule D — Wear by tire.
     *
     * Returns a Collection of arrays, one per tire, with:
     *   tire, current_position, latest_tread_center, latest_tread_inner,
     *   latest_tread_outer, lifetime_avg_wear_per_1000mi, notes
     *
     * @param  TireStatus|null  $filterStatus  null = all tires, Active = active only, Retired = retired only
     */
    public function wearByTire(?Vehicle $vehicle, ?TireStatus $filterStatus = null): Collection
    {
        $query = Tire::query();
        if ($vehicle) {
            $query->where('vehicle_id', $vehicle->id);
        }
        if ($filterStatus !== null) {
            $query->where('status', $filterStatus);
        }

        $tires = $query->with(['wearPlacements' => function ($q) {
            $q->addSelect('rotations.rotated_on');
        }])->get();

        $intervals = $this->buildIntervals($vehicle);

        return $tires->map(function (Tire $tire) use ($intervals): array {
            $latestPlacement = $tire->wearPlacements->last();
            $tireIntervals = $intervals->filter(fn ($i) => $i['tire_id'] === $tire->id);
            $count = $tireIntervals->count();

            $notes = $tire->wearPlacements
                ->filter(fn ($p) => $p->note !== null)
                ->map(fn ($p) => $p->rotated_on.': '.$p->note)
                ->values()
                ->all();

            $avgWear = $count > 0 ? round($tireIntervals->avg('wear_per_1000mi'), 4) : null;
            $projectedMiles = $count >= 2 ? $this->projectFromTread($latestPlacement?->tread_center, $avgWear) : null;

            return [
                'tire' => $tire,
                'current_position' => $tire->currentPosition(),
                'latest_tread_center' => $latestPlacement ? (float) $latestPlacement->tread_center : null,
                'latest_tread_inner' => $latestPlacement ? $latestPlacement->tread_inner : null,
                'latest_tread_outer' => $latestPlacement ? $latestPlacement->tread_outer : null,
                'latest_is_cupped' => $latestPlacement ? (bool) $latestPlacement->is_cupped : false,
                'lifetime_avg_wear_per_1000mi' => $avgWear,
                'projected_miles' => $projectedMiles,
                'notes' => $notes,
            ];
        });
    }

    /**
     * Projects miles remaining until tread hits $limitTread (default 2/32").
     * Returns null if fewer than 2 wear intervals (insufficient data).
     */
    public function projectedReplacementMileage(Tire $tire, float $limitTread = 2.0): ?float
    {
        $intervals = $this->buildIntervals(null, $tire);

        if ($intervals->count() < 2) {
            return null;
        }

        $latestTread = $tire->wearPlacements()->orderByDesc('rotations.odometer')->value('placements.tread_center');

        return $this->projectFromTread($latestTread, $intervals->avg('wear_per_1000mi'), $limitTread);
    }

    /**
     * Project remaining miles until $latestTread reaches $limitTread, given an average wear rate.
     */
    private function projectFromTread(?float $latestTread, ?float $avgWear, float $limitTread = 2.0): ?float
    {
        if ($latestTread === null || $avgWear === null || $avgWear <= 0) {
            return null;
        }

        $remaining = ((float) $latestTread - $limitTread) / $avgWear * 1000;

        return max(0, round($remaining));
    }

    /**
     * Build wear intervals for all tires (optionally scoped to a vehicle or single tire).
     *
     * Each interval: tire_id, from_position (TirePosition), wear_per_1000mi, tread_at_removal.
     */
    private function buildIntervals(?Vehicle $vehicle, ?Tire $tire = null): Collection
    {
        $query = Tire::query()->with('wearPlacements');

        if ($vehicle) {
            $query->where('vehicle_id', $vehicle->id);
        }
        if ($tire) {
            $query->where('id', $tire->id);
        }

        $intervals = collect();

        foreach ($query->get() as $t) {
            $intervals = $intervals->concat(
                $t->wearPlacements->values()->sliding(2)
                    ->map(function ($pair) use ($t) {
                        [$prev, $curr] = $pair->values();
                        $odometerDelta = $curr->rotation_odometer - $prev->rotation_odometer;

                        if ($odometerDelta <= 0 || $curr->from_position === null) {
                            return null;
                        }

                        $wear = (float) $prev->tread_center - (float) $curr->tread_center;

                        return [
                            'tire_id' => $t->id,
                            'from_position' => $curr->from_position,
                            'wear_per_1000mi' => $wear / $odometerDelta * 1000,
                            'tread_at_removal' => (float) $curr->tread_center,
                        ];
                    })
                    ->filter()
            );
        }

        return $intervals;
    }
}
