<?php

use App\Enums\TireStatus;
use App\Models\Tire;
use App\Services\WearReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')]
class extends Component {

    public Tire $tire;
    public bool $editing = false;

    #[Validate('required|string|max:255')]
    public string $label = '';

    #[Validate('nullable|string|max:255')]
    public ?string $brand = null;

    #[Validate('nullable|string|max:255')]
    public ?string $model = null;

    #[Validate('nullable|string|max:12')]
    public ?string $tin = null;

    #[Validate('nullable|string|max:255')]
    public ?string $size = null;

    #[Validate('nullable|date')]
    public ?string $purchased_on = null;

    #[Validate('boolean')]
    public bool $has_cracking = false;

    #[Validate('boolean')]
    public bool $has_bulge = false;

    #[Validate('boolean')]
    public bool $has_cupping = false;

    #[Validate('boolean')]
    public bool $has_puncture_repair = false;

    public function mount(): void
    {
        $this->authorize('view', $this->tire);

        $this->label = $this->tire->label;
        $this->brand = $this->tire->brand;
        $this->model = $this->tire->model;
        $this->tin = $this->tire->tin;
        $this->size = $this->tire->size;
        $this->purchased_on = $this->tire->purchased_on?->toDateString();
        $this->has_cracking = (bool) $this->tire->has_cracking;
        $this->has_bulge = (bool) $this->tire->has_bulge;
        $this->has_cupping = (bool) $this->tire->has_cupping;
        $this->has_puncture_repair = (bool) $this->tire->has_puncture_repair;
    }

    #[Computed]
    public function duplicateLabel(): bool
    {
        $trimmed = trim($this->label);
        if ($trimmed === '') {
            return false;
        }

        return $this->tire->vehicle->tires()
            ->active()
            ->where('label', $trimmed)
            ->where('id', '!=', $this->tire->id)
            ->exists();
    }

    #[Computed]
    public function history(): \Illuminate\Support\Collection
    {
        return $this->tire->wearPlacements()->addSelect('rotations.rotated_on')->get();
    }

    #[Computed]
    public function projectedMiles(): ?float
    {
        return app(WearReportService::class)->projectedReplacementMileage($this->tire);
    }

    #[Computed]
    public function currentPosition(): ?string
    {
        return $this->tire->currentPosition()?->label();
    }

    #[Computed]
    public function chartPoints(): array
    {
        return $this->history->map(fn($p) => [
            'odometer' => (int) $p->rotation_odometer,
            'tread' => (float) $p->tread_center,
        ])->values()->all();
    }

    public function startEdit(): void
    {
        $this->editing = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->tire);
        $this->validate();
        $this->tire->update([
            'label' => $this->label,
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'tin' => $this->tin ?: null,
            'size' => $this->size ?: null,
            'purchased_on' => $this->purchased_on ?: null,
            'has_cracking' => $this->has_cracking,
            'has_bulge' => $this->has_bulge,
            'has_cupping' => $this->has_cupping,
            'has_puncture_repair' => $this->has_puncture_repair,
        ]);
        $this->editing = false;
    }

    public function cancelEdit(): void
    {
        $this->label = $this->tire->label;
        $this->brand = $this->tire->brand;
        $this->model = $this->tire->model;
        $this->tin = $this->tire->tin;
        $this->size = $this->tire->size;
        $this->purchased_on = $this->tire->purchased_on?->toDateString();
        $this->has_cracking = (bool) $this->tire->has_cracking;
        $this->has_bulge = (bool) $this->tire->has_bulge;
        $this->has_cupping = (bool) $this->tire->has_cupping;
        $this->has_puncture_repair = (bool) $this->tire->has_puncture_repair;
        $this->editing = false;
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tires.show');
    }
};

?>

<div>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h1 class="font-display font-semibold uppercase text-2xl tracking-wider text-ink-900">Tire
                <span class="font-mono">{{ $tire->label }}</span></h1>
            <x-treadmark.button variant="ghost" size="sm" href="{{ route('tires.index') }}">
                <x-treadmark.icon name="arrow-left" class="w-4 h-4"/>
                Back to all tires
            </x-treadmark.button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-4">

            {{-- Tire info card --}}
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-md overflow-hidden">

                {{-- Dark header --}}
                <div class="flex items-center justify-between gap-4 px-8 py-6 bg-ink-900">
                    <div>
                        <div class="font-display font-semibold uppercase tracking-wider text-xl text-white">
                            {{ $tire->brand ? $tire->brand.' '.($tire->model ?? '') : 'Tire '.$tire->label }}
                        </div>
                        <div class="font-mono text-xs tracking-widest text-ink-300 mt-1 uppercase">
                            @if ($tire->size)
                                {{ $tire->size }} ·
                            @endif
                            @if ($tire->tin)
                                                  DOT {{ $tire->tin }} ·
                            @endif
                            @if ($tire->purchased_on)
                                                  purchased {{ $tire->purchased_on->format('M Y') }}
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        @php
                            $isActive = $tire->status->value === 'active';
                        @endphp
                        <x-treadmark.badge :tone="$isActive ? 'success' : 'neutral'" dot>
                            {{ $tire->status->label() }}
                        </x-treadmark.badge>
                        @if (! $editing)
                            <x-treadmark.button variant="inverse" size="sm" wire:click="startEdit">
                                <x-treadmark.icon name="pencil-simple" class="w-4 h-4"/>
                                Edit
                            </x-treadmark.button>
                        @endif
                    </div>
                </div>

                @if ($editing)
                    {{-- Edit form --}}
                    <form wire:submit="save" class="px-8 py-6 grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <x-treadmark.input wire:model.live="label" type="text" label="Label" :error="$errors->first('label')"/>
                            @if ($this->duplicateLabel)
                                <p class="mt-1.5 text-[12px] text-[#8A6000]">'{{ trim($label) }}' is already used by an
                                                                             active tire.</p>
                            @endif
                        </div>
                        <x-treadmark.input wire:model="brand" type="text" label="Brand" placeholder="BF Goodrich" :error="$errors->first('brand')"/>
                        <x-treadmark.input wire:model="model" type="text" label="Model" placeholder="KO2" :error="$errors->first('model')"/>
                        <x-treadmark.input wire:model="tin" type="text" label="DOT / TIN" placeholder="DOT XXXX XXXX XX" maxlength="12" :error="$errors->first('tin')"/>
                        <x-treadmark.input wire:model="size" type="text" label="Size" placeholder="275/70R18" :error="$errors->first('size')"/>
                        <x-treadmark.input wire:model="purchased_on" type="date" label="Purchase Date" :error="$errors->first('purchased_on')"/>
                        <div class="col-span-2">
                            <p class="font-mono text-[11px] tracking-widest uppercase text-ink-400 mb-2">Condition
                                                                                                         Flags</p>
                            <div class="flex flex-wrap gap-x-6 gap-y-2">
                                @foreach ([
                                    'has_cracking' => 'Cracking / dry rot',
                                    'has_bulge' => 'Sidewall bulge',
                                    'has_cupping' => 'Cupping',
                                    'has_puncture_repair' => 'Plug / patch',
                                ] as $field => $label)
                                    <label class="flex items-center gap-2 text-sm text-ink-700 cursor-pointer select-none">
                                        <input type="checkbox" wire:model="{{ $field }}" class="rounded border-ink-300 text-blaze-600 focus:ring-blaze-500">
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex items-end justify-between gap-2 col-span-2">
                            <x-treadmark.button type="button" variant="ghost" size="sm" wire:click="cancelEdit">Cancel
                            </x-treadmark.button>
                            <x-treadmark.button type="submit">Save</x-treadmark.button>
                        </div>
                    </form>
                @else
                    {{-- Stat row --}}
                    <div class="grid grid-cols-3 gap-px bg-ink-100 border-b border-ink-100">
                        <div class="bg-white px-6 py-4">
                            <div class="flex flex-col gap-1">
                                <span class="font-mono uppercase tracking-caps text-[11px] text-ink-400">Current Position</span>
                                <div class="mt-0.5">
                                    @if ($this->currentPosition)
                                        @php
                                            $pos = $tire->currentPosition();
                                        @endphp
                                        <x-treadmark.position-tag :position="$pos->value" size="md" show-label/>
                                    @else
                                        <span class="font-display font-semibold text-[21px] text-ink-300">—</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="bg-white px-6 py-4">
                            @php
                                $latestPlacement = $this->history->last();
                                $latestTread = $latestPlacement?->tread_center;
                            @endphp
                            <x-treadmark.stat-tile size="sm" label="Latest Tread" :value="$latestTread !== null ? (string) $latestTread : '—'" unit='/32"' mono/>
                        </div>
                        <div class="bg-white px-6 py-4">
                            <x-treadmark.stat-tile size="sm" label='Projected replacement' :value="$this->projectedMiles !== null ? '≈ '.number_format($this->projectedMiles) : '—'" unit="mi" mono/>
                        </div>
                    </div>

                    {{-- Details grid --}}
                    <div class="px-8 py-6">
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-8 gap-y-4">
                            @foreach ([
                                'Brand' => $tire->brand,
                                'Model' => $tire->model,
                                'DOT / TIN' => $tire->tin,
                                'Size' => $tire->size,
                                'Purchased' => $tire->purchased_on?->format('M j, Y'),
                                'Rotations' => (string) $this->history->count(),
                            ] as $dtLabel => $dtValue)
                                <div>
                                    <dt class="font-mono text-[10px] tracking-widest uppercase text-ink-400">{{ $dtLabel }}</dt>
                                    <dd class="mt-0.5 font-medium text-ink-800 text-sm">{{ $dtValue ?? '—' }}</dd>
                                </div>
                            @endforeach
                        </dl>

                        @php
                            $activeConditions = array_filter([
                                $tire->has_cracking ? 'Cracking' : null,
                                $tire->has_bulge ? 'Bulge' : null,
                                $tire->has_cupping ? 'Cupping' : null,
                                $tire->has_puncture_repair ? 'Plug/Patch' : null,
                            ]);
                        @endphp
                        @if ($activeConditions)
                            <div class="mt-4 flex flex-wrap gap-1.5">
                                @foreach ($activeConditions as $condition)
                                    <x-treadmark.badge tone="gold" size="sm">{{ $condition }}</x-treadmark.badge>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Rotation history table --}}
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">
                <div class="px-8 py-4 border-b border-ink-100">
                    <div class="font-display font-semibold uppercase tracking-wider text-sm text-ink-900">Rotation
                                                                                                          History
                    </div>
                </div>

                @if ($this->history->isEmpty())
                    <div class="px-8 py-6 text-sm text-ink-400">No rotation history yet.</div>
                @else
                    <div class="px-8 py-4 overflow-x-auto">
                        <div class="grid grid-cols-[1fr_0.9fr_0.5fr_0.5fr_0.8fr_0.8fr_1fr_1.2fr] min-w-[680px]">

                            {{-- Header row --}}
                            @php $hdr = 'pb-2 border-b border-ink-200 font-mono text-[10px] tracking-widest uppercase text-ink-400'; @endphp
                            <div class="{{ $hdr }}">Date</div>
                            <div class="{{ $hdr }} text-right">Odometer</div>
                            <div class="{{ $hdr }}">From</div>
                            <div class="{{ $hdr }}">To</div>
                            <div class="{{ $hdr }}">Tread</div>
                            <div class="{{ $hdr }}">Inner / Outer</div>
                            <div class="{{ $hdr }}">Wear Pattern</div>
                            <div class="{{ $hdr }}">Note</div>

                            {{-- Data rows --}}
                            @foreach ($this->history as $p)
                                @php
                                    $scalloped = $p->is_cupped;
                                    $wearTags = array_filter([
                                        $p->isCenterWear() ? 'Center' : null,
                                        $p->isEdgeWear() ? 'Edge' : null,
                                        $p->is_feathering ? 'Feathering' : null,
                                        $p->is_cupped ? 'Cupping' : null,
                                    ]);
                                    $rowBorder = $loop->last ? 'py-3' : 'py-3 border-b border-ink-100';
                                @endphp

                                <div class="{{ $rowBorder }} text-sm text-ink-700 whitespace-nowrap">
                                    {{ \Carbon\Carbon::parse($p->rotated_on)->format('M j, Y') }}
                                </div>
                                <div class="{{ $rowBorder }} text-right font-mono text-sm text-ink-500">
                                    {{ number_format($p->rotation_odometer) }}
                                </div>
                                <div class="{{ $rowBorder }}">
                                    @if ($p->from_position)
                                        <x-treadmark.position-tag :position="$p->from_position->value" size="sm"/>
                                    @else
                                        <span class="text-ink-300 text-xs">—</span>
                                    @endif
                                </div>
                                <div class="{{ $rowBorder }}">
                                    @if ($p->to_position)
                                        <x-treadmark.position-tag :position="$p->to_position->value" size="sm"/>
                                    @else
                                        <span class="text-ink-300 text-xs">—</span>
                                    @endif
                                </div>
                                <div class="{{ $rowBorder }} pr-2">
                                    @if ($p->tread_center !== null)
                                        <x-treadmark.tread-gauge :depth="$p->tread_center" size="sm"/>
                                    @else
                                        <span class="text-ink-300 font-mono text-xs">—</span>
                                    @endif
                                </div>
                                <div class="{{ $rowBorder }}">
                                    @if ($p->tread_inner !== null || $p->tread_outer !== null)
                                        <span class="font-mono text-xs {{ $scalloped ? 'text-rust-600 font-semibold' : 'text-ink-400' }}">
                                            {{ $p->tread_inner ?? '?' }} / {{ $p->tread_outer ?? '?' }}
                                        </span>
                                        @if ($scalloped)
                                            <x-scallop-warning/>
                                        @endif
                                    @else
                                        <span class="text-ink-300 text-xs">—</span>
                                    @endif
                                </div>
                                <div class="{{ $rowBorder }}">
                                    @if ($wearTags)
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($wearTags as $tag)
                                                <x-treadmark.badge tone="gold" size="sm">{{ $tag }}</x-treadmark.badge>
                                            @endforeach
                                        </div>
                                    @else
                                        <span class="text-ink-300 text-xs">—</span>
                                    @endif
                                </div>
                                <div class="{{ $rowBorder }} text-ink-500 text-xs leading-snug">{{ $p->note ?? '' }}</div>

                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            {{-- Tread chart --}}
            @if (count($this->chartPoints) >= 2)
                <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">
                    <div class="px-8 py-4 border-b border-ink-100 font-display font-semibold uppercase tracking-wider text-sm text-ink-900">
                        Tread Depth Over Time
                    </div>
                    <div class="p-6">
                        @php
                            $pts = $this->chartPoints;
                            $minOdo = collect($pts)->min('odometer');
                            $maxOdo = collect($pts)->max('odometer');
                            $maxTread = max(collect($pts)->max('tread'), 16);
                            $w = 600; $h = 200; $pad = ['t' => 10, 'r' => 20, 'b' => 36, 'l' => 36];
                            $innerW = $w - $pad['l'] - $pad['r'];
                            $innerH = $h - $pad['t'] - $pad['b'];
                            $xScale = fn ($o) => $innerW > 0 && $maxOdo > $minOdo
                                ? $pad['l'] + ($o - $minOdo) / ($maxOdo - $minOdo) * $innerW : $pad['l'];
                            $yScale = fn ($t) => $pad['t'] + (1 - $t / max($maxTread, 1)) * $innerH;
                            $polyline = collect($pts)->map(fn ($p) => $xScale($p['odometer']).' '.$yScale($p['tread']))->join(' ');
                        @endphp
                        <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full" xmlns="http://www.w3.org/2000/svg">
                            {{-- 2/32" limit line --}}
                            <line x1="{{ $pad['l'] }}" y1="{{ $yScale(2) }}" x2="{{ $w - $pad['r'] }}" y2="{{ $yScale(2) }}"
                                  stroke="#C42B22" stroke-width="1" stroke-dasharray="4,3"/>
                            <text x="{{ $pad['l'] + 2 }}" y="{{ $yScale(2) - 3 }}" fill="#C42B22" font-size="9">2/32"
                                                                                                                limit
                            </text>

                            {{-- Y axis ticks --}}
                            @foreach ([2, 4, 6, 8, 10, 12, 14, 16] as $tick)
                                @if ($tick <= $maxTread)
                                    <line x1="{{ $pad['l'] - 4 }}" y1="{{ $yScale($tick) }}" x2="{{ $pad['l'] }}" y2="{{ $yScale($tick) }}" stroke="#C9D1C8" stroke-width="1"/>
                                    <text x="{{ $pad['l'] - 6 }}" y="{{ $yScale($tick) + 3 }}" text-anchor="end" fill="#7C877B" font-size="9">{{ $tick }}</text>
                                @endif
                            @endforeach

                            {{-- Tread line --}}
                            <polyline points="{{ $polyline }}" fill="none" stroke="#FF5400" stroke-width="2.5" stroke-linejoin="round"/>
                            @foreach ($pts as $p)
                                <circle cx="{{ $xScale($p['odometer']) }}" cy="{{ $yScale($p['tread']) }}" r="4" fill="#FF5400"/>
                                <text x="{{ $xScale($p['odometer']) }}" y="{{ $yScale($p['tread']) - 7 }}" text-anchor="middle" fill="#CC4300" font-size="9">{{ $p['tread'] }}</text>
                            @endforeach

                            {{-- Axes --}}
                            <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] }}" x2="{{ $pad['l'] }}" y2="{{ $pad['t'] + $innerH }}" stroke="#C9D1C8" stroke-width="1"/>
                            <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] + $innerH }}" x2="{{ $pad['l'] + $innerW }}" y2="{{ $pad['t'] + $innerH }}" stroke="#C9D1C8" stroke-width="1"/>

                            @if ($maxOdo > $minOdo)
                                <text x="{{ $xScale($minOdo) }}" y="{{ $pad['t'] + $innerH + 12 }}" text-anchor="middle" fill="#7C877B" font-size="9">{{ number_format($minOdo) }}</text>
                                <text x="{{ $xScale($maxOdo) }}" y="{{ $pad['t'] + $innerH + 12 }}" text-anchor="middle" fill="#7C877B" font-size="9">{{ number_format($maxOdo) }}</text>
                            @endif

                            <text x="10" y="{{ $pad['t'] + $innerH / 2 }}"
                                  transform="rotate(-90 10 {{ $pad['t'] + $innerH / 2 }})"
                                  text-anchor="middle" fill="#7C877B" font-size="9">tread (32nds)
                            </text>
                        </svg>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
