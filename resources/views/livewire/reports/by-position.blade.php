<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Models\Vehicle;
use App\Services\WearReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?int $vehicle_id;
    protected Vehicle $vehicle;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (isset($this->vehicle_id)) {
            $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }
        $this->vehicle_id = $this->vehicle->id;
    }

    #[Computed]
    public function report(): \Illuminate\Support\Collection
    {
        return app(WearReportService::class)->wearByPosition($this->vehicle);
    }

    #[Computed]
    public function fastestPosition(): ?array
    {
        return $this->report->whereNotNull('avg_wear_per_1000mi')->sortByDesc('avg_wear_per_1000mi')->first();
    }

    #[Computed]
    public function outlierAlert(): ?string
    {
        $rows = $this->report->whereNotNull('avg_wear_per_1000mi');
        if ($rows->count() < 2) {
            return null;
        }
        $fastest = $rows->sortByDesc('avg_wear_per_1000mi')->first();
        $othersAvg = $rows->filter(fn ($r) => $r['position'] !== $fastest['position'])->avg('avg_wear_per_1000mi');
        if ($othersAvg > 0 && $fastest['avg_wear_per_1000mi'] > 2 * $othersAvg) {
            return $fastest['position']->label().' is wearing significantly faster. Check alignment or consider rotating more frequently.';
        }
        return null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.reports.by-position');
    }
};

?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Wear by Position') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if ($this->outlierAlert)
                <div class="mb-4 p-4 bg-orange-50 border border-orange-300 rounded-lg flex gap-2 text-orange-800 text-sm">
                    <x-phosphor-warning-duotone class="w-5 h-5 shrink-0 text-orange-500 mt-0.5" />
                    <span>{{ $this->outlierAlert }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-200">
                                <th class="pb-3 font-semibold text-gray-600">Position</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Intervals</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Avg Wear / 1,000 mi</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Avg Tread at Removal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($this->report->sortByDesc('avg_wear_per_1000mi') as $row)
                                @php
                                    $isFastest = $this->fastestPosition && $row['position'] === $this->fastestPosition['position'];
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 font-medium text-gray-800">{{ $row['position']->label() }}</td>
                                    <td class="py-3 text-right text-gray-600">{{ $row['intervals'] ?? '—' }}</td>
                                    <td class="py-3 text-right font-mono {{ $isFastest ? 'text-orange-600 font-bold' : 'text-gray-700' }}">
                                        @if ($row['avg_wear_per_1000mi'] !== null)
                                            {{ number_format($row['avg_wear_per_1000mi'], 2) }}
                                            @if ($isFastest)
                                                <span class="ml-1 text-xs bg-orange-100 text-orange-700 px-1 rounded">fastest</span>
                                            @endif
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right text-gray-600">
                                        @if ($row['avg_tread_at_removal'] !== null)
                                            {{ number_format($row['avg_tread_at_removal'], 1) }}/32"
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-4 text-xs text-gray-400">Sorted fastest to slowest. Wear rate = tread lost per 1,000 miles.</p>
                </div>
            </div>
        </div>
    </div>
</div>
