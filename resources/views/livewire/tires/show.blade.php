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

    public function mount(): void
    {
        $this->brand = $this->tire->brand;
        $this->model = $this->tire->model;
        $this->tin = $this->tire->tin;
        $this->size = $this->tire->size;
        $this->purchased_on = $this->tire->purchased_on?->toDateString();
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
        $this->validate();
        $this->tire->update([
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'tin' => $this->tin ?: null,
            'size' => $this->size ?: null,
            'purchased_on' => $this->purchased_on ?: null,
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
            <a href="{{ route('reports.by-tire') }}" class="text-sm text-blue-600 hover:underline">← Back to report</a>
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
                            <button wire:click="startEdit" class="text-sm text-blue-600 hover:underline">Edit</button>
                        @endif
                    </div>

                    @if ($editing)
                        <form wire:submit="save" class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label value="Brand" />
                                <x-text-input wire:model="brand" class="mt-1 block w-full" type="text" />
                                <x-input-error :messages="$errors->get('brand')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Model" />
                                <x-text-input wire:model="model" class="mt-1 block w-full" type="text" />
                                <x-input-error :messages="$errors->get('model')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="DOT / TIN" />
                                <x-text-input wire:model="tin" class="mt-1 block w-full" type="text" maxlength="12" />
                                <x-input-error :messages="$errors->get('tin')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Size" />
                                <x-text-input wire:model="size" class="mt-1 block w-full" type="text" />
                                <x-input-error :messages="$errors->get('size')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Purchase Date" />
                                <x-text-input wire:model="purchased_on" class="mt-1 block w-full" type="date" />
                                <x-input-error :messages="$errors->get('purchased_on')" class="mt-1" />
                            </div>
                            <div class="flex items-end gap-2">
                                <x-primary-button type="submit">Save</x-primary-button>
                                <button type="button" wire:click="cancelEdit" class="text-sm text-gray-500 hover:underline">Cancel</button>
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
                                        <th class="pb-2 font-semibold text-gray-600">Note</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach ($this->history as $p)
                                        @php
                                            $scalloped = $p->tread_inner !== null
                                                && $p->tread_outer !== null
                                                && abs($p->tread_inner - $p->tread_outer) >= 2;
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
                                                        <x-phosphor-warning-circle-duotone class="w-3.5 h-3.5 inline text-red-500" title="Uneven wear. Check pressure and alignment." />
                                                    @endif
                                                @else
                                                    <span class="text-gray-400">—</span>
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
