<?php

use App\Http\Controllers\TireController;
use App\Http\Controllers\VehicleController;
use App\Livewire\RotationDashboard;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::view('profile', 'profile')->name('profile');

    Route::middleware(['verified'])->group(function () {
        //Volt::route('dashboard', 'dashboard')->name('dashboard');
        Route::get('dashboard', RotationDashboard::class)->name('dashboard')
            ->middleware('activeVehicleTires');

        Route::resource('vehicles', VehicleController::class)->only([
            'create', 'store', 'edit', 'update',
        ]);

        Route::resource('vehicles.tires', TireController::class)->only([
            'index', 'create', 'store', 'edit', 'update',
        ]);

        Volt::route('tires', 'tires.index')->name('tires.index');

        Volt::route('vehicles', 'vehicles.index')->name('vehicles.index');
    });
});

require __DIR__.'/auth.php';
