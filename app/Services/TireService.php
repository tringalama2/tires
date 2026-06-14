<?php

namespace App\Services;

use App\Enums\TirePosition;
use App\Models\Tire;

class TireService
{
    /**
     * Rule A — Current position: to_position of the tire's latest non-setup placement.
     */
    public function currentPosition(Tire $tire): ?TirePosition
    {
        return $tire->placements()
            ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderByDesc('rotations.odometer')
            ->value('placements.to_position');
    }
}
