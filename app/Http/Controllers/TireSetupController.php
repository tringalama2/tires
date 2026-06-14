<?php

namespace App\Http\Controllers;

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Http\Requests\TireRequest;
use App\Models\Tire;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use ValueError;

class TireSetupController extends Controller
{
    public function index(SelectVehicle $selectVehicle, Vehicle $vehicle): RedirectResponse|View
    {
        Gate::authorize('view', $vehicle);

        $selectVehicle($vehicle);

        $setupRotation = $vehicle->rotations()->where('is_setup', true)->first();
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

    public function create(Vehicle $vehicle, string $tirePosition): RedirectResponse|View
    {
        Gate::authorize('create', Tire::class);

        try {
            $position = TirePosition::from($tirePosition);
        } catch (ValueError) {
            abort(404, 'Invalid tire position.');
        }

        // Guard: reject if this position already has a tire in the setup rotation.
        $setupRotation = $vehicle->rotations()->where('is_setup', true)->first();
        if ($setupRotation && $setupRotation->placements()->where('to_position', $position->value)->exists()) {
            return redirect()
                ->route('vehicles.setuptires.index', $vehicle)
                ->with('status', "A tire is already placed at {$position->label()}.");
        }

        $existingTire = $vehicle->tires()->first();

        return view('tires.create', compact('vehicle', 'position', 'existingTire'));
    }

    public function store(TireRequest $request, Vehicle $vehicle, string $tirePosition): RedirectResponse
    {
        Gate::authorize('create', Tire::class);

        try {
            $position = TirePosition::from($tirePosition);
        } catch (ValueError) {
            abort(404, 'Invalid tire position.');
        }

        $tire = $vehicle->tires()->create(
            $request->safe()->except(['starting_tread']) + ['status' => TireStatus::Active]
        );

        // Find or create the vehicle's is_setup rotation at the vehicle's starting odometer.
        $setupRotation = $vehicle->rotations()->firstOrCreate(
            ['is_setup' => true],
            [
                'rotated_on' => $vehicle->created_at->toDateString(),
                'odometer' => $vehicle->starting_odometer,
            ]
        );

        $setupRotation->placements()->create([
            'tire_id' => $tire->id,
            'from_position' => null,
            'to_position' => $position->value,
            'tread_center' => $request->validated()['starting_tread'],
        ]);

        return to_route('vehicles.setuptires.index', $vehicle);
    }
}
