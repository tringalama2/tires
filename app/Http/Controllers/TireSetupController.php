<?php

namespace App\Http\Controllers;

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TireSetupController extends Controller
{
    public function index(SelectVehicle $selectVehicle, Vehicle $vehicle): RedirectResponse|View
    {
        $this->authorize('view', $vehicle);

        $selectVehicle($vehicle);

        $setupRotation = $vehicle->rotations()->setup()->first();
        $placedPositions = $setupRotation
            ? $setupRotation->placements()->pluck('to_position')->all()
            : [];

        $tireAtPosition = function (TirePosition $position) use ($setupRotation): ?Tire {
            if (! $setupRotation) {
                return null;
            }

            $placement = $setupRotation->placements()
                ->where('to_position', $position->value)
                ->with('tire')
                ->first();

            return $placement?->tire;
        };

        return view('tires.index', [
            'vehicle' => $vehicle,
            'frontLeftTire' => $tireAtPosition(TirePosition::FrontLeft),
            'frontRightTire' => $tireAtPosition(TirePosition::FrontRight),
            'rearLeftTire' => $tireAtPosition(TirePosition::RearLeft),
            'rearRightTire' => $tireAtPosition(TirePosition::RearRight),
            'spareTire' => $tireAtPosition(TirePosition::Spare),
        ]);
    }
}
