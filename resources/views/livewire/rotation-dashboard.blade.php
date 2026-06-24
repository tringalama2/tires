<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Livewire\Concerns\ResolvesActiveVehicle;
use App\Models\Rotation;
use App\Services\WearReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app')]
class extends Component {
    use ResolvesActiveVehicle;

    #[Locked]
    public string|int|null $vehicle_id = null;

    public function mount(SelectVehicle $selectVehicle): void
    {
        $vehicle = $this->resolveVehicle($selectVehicle);

        if ($vehicle->tires()->count() === 0) {
            $this->redirect(route('vehicles.setuptires.index', ['vehicle' => $vehicle]));
        }
    }

    #[Computed]
    public function latestRotation(): ?Rotation
    {
        return $this->vehicle()->rotations()->real()->orderByDesc('odometer')->first();
    }

    #[Computed]
    public function currentOdometer(): int
    {
        return $this->latestRotation?->odometer ?? $this->vehicle()->starting_odometer;
    }

    #[Computed]
    public function daysSinceRotation(): ?int
    {
        if (! $this->latestRotation) {
            return null;
        }

        return (int) Carbon::parse($this->latestRotation->rotated_on)->diffInDays(Carbon::today());
    }

    #[Computed]
    public function replacementAlerts(): Collection
    {
        return $this->allTiresSortedByMilesLeft
            ->filter(fn ($r) => $r['projected_miles'] !== null && $r['projected_miles'] <= 10000)
            ->values();
    }

    #[Computed]
    public function allTiresSortedByMilesLeft(): Collection
    {
        return app(WearReportService::class)->wearByTire($this->vehicle(), TireStatus::Active)
            ->sortBy(fn ($r) => $r['projected_miles'] ?? PHP_INT_MAX)
            ->values();
    }

    #[Computed]
    public function currentPositions(): Collection
    {
        $order = array_flip(array_map(fn ($p) => $p->value, TirePosition::order()));

        return $this->allTiresSortedByMilesLeft
            ->filter(fn ($r) => $r['current_position'] !== null)
            ->sortBy(fn ($r) => $order[$r['current_position']->value])
            ->values();
    }

    #[Computed]
    public function fastestWearPosition(): ?array
    {
        return app(WearReportService::class)->wearByPosition($this->vehicle())
            ->whereNotNull('avg_wear_per_1000mi')
            ->sortByDesc('avg_wear_per_1000mi')
            ->first();
    }

    #[Computed]
    public function unevenWearAlert(): ?string
    {
        $outlier = app(WearReportService::class)->unevenWearOutlier($this->vehicle(), 1.5);

        return $outlier
            ? $outlier['position']->label().' is wearing significantly faster than the rest. Check alignment or rotate more frequently.'
            : null;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.rotation-dashboard');
    }
};

?>

<div>
    <x-slot name="header">
        <div class="flex items-baseline justify-between gap-4">
            <div>
                <h1 class="font-display font-semibold uppercase text-2xl tracking-wider text-ink-900">Dashboard</h1>
                <p class="text-ink-500 text-sm mt-0.5">
                    {{ session('vehicle')?->year }} {{ session('vehicle')?->make }} {{ session('vehicle')?->model }}
                    · {{ session('vehicle')?->tires()->count() }} tires in rotation
                </p>
            </div>
            <x-treadmark.button href="{{ route('rotations.prepare') }}" size="sm">
                <x-treadmark.icon name="arrows-clockwise" class="w-4 h-4"/>
                Log Rotation
            </x-treadmark.button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Stat row --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @if ($this->latestRotation)
                    <x-treadmark.card>
                        <x-treadmark.stat-tile
                            label="Last rotation"
                            value="{{ $this->latestRotation->rotated_on->format('M j, Y') }}"
                            sub="{{ $this->daysSinceRotation }} day{{ $this->daysSinceRotation === 1 ? '' : 's' }} ago"
                        />
                    </x-treadmark.card>
                @endif

                <x-treadmark.card @class(['col-span-2 sm:col-span-1' => ! $this->latestRotation])>
                    <x-treadmark.stat-tile
                        label="Odometer"
                        value="{{ number_format($this->currentOdometer) }}"
                        unit="mi"
                        mono
                    />
                </x-treadmark.card>

                @if ($this->latestRotation)
                    <x-treadmark.card tone="inverse" class="col-span-2 sm:col-span-1">
                        @if ($this->fastestWearPosition)
                            <x-treadmark.stat-tile
                                tone="inverse"
                                label="Fastest wear"
                                value="{{ $this->fastestWearPosition['position']->label() }}"
                                sub="{{ number_format($this->fastestWearPosition['avg_wear_per_1000mi'], 2) }} /1k mi"
                            />
                        @else
                            <x-treadmark.stat-tile
                                tone="inverse"
                                label="Fastest wear"
                                value="—"
                                sub="More data needed"
                            />
                        @endif
                    </x-treadmark.card>
                @endif
            </div>

            {{-- Replacement alerts --}}
            @if ($this->replacementAlerts->isNotEmpty())
                <x-treadmark.alert tone="danger" title="Tires nearing replacement">
                    <ul class="mt-1 space-y-1">
                        @foreach ($this->replacementAlerts as $alert)
                            <li>
                                <a href="{{ route('tires.show', $alert['tire']) }}"
                                   class="font-mono font-semibold underline underline-offset-2">{{ $alert['tire']->label }}</a>
                                ({{ $alert['current_position']?->label() ?? '—' }})
                                — ≈ {{ number_format($alert['projected_miles']) }} miles to 2/32"
                            </li>
                        @endforeach
                    </ul>
                </x-treadmark.alert>
            @endif

            {{-- Uneven wear alert --}}
            @if ($this->unevenWearAlert)
                <x-treadmark.alert tone="warn" title="Uneven wear detected">
                    {{ $this->unevenWearAlert }}
                </x-treadmark.alert>
            @endif

            {{-- Rotation note --}}
            @if ($this->latestRotation?->note)
                <p class="text-sm text-ink-500 italic px-1">{{ $this->latestRotation->note }}</p>
            @endif

            {{-- Main 2-column grid --}}
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-4">

                {{-- Current positions card — always visible once tires are set up --}}
                <div @class([
                    'bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden',
                    'lg:col-span-3' => $this->latestRotation,
                    'lg:col-span-5' => ! $this->latestRotation,
                ])>
                    <div class="px-5 py-4 border-b border-ink-100 font-display font-semibold uppercase tracking-wider text-base text-ink-900">
                        Current positions
                    </div>
                    <div class="p-5">
                        @if ($this->currentPositions->isNotEmpty())
                            {{-- 3-col grid: left tires | car silhouette | right tires --}}
                            <div class="grid grid-cols-[1fr_60px_1fr] items-center gap-x-3 gap-y-5">
                                {{-- FL --}}
                                @php $fl = $this->currentPositions->firstWhere('current_position.value', 'FL'); @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <x-treadmark.position-tag position="FL" size="sm"/>
                                        <span class="font-mono font-semibold text-sm text-ink-700">{{ $fl['tire']->label ?? '—' }}</span>
                                    </div>
                                    @if ($fl && $fl['latest_tread_center'] !== null)
                                        <x-treadmark.tread-gauge :depth="$fl['latest_tread_center']" size="sm" class="w-full"/>
                                    @endif
                                </div>

                                {{-- Car silhouette (spans 2 rows) --}}
                                <div class="row-span-2 flex items-center justify-center">
                                    <x-img.car-top-view class="w-12 fill-ink-200" aria-hidden="true"/>
                                </div>

                                {{-- FR --}}
                                @php $fr = $this->currentPositions->firstWhere('current_position.value', 'FR'); @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <x-treadmark.position-tag position="FR" size="sm"/>
                                        <span class="font-mono font-semibold text-sm text-ink-700">{{ $fr['tire']->label ?? '—' }}</span>
                                    </div>
                                    @if ($fr && $fr['latest_tread_center'] !== null)
                                        <x-treadmark.tread-gauge :depth="$fr['latest_tread_center']" size="sm" class="w-full"/>
                                    @endif
                                </div>

                                {{-- RL --}}
                                @php $rl = $this->currentPositions->firstWhere('current_position.value', 'RL'); @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <x-treadmark.position-tag position="RL" size="sm"/>
                                        <span class="font-mono font-semibold text-sm text-ink-700">{{ $rl['tire']->label ?? '—' }}</span>
                                    </div>
                                    @if ($rl && $rl['latest_tread_center'] !== null)
                                        <x-treadmark.tread-gauge :depth="$rl['latest_tread_center']" size="sm" class="w-full"/>
                                    @endif
                                </div>

                                {{-- RR --}}
                                @php $rr = $this->currentPositions->firstWhere('current_position.value', 'RR'); @endphp
                                <div class="flex flex-col items-center gap-1.5">
                                    <div class="flex items-center gap-1.5">
                                        <x-treadmark.position-tag position="RR" size="sm"/>
                                        <span class="font-mono font-semibold text-sm text-ink-700">{{ $rr['tire']->label ?? '—' }}</span>
                                    </div>
                                    @if ($rr && $rr['latest_tread_center'] !== null)
                                        <x-treadmark.tread-gauge :depth="$rr['latest_tread_center']" size="sm" class="w-full"/>
                                    @endif
                                </div>

                                {{-- SP — full width below --}}
                                @php $sp = $this->currentPositions->firstWhere('current_position.value', 'SP'); @endphp
                                @if ($sp)
                                    <div class="col-span-3 flex flex-col items-center gap-1.5 pt-1 border-t border-ink-100">
                                        <div class="flex items-center gap-1.5">
                                            <x-treadmark.position-tag position="SP" size="sm"/>
                                            <span class="font-mono font-semibold text-sm text-ink-700">{{ $sp['tire']->label }}</span>
                                            <span class="text-xs text-ink-400 font-mono">spare</span>
                                        </div>
                                        @if ($sp['latest_tread_center'] !== null)
                                            <div class="w-32">
                                                <x-treadmark.tread-gauge :depth="$sp['latest_tread_center']" size="sm"/>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-ink-400 text-center py-4">No position data yet.</p>
                        @endif

                        @if (! $this->latestRotation)
                            <div class="mt-5 pt-4 border-t border-ink-100 text-center">
                                <x-treadmark.button href="{{ route('rotations.prepare') }}">
                                    Log First Rotation
                                </x-treadmark.button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Nearing replacement card (2/5) — only once rotations exist --}}
                @if ($this->latestRotation)
                    <div class="lg:col-span-2 bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden flex flex-col">
                        <div class="px-5 py-4 border-b border-ink-100 font-display font-semibold uppercase tracking-wider text-base text-ink-900">
                            Nearing replacement
                        </div>
                        <div class="p-5 flex flex-col gap-4 flex-1">
                            @forelse ($this->allTiresSortedByMilesLeft->filter(fn($r) => $r['projected_miles'] !== null) as $t)
                                <div>
                                    <div class="flex justify-between items-baseline mb-1.5">
                                        <span class="font-mono font-semibold text-sm text-ink-900">
                                            {{ $t['tire']->label }}
                                            @if ($t['current_position'])
                                                <span class="text-ink-400 font-normal">·</span>
                                                <x-treadmark.position-tag position="{{ $t['current_position']->value }}" size="sm"/>
                                            @endif
                                        </span>
                                        <span class="font-mono text-xs text-ink-500">≈ {{ number_format($t['projected_miles']) }} mi</span>
                                    </div>
                                    <x-treadmark.tread-gauge :depth="$t['latest_tread_center']" size="sm"/>
                                </div>
                            @empty
                                <p class="text-sm text-ink-400 text-center py-4">Need 2+ rotations per tire for projections.</p>
                            @endforelse

                            <div class="mt-auto pt-4 border-t border-ink-100 flex flex-col gap-2">
                                <x-treadmark.button href="{{ route('rotations.prepare') }}" class="w-full justify-center">
                                    Log Rotation
                                </x-treadmark.button>
                                <x-treadmark.button href="{{ route('reports.by-tire') }}" variant="ghost" size="sm" class="w-full justify-center">
                                    View wear reports
                                </x-treadmark.button>
                            </div>
                        </div>
                    </div>
                @endif

            </div>

        </div>
    </div>

    {{-- FAB: always-accessible Log Rotation on mobile --}}
    <a href="{{ route('rotations.prepare') }}"
       class="sm:hidden fixed bottom-6 right-4 z-10 inline-flex items-center gap-2 bg-blaze-500 hover:bg-blaze-600 active:bg-blaze-700 text-white font-display font-semibold uppercase tracking-wider2 rounded-full px-5 py-3 shadow-lg transition-colors duration-150">
        <x-treadmark.icon name="arrows-clockwise" class="w-4 h-4"/>
        Log Rotation
    </a>
</div>
