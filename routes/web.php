<?php

use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::view('/', 'welcome');

Route::middleware(['auth'])->group(function () {
    Route::view('profile', 'profile')->name('profile');


    Route::middleware(['verified'])->group(function () {
        Volt::route('dashboard', 'dashboard')->name('dashboard');

        Route::resource('vehicles', VehicleController::class)->only([
            'create', 'store', 'edit', 'update'
        ]);

        Volt::route('vehicles', 'vehicles.index')->name('vehicles.index');

    });
});



require __DIR__.'/auth.php';
