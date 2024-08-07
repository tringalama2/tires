<?php

namespace App\Http\Controllers;

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
    public function index(Vehicle $vehicle): RedirectResponse|View
    {
        Gate::authorize('view', $vehicle);

        $frontLeftTire = Tire::installed()->where('vehicle_id', $vehicle->id)->currentRotationByPosition(TirePosition::FrontLeft)->first();
        $frontRightTire = Tire::installed()->where('vehicle_id', $vehicle->id)->currentRotationByPosition(TirePosition::FrontRight)->first();
        $rearLeftTire = Tire::installed()->where('vehicle_id', $vehicle->id)->currentRotationByPosition(TirePosition::RearLeft)->first();
        $rearRightTire = Tire::installed()->where('vehicle_id', $vehicle->id)->currentRotationByPosition(TirePosition::RearRight)->first();
        $spareTire = Tire::installed()->where('vehicle_id', $vehicle->id)->currentRotationByPosition(TirePosition::Spare)->first();

        return view('tires.index', compact('vehicle', 'frontLeftTire', 'frontRightTire', 'rearLeftTire', 'rearRightTire', 'spareTire'));
    }

    public function create(Vehicle $vehicle, int $intTirePosition): RedirectResponse|View
    {
        Gate::authorize('create', Tire::class);

        try {
            $tirePosition = TirePosition::from($intTirePosition);
        } catch (ValueError $e) {
            abort(404, 'Invalid Tire position.');
        }

        $existingTire = Tire::installed()->where('vehicle_id', $vehicle->id)->first();

        return view('tires.create', compact('vehicle', 'tirePosition', 'existingTire'));
    }

    public function store(TireRequest $request, Vehicle $vehicle, int $intTirePosition)
    {
        Gate::authorize('create', Tire::class);

        try {
            $tirePosition = TirePosition::from($intTirePosition);
        } catch (ValueError $e) {
            abort(404, 'Invalid Tire position.');
        }

        $tire = $vehicle->tires()->create($request->safe()->except(['starting_tread']) + ['status' => TireStatus::Installed]);

        $rotation = $tire->rotations()->create([
            'starting_position' => $intTirePosition,
            'rotated_on' => $vehicle->created_at->toDateString(),
            'starting_odometer' => $vehicle->starting_odometer,
            'starting_tread' => $request->safe()['starting_tread'],
        ]);

        return to_route('vehicles.setuptires.index', $vehicle);
    }

    public function show(Vehicle $vehicle, Tire $tire) {}

    public function edit(Vehicle $vehicle, Tire $tire) {}

    public function update(TireRequest $request, Vehicle $vehicle, Tire $tire) {}

    public function destroy(Vehicle $vehicle, Tire $tire) {}
}
