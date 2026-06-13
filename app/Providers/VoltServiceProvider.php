<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class VoltServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Livewire::addLocation(config('livewire.view_path', resource_path('views/livewire')));
        Livewire::addLocation(resource_path('views/pages'));
    }
}
