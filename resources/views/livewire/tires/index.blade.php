<?php

use App\Actions\SelectVehicle;
use App\Enums\TireStatus;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Services\TireService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app')]
class extends Component {

    #[Locked]
    public ?int $vehicle_id;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (isset($this->vehicle_id)) {
            $vehicle = Vehicle::findOrFail($this->vehicle_id);
            $this->authorize('view', $vehicle);
            $selectVehicle($vehicle);
        } else {
            $vehicle = session('vehicle');
        }
        $this->vehicle_id = $vehicle->id;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::findOrFail($this->vehicle_id);
    }

    #[Computed]
    public function tires(): \Illuminate\Support\Collection
    {
        $tireService = app(TireService::class);

        return $this->vehicle()->tires()
            ->orderBy('status')
            ->orderByDesc('purchased_on')
            ->orderBy('label')
            ->get()
            ->map(function (Tire $tire) use ($tireService) {
                $pos = $tireService->currentPosition($tire);
                $latestTread = $tire->placements()
                    ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                    ->where('rotations.is_setup', false)
                    ->orderByDesc('rotations.odometer')
                    ->value('placements.tread_center');

                return [
                    'tire' => $tire,
                    'current_position' => $pos?->label() ?? '—',
                    'latest_tread' => $latestTread !== null ? (float) $latestTread : null,
                ];
            });
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tires.index');
    }
};

?>

<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-ink-800 leading-tight">{{ __('Tires') }}</h2>
            <x-treadmark.button href="{{ route('rotations.swap', hashid_encode($vehicle_id)) }}" wire:navigate size="sm">
                <x-treadmark.icon name="wrench" class="w-4 h-4"/>
                Swap Tire
            </x-treadmark.button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    @if ($this->tires->isEmpty())
                        <p class="text-sm text-ink-400">No tires yet.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                            <tr class="text-left border-b border-ink-200">
                                <th class="pb-3 font-semibold text-ink-500">Label</th>
                                <th class="pb-3 font-semibold text-ink-500">Brand / Model</th>
                                <th class="pb-3 font-semibold text-ink-500">Position</th>
                                <th class="pb-3 font-semibold text-ink-500 text-right">Latest Tread</th>
                                <th class="pb-3 font-semibold text-ink-500">Status</th>
                                <th class="pb-3 font-semibold text-ink-500"></th>
                            </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                            @foreach ($this->tires as $row)
                                @php $tire = $row['tire']; @endphp
                                <tr class="hover:bg-ink-50 {{ $tire->status === TireStatus::Retired ? 'opacity-60' : '' }}">
                                    <td class="py-3 font-mono font-bold text-ink-900">
                                        <a href="{{ route('tires.show', $tire) }}" class="hover:underline">{{ $tire->label }}</a>
                                    </td>
                                    <td class="py-3 text-ink-700">
                                        {{ implode(' ', array_filter([$tire->brand, $tire->model])) ?: '—' }}
                                    </td>
                                    <td class="py-3 text-ink-500">{{ $row['current_position'] }}</td>
                                    <td class="py-3 text-right font-mono text-ink-700">
                                        {{ $row['latest_tread'] !== null ? $row['latest_tread'].'/32"' : '—' }}
                                    </td>
                                    <td class="py-3">
                                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $tire->status === TireStatus::Active ? 'bg-fern-100 text-fern-600' : 'bg-ink-100 text-ink-500' }}">
                                                {{ $tire->status->label() }}
                                            </span>
                                    </td>
                                    <td class="py-3 text-right">
                                        <x-treadmark.button variant="ghost" size="sm" href="{{ route('tires.show', $tire) }}">
                                            View
                                        </x-treadmark.button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
