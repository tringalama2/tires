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
    public function odometerThrough(): ?int
    {
        return $this->vehicle->rotations()->where('is_setup', false)->max('odometer');
    }

    #[Computed]
    public function rotationCount(): int
    {
        return $this->vehicle->rotations()->where('is_setup', false)->count();
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
        <h1 class="font-display font-semibold uppercase text-2xl tracking-wider text-ink-900">Wear by Tire</h1>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Report card --}}
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-md overflow-hidden">

                {{-- Dark header --}}
                <div class="flex items-center justify-between gap-4 px-8 py-6 bg-ink-900">
                    <div>
                        <div class="font-display font-semibold uppercase tracking-wider text-xl text-white">Wear Report</div>
                        <div class="font-mono text-xs tracking-widest text-ink-300 mt-1 uppercase">
                            {{ session('vehicle')?->year }} {{ session('vehicle')?->make }} {{ session('vehicle')?->model }}
                            @if ($this->odometerThrough)
                                · through {{ number_format($this->odometerThrough) }} mi
                            @endif
                        </div>
                    </div>
                    <x-treadmark.icon name="tire" class="w-8 h-8 text-ink-500 flex-none" />
                </div>

                {{-- Stat row --}}
                <div class="grid grid-cols-3 gap-px bg-ink-100 border-b border-ink-100">
                    <div class="bg-white px-6 py-4">
                        <x-treadmark.stat-tile size="sm" label="Odometer" :value="$this->odometerThrough ? number_format($this->odometerThrough) : '—'" unit="mi" mono />
                    </div>
                    <div class="bg-white px-6 py-4">
                        <x-treadmark.stat-tile size="sm" label="Rotations" :value="(string) $this->rotationCount" sub="since install" />
                    </div>
                    <div class="bg-white px-6 py-4">
                        <x-treadmark.stat-tile size="sm" label="Tires tracked" :value="(string) $this->report->count()" />
                    </div>
                </div>

                {{-- Table --}}
                <div class="px-8 py-6 overflow-x-auto">
                    <div class="font-display font-semibold uppercase tracking-wider text-sm text-ink-900 mb-4">Wear by tire</div>

                    <div class="grid grid-cols-[0.7fr_0.5fr_1.4fr_0.8fr_0.9fr_1fr] pb-2 border-b border-ink-200 font-mono text-[10px] tracking-widest uppercase text-ink-400 min-w-[560px]">
                        <span>Tire</span>
                        <span>Pos</span>
                        <span>Tread</span>
                        <span class="text-right">Avg /1k</span>
                        <span class="text-right">To 2/32"</span>
                        <span>Notes</span>
                    </div>

                    @foreach ($this->report as $row)
                        @php
                            $tire = $row['tire'];
                            $isExpanded = $this->expanded[$tire->id] ?? false;
                            $hasScallop = $row['latest_is_cupped'];
                        @endphp
                        <div class="grid grid-cols-[0.7fr_0.5fr_1.4fr_0.8fr_0.9fr_1fr] items-start py-3 border-b border-ink-100 last:border-0 min-w-[560px]">

                            {{-- Tire label --}}
                            <div>
                                <a href="{{ route('tires.show', $tire) }}" class="font-mono font-semibold text-sm text-ink-900 hover:text-steel-600 hover:underline">
                                    {{ $tire->label }}
                                </a>
                                @if ($tire->brand)
                                    <div class="text-xs text-ink-400">{{ $tire->brand }}</div>
                                @endif
                                @php
                                    $conditionBadges = array_filter([
                                        $tire->has_cracking ? 'Cracking' : null,
                                        $tire->has_bulge ? 'Bulge' : null,
                                        $tire->has_cupping ? 'Cupping' : null,
                                        $tire->has_puncture_repair ? 'Plug/Patch' : null,
                                    ]);
                                @endphp
                                @foreach ($conditionBadges as $badge)
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800 mt-0.5">{{ $badge }}</span>
                                @endforeach
                            </div>

                            {{-- Position tag --}}
                            <div class="pt-0.5">
                                @if ($row['current_position'])
                                    <x-treadmark.position-tag :position="$row['current_position']->value" size="sm" />
                                @else
                                    <span class="text-ink-300 text-xs">—</span>
                                @endif
                            </div>

                            {{-- Tread gauge + inner/outer --}}
                            <div class="pr-4 pt-0.5">
                                @if ($row['latest_tread_center'] !== null)
                                    <x-treadmark.tread-gauge :depth="$row['latest_tread_center']" size="sm" />
                                @else
                                    <span class="text-ink-300 font-mono text-xs">—</span>
                                @endif
                                @if ($row['latest_tread_inner'] !== null || $row['latest_tread_outer'] !== null)
                                    <div class="mt-1 flex items-center gap-1">
                                        <span class="font-mono text-xs {{ $hasScallop ? 'text-rust-600 font-semibold' : 'text-ink-400' }}">
                                            {{ $row['latest_tread_inner'] ?? '?' }} / {{ $row['latest_tread_outer'] ?? '?' }}
                                        </span>
                                        @if ($hasScallop)
                                            <x-scallop-warning />
                                        @endif
                                    </div>
                                @endif
                            </div>

                            {{-- Avg wear --}}
                            <span class="text-right font-mono text-sm text-ink-600 pt-0.5">
                                {{ $row['lifetime_avg_wear_per_1000mi'] !== null ? number_format($row['lifetime_avg_wear_per_1000mi'], 2) : '—' }}
                            </span>

                            {{-- Projected miles --}}
                            <span class="text-right font-mono text-sm text-ink-500 pt-0.5">
                                @if ($row['projected_miles'] !== null)
                                    ≈ {{ number_format($row['projected_miles']) }}
                                @else
                                    <span class="text-ink-300">—</span>
                                @endif
                            </span>

                            {{-- Notes --}}
                            <div class="max-w-xs pt-0.5">
                                @if (count($row['notes']) > 0)
                                    <p class="text-xs text-ink-500 truncate">{{ $row['notes'][0] }}</p>
                                    @if (count($row['notes']) > 1)
                                        <button wire:click="toggleNotes('{{ $tire->id }}')" class="text-xs text-steel-600 hover:underline mt-0.5">
                                            {{ $isExpanded ? 'hide' : '+'.( count($row['notes']) - 1).' more' }}
                                        </button>
                                        @if ($isExpanded)
                                            <div class="mt-1 space-y-0.5">
                                                @foreach (array_slice($row['notes'], 1) as $note)
                                                    <p class="text-xs text-ink-500">{{ $note }}</p>
                                                @endforeach
                                            </div>
                                        @endif
                                    @endif
                                @else
                                    <span class="text-ink-300 text-xs">—</span>
                                @endif
                            </div>

                        </div>
                    @endforeach

                    <p class="mt-4 text-xs text-ink-400 leading-relaxed">
                        <x-treadmark.icon name="warning-circle-fill" class="w-3.5 h-3.5 inline text-rust-400 align-middle" />
                        = uneven inner/outer wear ≥ 2/32". Check pressure and alignment.
                        Projection to 2/32" requires ≥ 2 wear intervals. Readings are hand-gauged (±1/32").
                    </p>
                </div>
            </div>

            {{-- Tread over time chart --}}
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-ink-100 font-display font-semibold uppercase tracking-wider text-sm text-ink-900">
                    Tread depth over time
                </div>
                <div class="p-6">
                    @php
                        $chartColors = ['#FF5400','#2F8F52','#3B82C4','#C98A00','#C42B22'];
                        $data = $this->chartData;
                        $allOdometers = collect($data)->flatMap(fn ($t) => collect($t['points'])->pluck('odometer'))->filter()->values();
                        $allTreads = collect($data)->flatMap(fn ($t) => collect($t['points'])->pluck('tread'))->filter()->values();
                        $minOdo = $allOdometers->min() ?? 0;
                        $maxOdo = $allOdometers->max() ?? 1;
                        $maxTread = max(($allTreads->max() ?? 16), 16);
                        $w = 600; $h = 220; $pad = ['t' => 10, 'r' => 20, 'b' => 36, 'l' => 36];
                        $innerW = $w - $pad['l'] - $pad['r'];
                        $innerH = $h - $pad['t'] - $pad['b'];
                        $xScale = fn ($odo) => $innerW > 0 && $maxOdo > $minOdo
                            ? $pad['l'] + ($odo - $minOdo) / ($maxOdo - $minOdo) * $innerW
                            : $pad['l'];
                        $yScale = fn ($tread) => $pad['t'] + (1 - $tread / max($maxTread, 1)) * $innerH;
                    @endphp
                    <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full" xmlns="http://www.w3.org/2000/svg">
                        {{-- 2/32" threshold line --}}
                        <line x1="{{ $pad['l'] }}" y1="{{ $yScale(2) }}"
                              x2="{{ $w - $pad['r'] }}" y2="{{ $yScale(2) }}"
                              stroke="#C42B22" stroke-width="1" stroke-dasharray="4,3" />
                        <text x="{{ $pad['l'] + 2 }}" y="{{ $yScale(2) - 3 }}" fill="#C42B22" font-size="9">2/32" limit</text>

                        {{-- Y axis ticks --}}
                        @foreach ([2, 4, 6, 8, 10, 12, 14, 16] as $tick)
                            @if ($tick <= $maxTread)
                                <line x1="{{ $pad['l'] - 4 }}" y1="{{ $yScale($tick) }}"
                                      x2="{{ $pad['l'] }}" y2="{{ $yScale($tick) }}"
                                      stroke="#C9D1C8" stroke-width="1"/>
                                <text x="{{ $pad['l'] - 6 }}" y="{{ $yScale($tick) + 3 }}"
                                      text-anchor="end" fill="#7C877B" font-size="9">{{ $tick }}</text>
                            @endif
                        @endforeach

                        {{-- Lines per tire --}}
                        @foreach ($data as $i => $series)
                            @php
                                $color = $chartColors[$i % count($chartColors)];
                                $pts = $series['points'];
                                $polyline = collect($pts)->map(fn ($p) => $xScale($p['odometer']).' '.$yScale($p['tread']))->join(' ');
                            @endphp
                            @if (count($pts) >= 2)
                                <polyline points="{{ $polyline }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round"/>
                            @endif
                            @foreach ($pts as $p)
                                <circle cx="{{ $xScale($p['odometer']) }}" cy="{{ $yScale($p['tread']) }}" r="3" fill="{{ $color }}"/>
                            @endforeach
                            <rect x="{{ $pad['l'] + ($i * 70) }}" y="{{ $h - $pad['b'] + 14 }}" width="10" height="4" fill="{{ $color }}" rx="1"/>
                            <text x="{{ $pad['l'] + ($i * 70) + 13 }}" y="{{ $h - $pad['b'] + 19 }}" fill="#586257" font-size="9">{{ $series['label'] }}</text>
                        @endforeach

                        {{-- Axes --}}
                        <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] }}"
                              x2="{{ $pad['l'] }}" y2="{{ $pad['t'] + $innerH }}"
                              stroke="#C9D1C8" stroke-width="1"/>
                        <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] + $innerH }}"
                              x2="{{ $pad['l'] + $innerW }}" y2="{{ $pad['t'] + $innerH }}"
                              stroke="#C9D1C8" stroke-width="1"/>

                        @if ($maxOdo > $minOdo)
                            <text x="{{ $xScale($minOdo) }}" y="{{ $pad['t'] + $innerH + 12 }}"
                                  text-anchor="middle" fill="#7C877B" font-size="9">{{ number_format($minOdo) }}</text>
                            <text x="{{ $xScale($maxOdo) }}" y="{{ $pad['t'] + $innerH + 12 }}"
                                  text-anchor="middle" fill="#7C877B" font-size="9">{{ number_format($maxOdo) }}</text>
                        @endif

                        <text x="10" y="{{ $pad['t'] + $innerH / 2 }}"
                              transform="rotate(-90 10 {{ $pad['t'] + $innerH / 2 }})"
                              text-anchor="middle" fill="#7C877B" font-size="9">tread (32nds)</text>
                    </svg>
                </div>
            </div>

        </div>
    </div>
</div>
