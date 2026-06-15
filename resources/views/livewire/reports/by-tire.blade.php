<?php

use App\Actions\SelectVehicle;
use App\Models\Placement;
use App\Models\Vehicle;
use App\Services\WearReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?int $vehicle_id;
    protected Vehicle $vehicle;

    /** Tracks which tire's notes are expanded. */
    public array $expanded = [];

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
        return app(WearReportService::class)->wearByTire($this->vehicle);
    }

    #[Computed]
    public function chartData(): array
    {
        $tires = $this->vehicle->tires()->with(['placements' => function ($q) {
            $q->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                ->where('rotations.is_setup', false)
                ->orderBy('rotations.odometer')
                ->select('placements.*', 'rotations.odometer as rotation_odometer');
        }])->get();

        return $tires->map(fn ($tire) => [
            'label' => $tire->label,
            'points' => $tire->placements->map(fn ($p) => [
                'odometer' => (int) $p->rotation_odometer,
                'tread' => (float) $p->tread_center,
            ])->values()->all(),
        ])->values()->all();
    }

    public function toggleNotes(string $tireId): void
    {
        $this->expanded[$tireId] = ! ($this->expanded[$tireId] ?? false);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.reports.by-tire');
    }
};

?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Wear by Tire') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Tire table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left border-b border-gray-200">
                                <th class="pb-3 font-semibold text-gray-600">Tire</th>
                                <th class="pb-3 font-semibold text-gray-600">Position</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Center</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Inner / Outer</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Avg Wear /1k mi</th>
                                <th class="pb-3 font-semibold text-gray-600 text-right">Miles to 2/32"</th>
                                <th class="pb-3 font-semibold text-gray-600">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($this->report as $row)
                                @php
                                    $tire = $row['tire'];
                                    $isExpanded = $this->expanded[$tire->id] ?? false;
                                    $hasScallop = $row['latest_tread_inner'] !== null
                                        && $row['latest_tread_outer'] !== null
                                        && abs($row['latest_tread_inner'] - $row['latest_tread_outer']) >= 2;
                                @endphp
                                <tr class="hover:bg-gray-50 align-top">
                                    <td class="py-3">
                                        <a href="{{ route('tires.show', $tire) }}" class="font-bold text-ink-900 hover:text-steel-600 hover:underline">
                                            {{ $tire->label }}
                                        </a>
                                        @if ($tire->brand)
                                            <div class="text-xs text-gray-400">{{ $tire->brand }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 text-gray-700">{{ $row['current_position']?->label() ?? '—' }}</td>
                                    <td class="py-3 text-right font-mono">
                                        {{ $row['latest_tread_center'] !== null ? $row['latest_tread_center'].'/32"' : '—' }}
                                    </td>
                                    <td class="py-3 text-right">
                                        @if ($row['latest_tread_inner'] !== null || $row['latest_tread_outer'] !== null)
                                            <span class="inline-flex items-center gap-1 justify-end">
                                                <span class="{{ $hasScallop ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                                    {{ $row['latest_tread_inner'] ?? '?' }} / {{ $row['latest_tread_outer'] ?? '?' }}
                                                </span>
                                                @if ($hasScallop)
                                                    <x-scallop-warning />
                                                @endif
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right font-mono text-gray-700">
                                        {{ $row['lifetime_avg_wear_per_1000mi'] !== null ? number_format($row['lifetime_avg_wear_per_1000mi'], 2) : '—' }}
                                    </td>
                                    <td class="py-3 text-right text-gray-700">
                                        @if ($row['projected_miles'] !== null)
                                            ≈ {{ number_format($row['projected_miles']) }}
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 max-w-xs">
                                        @if (count($row['notes']) > 0)
                                            <p class="text-xs text-gray-600 truncate">{{ $row['notes'][0] }}</p>
                                            @if (count($row['notes']) > 1)
                                                <button wire:click="toggleNotes('{{ $tire->id }}')" class="text-xs text-steel-600 hover:underline mt-0.5">
                                                    {{ $isExpanded ? 'hide' : '+'.( count($row['notes']) - 1).' more' }}
                                                </button>
                                                @if ($isExpanded)
                                                    <div class="mt-1 space-y-0.5">
                                                        @foreach (array_slice($row['notes'], 1) as $note)
                                                            <p class="text-xs text-gray-600">{{ $note }}</p>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            @endif
                                        @else
                                            <span class="text-gray-400 text-xs">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <p class="mt-3 text-xs text-gray-400">
                        <x-phosphor-warning-circle-duotone class="w-3.5 h-3.5 inline text-red-400" />
                        = uneven inner/outer wear ≥ 2/32". Check pressure (target 30 PSI) and alignment.
                        Projection requires ≥ 2 intervals.
                    </p>
                </div>
            </div>

            {{-- Tread over time chart --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-base font-semibold text-gray-700 mb-4">Tread Depth Over Time</h3>
                    @php
                        $colors = ['#2563eb','#7c3aed','#0891b2','#16a34a','#d97706'];
                        $data = $this->chartData;
                        $allOdometers = collect($data)->flatMap(fn ($t) => collect($t['points'])->pluck('odometer'))->filter()->values();
                        $allTreads = collect($data)->flatMap(fn ($t) => collect($t['points'])->pluck('tread'))->filter()->values();
                        $minOdo = $allOdometers->min() ?? 0;
                        $maxOdo = $allOdometers->max() ?? 1;
                        $minTread = 0;
                        $maxTread = max(($allTreads->max() ?? 16), 16);
                        $w = 600; $h = 220; $pad = ['t' => 10, 'r' => 20, 'b' => 36, 'l' => 36];
                        $innerW = $w - $pad['l'] - $pad['r'];
                        $innerH = $h - $pad['t'] - $pad['b'];
                        $xScale = fn ($odo) => $innerW > 0 && $maxOdo > $minOdo
                            ? $pad['l'] + ($odo - $minOdo) / ($maxOdo - $minOdo) * $innerW
                            : $pad['l'];
                        $yScale = fn ($tread) => $pad['t'] + (1 - ($tread - $minTread) / max($maxTread - $minTread, 1)) * $innerH;
                    @endphp
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full" xmlns="http://www.w3.org/2000/svg">
                        {{-- Replacement threshold line at 2/32" --}}
                        <line
                            x1="{{ $pad['l'] }}" y1="{{ $yScale(2) }}"
                            x2="{{ $w - $pad['r'] }}" y2="{{ $yScale(2) }}"
                            stroke="#ef4444" stroke-width="1" stroke-dasharray="4,3" />
                        <text x="{{ $pad['l'] + 2 }}" y="{{ $yScale(2) - 3 }}" fill="#ef4444" font-size="9">2/32" limit</text>

                        {{-- Y axis ticks --}}
                        @foreach ([2, 4, 6, 8, 10, 12, 14, 16] as $tick)
                            @if ($tick <= $maxTread)
                                <line x1="{{ $pad['l'] - 4 }}" y1="{{ $yScale($tick) }}" x2="{{ $pad['l'] }}" y2="{{ $yScale($tick) }}" stroke="#9ca3af" stroke-width="1"/>
                                <text x="{{ $pad['l'] - 6 }}" y="{{ $yScale($tick) + 3 }}" text-anchor="end" fill="#6b7280" font-size="9">{{ $tick }}</text>
                            @endif
                        @endforeach

                        {{-- Lines per tire --}}
                        @foreach ($data as $i => $series)
                            @php
                                $color = $colors[$i % count($colors)];
                                $pts = $series['points'];
                                $polyline = collect($pts)->map(fn ($p) => $xScale($p['odometer']).' '.$yScale($p['tread']))->join(' ');
                            @endphp
                            @if (count($pts) >= 2)
                                <polyline points="{{ $polyline }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round"/>
                            @endif
                            @foreach ($pts as $p)
                                <circle cx="{{ $xScale($p['odometer']) }}" cy="{{ $yScale($p['tread']) }}" r="3" fill="{{ $color }}"/>
                            @endforeach
                            {{-- Legend --}}
                            <rect x="{{ $pad['l'] + ($i * 70) }}" y="{{ $h - $pad['b'] + 14 }}" width="10" height="4" fill="{{ $color }}" rx="1"/>
                            <text x="{{ $pad['l'] + ($i * 70) + 13 }}" y="{{ $h - $pad['b'] + 19 }}" fill="#374151" font-size="9">{{ $series['label'] }}</text>
                        @endforeach

                        {{-- Axes --}}
                        <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] }}" x2="{{ $pad['l'] }}" y2="{{ $pad['t'] + $innerH }}" stroke="#d1d5db" stroke-width="1"/>
                        <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] + $innerH }}" x2="{{ $pad['l'] + $innerW }}" y2="{{ $pad['t'] + $innerH }}" stroke="#d1d5db" stroke-width="1"/>

                        {{-- X axis labels --}}
                        @if ($maxOdo > $minOdo)
                            <text x="{{ $xScale($minOdo) }}" y="{{ $pad['t'] + $innerH + 12 }}" text-anchor="middle" fill="#6b7280" font-size="9">{{ number_format($minOdo) }}</text>
                            <text x="{{ $xScale($maxOdo) }}" y="{{ $pad['t'] + $innerH + 12 }}" text-anchor="middle" fill="#6b7280" font-size="9">{{ number_format($maxOdo) }}</text>
                        @endif

                        {{-- Y axis label --}}
                        <text x="10" y="{{ $pad['t'] + $innerH / 2 }}" transform="rotate(-90 10 {{ $pad['t'] + $innerH / 2 }})" text-anchor="middle" fill="#6b7280" font-size="9">tread (32nds)</text>
                    </svg>
                </div>
            </div>

        </div>
    </div>
</div>
