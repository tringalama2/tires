<?php

use App\Actions\SelectVehicle;
use App\Livewire\Concerns\ResolvesActiveVehicle;
use App\Services\WearReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    use ResolvesActiveVehicle;

    #[Locked]
    public ?int $vehicle_id;

    public function mount(SelectVehicle $selectVehicle): void
    {
        $this->resolveVehicle($selectVehicle);
    }

    #[Computed]
    public function report(): \Illuminate\Support\Collection
    {
        return app(WearReportService::class)->wearByPosition($this->vehicle());
    }

    #[Computed]
    public function fastestPosition(): ?array
    {
        return $this->report->whereNotNull('avg_wear_per_1000mi')->sortByDesc('avg_wear_per_1000mi')->first();
    }

    #[Computed]
    public function outlierAlert(): ?string
    {
        $outlier = app(WearReportService::class)->unevenWearOutlier($this->vehicle(), 2.0);

        return $outlier
            ? $outlier['position']->label().' is wearing significantly faster. Check alignment or consider rotating more frequently.'
            : null;
    }

    #[Computed]
    public function odometerThrough(): ?int
    {
        return $this->vehicle()->rotations()->real()->max('odometer');
    }

    #[Computed]
    public function rotationCount(): int
    {
        return $this->vehicle()->rotations()->real()->count();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.reports.by-position');
    }
};

?>

<div>
    <x-slot name="header">
        <h1 class="font-display font-semibold uppercase text-2xl tracking-wider text-ink-900">Wear by Position</h1>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

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
                    <x-treadmark.icon name="chart-bar" class="w-8 h-8 text-ink-500 flex-none" />
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
                        @if ($this->fastestPosition)
                            <x-treadmark.stat-tile size="sm" tone="brand" label="Fastest position"
                                :value="$this->fastestPosition['position']->label()"
                                :sub="number_format($this->fastestPosition['avg_wear_per_1000mi'], 2) . ' /1k mi'" />
                        @else
                            <x-treadmark.stat-tile size="sm" label="Fastest position" value="—" sub="Need more data" />
                        @endif
                    </div>
                </div>

                {{-- Outlier alert --}}
                @if ($this->outlierAlert)
                    <div class="px-8 pt-5">
                        <x-treadmark.alert tone="warn" title="Uneven wear">{{ $this->outlierAlert }}</x-treadmark.alert>
                    </div>
                @endif

                {{-- Table --}}
                <div class="px-8 py-6">
                    <div class="font-display font-semibold uppercase tracking-wider text-sm text-ink-900 mb-4">Wear by position</div>

                    <div class="grid grid-cols-[2fr_0.6fr_1fr_1fr] pb-2 border-b border-ink-200 font-mono text-[10px] tracking-widest uppercase text-ink-400">
                        <span>Position</span>
                        <span class="text-right">Intervals</span>
                        <span class="text-right">Avg /1k mi</span>
                        <span class="text-right">Avg at removal</span>
                    </div>

                    @foreach ($this->report->sortByDesc('avg_wear_per_1000mi') as $row)
                        @php $isFastest = $this->fastestPosition && $row['position'] === $this->fastestPosition['position']; @endphp
                        <div class="grid grid-cols-[2fr_0.6fr_1fr_1fr] items-center py-3 border-b border-ink-100 last:border-0">
                            <span class="flex items-center gap-2">
                                <x-treadmark.position-tag :position="$row['position']->value" size="sm" :active="$isFastest" />
                                <span class="text-sm text-ink-700">{{ $row['position']->label() }}</span>
                            </span>
                            <span class="text-right font-mono text-sm text-ink-500">{{ $row['intervals'] ?: '—' }}</span>
                            <span class="text-right font-mono text-sm {{ $isFastest ? 'font-semibold text-ink-900' : 'text-ink-600' }}">
                                @if ($row['avg_wear_per_1000mi'] !== null)
                                    {{ number_format($row['avg_wear_per_1000mi'], 2) }}
                                    @if ($isFastest)
                                        <span class="ml-1 text-[10px] bg-blaze-100 text-blaze-600 px-1.5 py-0.5 rounded-pill font-display uppercase tracking-caps">fastest</span>
                                    @endif
                                @else
                                    <span class="text-ink-300">—</span>
                                @endif
                            </span>
                            <span class="text-right font-mono text-sm text-ink-500">
                                @if ($row['avg_tread_at_removal'] !== null)
                                    {{ number_format($row['avg_tread_at_removal'], 1) }}/32"
                                @else
                                    <span class="text-ink-300">—</span>
                                @endif
                            </span>
                        </div>
                    @endforeach

                    <p class="mt-4 text-xs text-ink-400 leading-relaxed">
                        Wear rate = tread lost per 1,000 miles, charged to the position the tire left. Readings are hand-gauged (±1/32").
                    </p>
                </div>
            </div>

        </div>
    </div>
</div>
