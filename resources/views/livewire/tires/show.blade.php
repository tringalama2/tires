<?php

use App\Enums\TireStatus;
use App\Models\Tire;
use App\Services\WearReportService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    public Tire $tire;
    public bool $editing = false;

    #[Validate('nullable|string|max:50')]
    public ?string $brand = null;

    #[Validate('nullable|string|max:50')]
    public ?string $model = null;

    #[Validate('nullable|string|max:12')]
    public ?string $tin = null;

    #[Validate('nullable|string|max:30')]
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
    public function history(): \Illuminate\Support\Collection
    {
        return $this->tire->placements()
            ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
            ->where('rotations.is_setup', false)
            ->orderBy('rotations.odometer')
            ->select('placements.*', 'rotations.rotated_on', 'rotations.odometer as rotation_odometer')
            ->get();
    }

    #[Computed]
    public function projectedMiles(): ?float
    {
        return app(WearReportService::class)->projectedReplacementMileage($this->tire);
    }

    #[Computed]
    public function currentPosition(): ?string
    {
        return app(\App\Services\TireService::class)->currentPosition($this->tire)?->label();
    }

    #[Computed]
    public function chartPoints(): array
    {
        return $this->history->map(fn ($p) => [
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Tire {{ $tire->label }}
                @if ($this->currentPosition)
                    <span class="text-gray-400 font-normal text-lg">— {{ $this->currentPosition }}</span>
                @endif
            </h2>
            <x-treadmark.button variant="ghost" size="sm" href="{{ route('reports.by-tire') }}">
                <x-treadmark.icon name="arrow-left" class="w-4 h-4" /> Back to report
            </x-treadmark.button>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Tire info card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-semibold text-gray-700">Tire Info</h3>
                        @if (! $editing)
                            <x-treadmark.button variant="secondary" size="sm" wire:click="startEdit">
                            <x-treadmark.icon name="pencil-simple" class="w-4 h-4" /> Edit
                        </x-treadmark.button>
                        @endif
                    </div>

                    @if ($editing)
                        <form wire:submit="save" class="grid grid-cols-2 gap-4">
                            <x-treadmark.input
                                wire:model="brand"
                                type="text"
                                label="Brand"
                                :error="$errors->first('brand')"
                            />
                            <x-treadmark.input
                                wire:model="model"
                                type="text"
                                label="Model"
                                :error="$errors->first('model')"
                            />
                            <x-treadmark.input
                                wire:model="tin"
                                type="text"
                                label="DOT / TIN"
                                maxlength="12"
                                :error="$errors->first('tin')"
                            />
                            <x-treadmark.input
                                wire:model="size"
                                type="text"
                                label="Size"
                                :error="$errors->first('size')"
                            />
                            <x-treadmark.input
                                wire:model="purchased_on"
                                type="date"
                                label="Purchase Date"
                                :error="$errors->first('purchased_on')"
                            />
                            <div class="col-span-2">
                                <p class="text-sm font-medium text-gray-600 mb-2">Condition</p>
                                <div class="flex flex-wrap gap-x-6 gap-y-2">
                                    @foreach ([
                                        'has_cracking' => 'Cracking / dry rot',
                                        'has_bulge' => 'Sidewall bulge',
                                        'has_cupping' => 'Cupping',
                                        'has_puncture_repair' => 'Plug / patch',
                                    ] as $field => $label)
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer select-none">
                                            <input type="checkbox" wire:model="{{ $field }}"
                                                class="rounded border-gray-300 text-blaze-600 focus:ring-blaze-500">
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                            <div class="flex items-end gap-2">
                                <x-treadmark.button type="submit">Save</x-treadmark.button>
                                <x-treadmark.button type="button" variant="ghost" size="sm" wire:click="cancelEdit">Cancel</x-treadmark.button>
                            </div>
                        </form>
                    @else
                        <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Brand</dt>
                                <dd class="font-medium text-gray-800">{{ $tire->brand ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Model</dt>
                                <dd class="font-medium text-gray-800">{{ $tire->model ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">DOT / TIN</dt>
                                <dd class="font-medium text-gray-800">{{ $tire->tin ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Size</dt>
                                <dd class="font-medium text-gray-800">{{ $tire->size ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Purchased</dt>
                                <dd class="font-medium text-gray-800">{{ $tire->purchased_on?->format('M j, Y') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Status</dt>
                                <dd class="font-medium text-gray-800">{{ $tire->status->label() }}</dd>
                            </div>
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
                            <div class="mt-3 flex flex-wrap gap-1">
                                @foreach ($activeConditions as $condition)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">{{ $condition }}</span>
                                @endforeach
                            </div>
                        @endif
                    @endif

                    @if ($this->projectedMiles !== null)
                        <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-700">
                            Projected replacement: <span class="font-semibold">≈ {{ number_format($this->projectedMiles) }} miles to 2/32"</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Rotation history table --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="font-semibold text-gray-700 mb-4">Rotation History</h3>
                    @if ($this->history->isEmpty())
                        <p class="text-sm text-gray-400">No rotation history yet.</p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left border-b border-gray-200">
                                        <th class="pb-2 font-semibold text-gray-600">Date</th>
                                        <th class="pb-2 font-semibold text-gray-600 text-right">Odometer</th>
                                        <th class="pb-2 font-semibold text-gray-600">From</th>
                                        <th class="pb-2 font-semibold text-gray-600">To</th>
                                        <th class="pb-2 font-semibold text-gray-600 text-right">Center</th>
                                        <th class="pb-2 font-semibold text-gray-600 text-right">Inner / Outer</th>
                                        <th class="pb-2 font-semibold text-gray-600">Wear Pattern</th>
                                        <th class="pb-2 font-semibold text-gray-600">Note</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($this->history as $p)
                                        @php
                                            $scalloped = $p->is_cupped;
                                            $wearTags = array_filter([
                                                $p->isCenterWear() ? 'Center' : null,
                                                $p->isEdgeWear() ? 'Edge' : null,
                                                $p->is_feathering ? 'Feathering' : null,
                                                $p->is_cupped ? 'Cupping' : null,
                                            ]);
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2 text-gray-700 whitespace-nowrap">{{ \Carbon\Carbon::parse($p->rotated_on)->format('M j, Y') }}</td>
                                            <td class="py-2 text-right text-gray-600 font-mono">{{ number_format($p->rotation_odometer) }}</td>
                                            <td class="py-2 text-gray-600">{{ $p->from_position?->label() ?? '—' }}</td>
                                            <td class="py-2 text-gray-600">{{ $p->to_position->label() }}</td>
                                            <td class="py-2 text-right font-mono text-gray-700">{{ $p->tread_center }}/32"</td>
                                            <td class="py-2 text-right">
                                                @if ($p->tread_inner !== null || $p->tread_outer !== null)
                                                    <span class="{{ $scalloped ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                                        {{ $p->tread_inner ?? '?' }} / {{ $p->tread_outer ?? '?' }}
                                                    </span>
                                                    @if ($scalloped)
                                                        <x-scallop-warning />
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
                                                @endif
                                            </td>
                                            <td class="py-2">
                                                @if ($wearTags)
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach ($wearTags as $tag)
                                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    <span class="text-gray-400 text-xs">—</span>
                                                @endif
                                            </td>
                                            <td class="py-2 text-gray-600 max-w-xs text-xs">{{ $p->note ?? '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Tread chart --}}
            @if (count($this->chartPoints) >= 2)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-700 mb-4">Tread Depth Over Time</h3>
                        @php
                            $pts = $this->chartPoints;
                            $minOdo = collect($pts)->min('odometer');
                            $maxOdo = collect($pts)->max('odometer');
                            $maxTread = max(collect($pts)->max('tread'), 16);
                            $w = 500; $h = 180; $pad = ['t' => 10, 'r' => 20, 'b' => 30, 'l' => 32];
                            $innerW = $w - $pad['l'] - $pad['r'];
                            $innerH = $h - $pad['t'] - $pad['b'];
                            $xScale = fn ($o) => $innerW > 0 && $maxOdo > $minOdo
                                ? $pad['l'] + ($o - $minOdo) / ($maxOdo - $minOdo) * $innerW : $pad['l'];
                            $yScale = fn ($t) => $pad['t'] + (1 - $t / max($maxTread, 1)) * $innerH;
                            $polyline = collect($pts)->map(fn ($p) => $xScale($p['odometer']).' '.$yScale($p['tread']))->join(' ');
                        @endphp
                        <svg viewBox="0 0 {{ $w }} {{ $h }}" class="w-full" xmlns="http://www.w3.org/2000/svg">
                            <line x1="{{ $pad['l'] }}" y1="{{ $yScale(2) }}" x2="{{ $w - $pad['r'] }}" y2="{{ $yScale(2) }}"
                                stroke="#ef4444" stroke-width="1" stroke-dasharray="4,3" />
                            <text x="{{ $pad['l'] + 2 }}" y="{{ $yScale(2) - 3 }}" fill="#ef4444" font-size="8">2/32"</text>
                            <polyline points="{{ $polyline }}" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linejoin="round"/>
                            @foreach ($pts as $p)
                                <circle cx="{{ $xScale($p['odometer']) }}" cy="{{ $yScale($p['tread']) }}" r="4" fill="#2563eb"/>
                                <text x="{{ $xScale($p['odometer']) }}" y="{{ $yScale($p['tread']) - 6 }}" text-anchor="middle" fill="#1d4ed8" font-size="8">{{ $p['tread'] }}</text>
                            @endforeach
                            <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] }}" x2="{{ $pad['l'] }}" y2="{{ $pad['t'] + $innerH }}" stroke="#d1d5db" stroke-width="1"/>
                            <line x1="{{ $pad['l'] }}" y1="{{ $pad['t'] + $innerH }}" x2="{{ $pad['l'] + $innerW }}" y2="{{ $pad['t'] + $innerH }}" stroke="#d1d5db" stroke-width="1"/>
                            @foreach ([4, 8, 12, 16] as $tick)
                                @if ($tick <= $maxTread)
                                    <text x="{{ $pad['l'] - 4 }}" y="{{ $yScale($tick) + 3 }}" text-anchor="end" fill="#6b7280" font-size="8">{{ $tick }}</text>
                                @endif
                            @endforeach
                            <text x="{{ $xScale($minOdo) }}" y="{{ $h - 4 }}" text-anchor="middle" fill="#6b7280" font-size="8">{{ number_format($minOdo) }}</text>
                            <text x="{{ $xScale($maxOdo) }}" y="{{ $h - 4 }}" text-anchor="middle" fill="#6b7280" font-size="8">{{ number_format($maxOdo) }}</text>
                        </svg>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
