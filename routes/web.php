<?php

use App\Http\Controllers\TireSetupController;
use App\Http\Controllers\VehicleController;
use App\Livewire\RotationDashboard;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::view('profile', 'profile')->name('profile');

    Route::middleware(['verified'])->group(function () {

        Route::resource('vehicles', VehicleController::class)->only([
            'create', 'store',
        ]);

        Route::middleware(['firstVehicleExists'])->group(function () {

            Route::resource('vehicles', VehicleController::class)->only([
                'edit', 'update',
            ]);

            Volt::route('vehicles', 'vehicles.index')->name('vehicles.index');

            Route::get('vehicles/{vehicle}/setuptires/create/{tirePosition}', [TireSetupController::class, 'create'])->name('vehicles.setuptires.create');
            Route::post('vehicles/{vehicle}/setuptires/{tirePosition}', [TireSetupController::class, 'store'])->name('vehicles.setuptires.store');
            Route::resource('vehicles.setuptires', TireSetupController::class)->scoped(['tires' => 'id'])->only('index');

            Route::middleware(['activeVehicleTires'])->group(function () {
                Route::get('dashboard/{vehicle_id?}', RotationDashboard::class)->name('dashboard');

                Volt::route('rotations/prepare/{vehicle_id?}', 'rotations.prepare')->name('rotations.prepare');
                Volt::route('rotations/update/{vehicle_id?}', 'rotations.update')->name('rotations.update');
            });
        });
    });
});

require __DIR__.'/auth.php';
