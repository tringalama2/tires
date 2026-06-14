<?php

namespace App\Services;

use App\Enums\TirePosition;
use App\Models\Placement;
use App\Models\Rotation;
use App\Models\Vehicle;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RotationService
{
    public function __construct(private readonly TireService $tireService) {}

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
            $pos = $this->tireService->currentPosition($tire);
            if ($pos === null) {
                continue;
            }

            $lastTread = $tire->placements()
                ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                ->where('rotations.is_setup', false)
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
     *   rotation_id  string|null  (UUID — set for edits, null for new)
     *   placements   array of:
     *     tire_id       string (UUID)
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
                $rotation = Rotation::findOrFail($rotationId);
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
                Placement::create([
                    'rotation_id' => $rotation->id,
                    'tire_id' => $p['tire_id'],
                    'from_position' => $p['from_position'],
                    'to_position' => $p['to_position'],
                    'tread_center' => $p['tread_center'],
                    'tread_inner' => $p['tread_inner'] ?? null,
                    'tread_outer' => $p['tread_outer'] ?? null,
                    'note' => $p['note'] ?? null,
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
        $from = $fromPositions;
        $to = $toPositions;
        sort($from);
        sort($to);

        return $from === $to;
    }
}
