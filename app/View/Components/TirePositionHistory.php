<?php

namespace App\View\Components;

use App\Enums\TirePosition;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class TirePositionHistory extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public TirePosition $position,
        public Collection $currentRotation,
        public Collection $positionHistory
    ) {
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.tire-position-details');
    }
}
