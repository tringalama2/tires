<?php

namespace App\Services;

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Support\Collection;

class WearReportService
{
    public function __construct(private readonly TireService $tireService) {}

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

        $tires = $query->with(['placements' => function ($q) {
            $q->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                ->where('rotations.is_setup', false)
                ->orderBy('rotations.odometer')
                ->select('placements.*', 'rotations.odometer as rotation_odometer');
        }])->get();

        $intervals = $this->buildIntervals($vehicle);

        return $tires->map(function (Tire $tire) use ($intervals): array {
            $latestPlacement = $tire->placements->last();
            $tireIntervals = $intervals->filter(fn ($i) => $i['tire_id'] === $tire->id);
            $count = $tireIntervals->count();

            // Load notes separately to get rotation date for display
            $notes = $tire->placements()
                ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                ->where('rotations.is_setup', false)
                ->whereNotNull('placements.note')
                ->orderBy('rotations.odometer')
                ->get(['placements.note', 'rotations.rotated_on'])
                ->map(fn ($p) => $p->rotated_on.': '.$p->note)
                ->all();

            $avgWear = $count > 0 ? round($tireIntervals->avg('wear_per_1000mi'), 4) : null;

            $projectedMiles = null;
            if ($count >= 2 && $avgWear > 0 && $latestPlacement) {
                $remaining = ((float) $latestPlacement->tread_center - 2.0) / $avgWear * 1000;
                $projectedMiles = max(0, round($remaining));
            }

            return [
                'tire' => $tire,
                'current_position' => $this->tireService->currentPosition($tire),
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

        $avgWear = $intervals->avg('wear_per_1000mi');
        if ($avgWear <= 0) {
            return null;
        }

        $latestTread = $tire->placements()
            ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderByDesc('rotations.odometer')
            ->value('placements.tread_center');

        if ($latestTread === null) {
            return null;
        }

        $remaining = ((float) $latestTread - $limitTread) / $avgWear * 1000;

        return max(0, round($remaining));
    }

    public function scalpingFlag(Placement $placement): bool
    {
        return $placement->is_cupped;
    }

    /**
     * Build wear intervals for all tires (optionally scoped to a vehicle or single tire).
     *
     * Each interval: tire_id, from_position (TirePosition), wear_per_1000mi, tread_at_removal.
     */
    private function buildIntervals(?Vehicle $vehicle, ?Tire $tire = null): Collection
    {
        $query = Tire::query()
            ->with(['placements' => function ($q) {
                $q->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                    ->where('rotations.is_setup', false)
                    ->orderBy('rotations.odometer')
                    ->select('placements.*', 'rotations.odometer as rotation_odometer');
            }]);

        if ($vehicle) {
            $query->where('vehicle_id', $vehicle->id);
        }
        if ($tire) {
            $query->where('id', $tire->id);
        }

        $intervals = collect();

        foreach ($query->get() as $t) {
            $placements = $t->placements->values();

            for ($i = 1; $i < $placements->count(); $i++) {
                $prev = $placements[$i - 1];
                $curr = $placements[$i];

                $odometerDelta = $curr->rotation_odometer - $prev->rotation_odometer;

                if ($odometerDelta <= 0 || $curr->from_position === null) {
                    continue;
                }

                $wear = (float) $prev->tread_center - (float) $curr->tread_center;
                $wearPer1000 = $odometerDelta > 0 ? $wear / $odometerDelta * 1000 : 0;

                $intervals->push([
                    'tire_id' => $t->id,
                    'from_position' => $curr->from_position,
                    'wear_per_1000mi' => $wearPer1000,
                    'tread_at_removal' => (float) $curr->tread_center,
                ]);
            }
        }

        return $intervals;
    }
}
