<?php

use App\Http\Controllers\ExportDataController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\TireSetupController;
use App\Http\Controllers\VehicleController;
use App\Livewire\RotationDashboard;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');

Route::middleware(['auth'])->group(function () {
    Route::view('profile', 'profile')->name('profile');
    Route::get('profile/export', ExportDataController::class)->name('profile.export');

    Route::middleware(['verified'])->group(function () {

        Route::resource('vehicles', VehicleController::class)->only([
            'create', 'store',
        ]);

        Route::middleware(['firstVehicleExists'])->group(function () {

            Route::resource('vehicles', VehicleController::class)->only([
                'edit', 'update',
            ]);

            Route::livewire('vehicles', 'vehicles.index')->name('vehicles.index');

            Route::livewire('vehicles/{vehicle}/setuptires/create/{tirePosition}', 'vehicles.setuptire-create')->name('vehicles.setuptires.create');
            Route::resource('vehicles.setuptires', TireSetupController::class)->scoped(['tires' => 'id'])->only('index');

            Route::middleware(['activeVehicleTires'])->group(function () {
                Route::get('dashboard/{vehicle_id?}', RotationDashboard::class)->name('dashboard');

                Route::livewire('rotations/swap/{vehicle_id?}', 'rotations.swap')->name('rotations.swap');
                Route::livewire('rotations/prepare/{vehicle_id?}', 'rotations.prepare')->name('rotations.prepare');
                Route::livewire('rotations/update/{vehicle_id?}', 'rotations.update')->name('rotations.update');
                Route::livewire('rotations/prepare/edit/{edit_rotation_id}/{vehicle_id?}', 'rotations.prepare')->name('rotations.edit');

                Route::livewire('reports/by-position', 'reports.by-position')->name('reports.by-position');
                Route::livewire('reports/by-tire', 'reports.by-tire')->name('reports.by-tire');
                Route::livewire('tires', 'tires.index')->name('tires.index');
                Route::livewire('tires/{tire}', 'tires.show')->name('tires.show');
            });
        });
    });
});

require __DIR__.'/auth.php';
