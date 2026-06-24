<?php

namespace App\Services;

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RotationService
{
    /**
     * Rule E — Auto-seed next rotation stubs.
     *
     * Returns an array ordered by TirePosition::order(), each entry:
     *   ['tire' => Tire, 'from_position' => TirePosition, 'last_tread_center' => float|null]
     *
     * Only active tires with a known current position are included.
     */
    public function startNext(Vehicle $vehicle): array
    {
        $tires = $vehicle->activeTires()->get();

        $byPosition = [];
        foreach ($tires as $tire) {
            $pos = $tire->currentPosition();
            if ($pos === null) {
                continue;
            }

            $lastTread = $tire->placements()
                ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                ->orderByDesc('rotations.odometer')
                ->value('placements.tread_center');

            $byPosition[$pos->value] = [
                'tire' => $tire,
                'from_position' => $pos,
                'last_tread_center' => $lastTread !== null ? (float) $lastTread : null,
            ];
        }

        $ordered = [];
        foreach (TirePosition::order() as $pos) {
            if (isset($byPosition[$pos->value])) {
                $ordered[] = $byPosition[$pos->value];
            }
        }

        return $ordered;
    }

    /**
     * Validate and persist a rotation + placements atomically.
     *
     * $data shape:
     *   rotated_on   string (date)
     *   odometer     int
     *   note         string|null
     *   rotation_id  int|null  (set for edits, null for new)
     *   placements   array of:
     *     tire_id       int
     *     from_position string (TirePosition value)
     *     to_position   string (TirePosition value)
     *     tread_center  float
     *     tread_inner   float|null
     *     tread_outer   float|null
     *     note          string|null
     *
     * @throws ValidationException
     */
    public function save(array $data, Vehicle $vehicle): Rotation
    {
        $placements = $data['placements'];
        $rotationId = $data['rotation_id'] ?? null;

        $fromPositions = array_column($placements, 'from_position');
        $toPositions = array_column($placements, 'to_position');

        if (! $this->validatePermutation($fromPositions, $toPositions)) {
            throw ValidationException::withMessages([
                'placements' => 'The new positions must be a permutation of the current positions.',
            ]);
        }

        // Odometer must exceed all previous rotations (for new rotations only)
        if ($rotationId === null) {
            $maxOdometer = $vehicle->rotations()->max('odometer');
            if ($maxOdometer !== null && $data['odometer'] <= $maxOdometer) {
                throw ValidationException::withMessages([
                    'odometer' => 'Odometer must be greater than the previous rotation ('
                        .number_format($maxOdometer).' mi).',
                ]);
            }
        }

        return DB::transaction(function () use ($data, $vehicle, $placements, $rotationId) {
            if ($rotationId) {
                $rotation = $vehicle->rotations()->findOrFail($rotationId);
                $rotation->update([
                    'rotated_on' => $data['rotated_on'],
                    'odometer' => $data['odometer'],
                    'note' => $data['note'] ?? null,
                ]);
                $rotation->placements()->delete();
            } else {
                $rotation = Rotation::create([
                    'vehicle_id' => $vehicle->id,
                    'rotated_on' => $data['rotated_on'],
                    'odometer' => $data['odometer'],
                    'note' => $data['note'] ?? null,
                    'is_setup' => false,
                ]);
            }

            foreach ($placements as $p) {
                $tire = $vehicle->tires()->findOrFail($p['tire_id']);

                Placement::create([
                    'rotation_id' => $rotation->id,
                    'tire_id' => $tire->id,
                    'from_position' => $p['from_position'],
                    'to_position' => $p['to_position'],
                    'tread_center' => $p['tread_center'],
                    'tread_inner' => $p['tread_inner'] ?? null,
                    'tread_outer' => $p['tread_outer'] ?? null,
                    'note' => $p['note'] ?? null,
                    'is_feathering' => $p['is_feathering'] ?? false,
                    'is_cupped' => $p['is_cupped'] ?? false,
                ]);

                if (! empty($p['tire_flags'])) {
                    $tire->update($p['tire_flags']);
                }
            }

            return $rotation;
        });
    }

    /**
     * Persist a tire swap atomically.
     *
     * Creates a swap rotation (is_swap = true), retires each outgoing tire,
     * creates each replacement tire, and records placements for both sides.
     *
     * $data shape:
     *   rotated_on  string (date)
     *   odometer    int   (>= last rotation odometer)
     *   swaps       array of:
     *     retiring_tire_id    int
     *     retiring_tread      float|null
     *     replacement_label        string
     *     replacement_brand        string|null
     *     replacement_model        string|null
     *     replacement_tread        float
     *     replacement_tin          string|null
     *     replacement_size         string|null
     *     replacement_purchased_on string|null
     *
     * @throws ValidationException
     */
    public function saveSwap(array $data, Vehicle $vehicle): Rotation
    {
        $maxOdometer = $vehicle->rotations()->max('odometer');
        if ($maxOdometer !== null && $data['odometer'] < $maxOdometer) {
            throw ValidationException::withMessages([
                'odometer' => 'Odometer must be at least the previous rotation ('.number_format($maxOdometer).' mi).',
            ]);
        }

        Validator::make($data['swaps'], [
            '*.replacement_tin' => ['nullable', 'max:12'],
            '*.replacement_purchased_on' => ['nullable', 'date'],
        ])->validate();

        return DB::transaction(function () use ($data, $vehicle) {
            $rotation = Rotation::create([
                'vehicle_id' => $vehicle->id,
                'rotated_on' => $data['rotated_on'],
                'odometer' => $data['odometer'],
                'is_setup' => false,
                'is_swap' => true,
            ]);

            foreach ($data['swaps'] as $swap) {
                $retiring = $vehicle->tires()->findOrFail($swap['retiring_tire_id']);
                $position = $retiring->currentPosition();

                // Retiring tire placement — leaves the vehicle
                Placement::create([
                    'rotation_id' => $rotation->id,
                    'tire_id' => $retiring->id,
                    'from_position' => $position,
                    'to_position' => null,
                    'tread_center' => $swap['retiring_tread'] ?? null,
                ]);

                $retiring->update(['status' => TireStatus::Retired]);

                // Replacement tire — enters the vehicle at the vacated position
                $replacement = Tire::create([
                    'vehicle_id' => $vehicle->id,
                    'label' => $swap['replacement_label'],
                    'brand' => $swap['replacement_brand'] ?? null,
                    'model' => $swap['replacement_model'] ?? null,
                    'tin' => $swap['replacement_tin'] ?? null,
                    'size' => $swap['replacement_size'] ?? null,
                    'purchased_on' => $swap['replacement_purchased_on'] ?? null,
                    'status' => TireStatus::Active,
                ]);

                Placement::create([
                    'rotation_id' => $rotation->id,
                    'tire_id' => $replacement->id,
                    'from_position' => null,
                    'to_position' => $position,
                    'tread_center' => $swap['replacement_tread'],
                ]);
            }

            return $rotation;
        });
    }

    /**
     * True when $toPositions is a permutation of $fromPositions.
     *
     * @param  string[]  $fromPositions
     * @param  string[]  $toPositions
     */
    public function validatePermutation(array $fromPositions, array $toPositions): bool
    {
        sort($fromPositions);
        sort($toPositions);

        return $fromPositions === $toPositions;
    }
}
