<?php

use App\Actions\SelectVehicle;
use App\Models\Vehicle;
use App\Services\RotationService;
use App\Services\TireService;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    #[Locked]
    public ?int $vehicle_id = null;

    public string $step = 'entry'; // 'entry' | 'review'

    public string $rotated_on = '';
    public ?int $odometer = null;

    /**
     * Keyed by tire UUID. Each entry:
     *   retiring       bool
     *   retiring_tread string|null
     *   replacement_label      string
     *   replacement_brand      string|null
     *   replacement_model      string|null
     *   replacement_tread      string
     *   replacement_tin        string|null
     *   replacement_size       string|null
     *   replacement_purchased_on string|null
     */
    public array $swaps = [];

    public ?string $validationError = null;

    private Vehicle $vehicle;

    public function mount(SelectVehicle $selectVehicle, TireService $tireService): void
    {
        if (isset($this->vehicle_id)) {
            $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
            $this->authorize('view', $this->vehicle);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->vehicle_id = $this->vehicle->id;
        $this->rotated_on = Carbon::today()->toDateString();

        foreach ($this->vehicle->activeTires()->orderBy('label')->get() as $tire) {
            $pos = $tireService->currentPosition($tire);
            $this->swaps[$tire->id] = [
                'tire_label' => $tire->label,
                'tire_brand' => $tire->brand,
                'tire_model' => $tire->model,
                'current_position' => $pos?->label() ?? '—',
                'current_position_value' => $pos?->value,
                'retiring' => false,
                'retiring_tread' => '',
                'replacement_label' => '',
                'replacement_brand' => '',
                'replacement_model' => '',
                'replacement_tread' => '',
                'replacement_tin' => '',
                'replacement_size' => '',
                'replacement_purchased_on' => Carbon::today()->toDateString(),
            ];
        }
    }

    #[Computed]
    public function activeSwaps(): array
    {
        return array_filter($this->swaps, fn ($s) => $s['retiring']);
    }

    #[Computed]
    public function lastOdometer(): ?int
    {
        return $this->vehicle()->rotations()->max('odometer');
    }

    public function toReview(): void
    {
        $this->validationError = null;

        if (empty($this->activeSwaps)) {
            $this->validationError = 'Select at least one tire to retire.';
            return;
        }

        if (empty($this->rotated_on)) {
            $this->validationError = 'Date is required.';
            return;
        }

        if (empty($this->odometer)) {
            $this->validationError = 'Odometer is required.';
            return;
        }

        $lastOdo = $this->lastOdometer;
        if ($lastOdo !== null && $this->odometer < $lastOdo) {
            $this->validationError = 'Odometer must be at least the last recorded rotation ('.number_format($lastOdo).' mi).';
            return;
        }

        foreach ($this->activeSwaps as $tireId => $swap) {
            if (empty(trim($swap['replacement_label']))) {
                $this->validationError = "Replacement label is required for retiring {$swap['tire_label']}.";
                return;
            }
            if ($swap['replacement_tread'] === '' || $swap['replacement_tread'] === null) {
                $this->validationError = "Starting tread is required for the replacement of {$swap['tire_label']}.";
                return;
            }
            if (! empty($swap['replacement_tin']) && strlen(trim($swap['replacement_tin'])) > 12) {
                $this->validationError = "DOT/TIN for the replacement of {$swap['tire_label']} must be 12 characters or fewer.";
                return;
            }
            if (! empty($swap['replacement_purchased_on']) && ! strtotime($swap['replacement_purchased_on'])) {
                $this->validationError = "Purchase date for the replacement of {$swap['tire_label']} is not a valid date.";
                return;
            }
        }

        $this->step = 'review';
    }

    public function back(): void
    {
        $this->step = 'entry';
        $this->validationError = null;
    }

    public function save(RotationService $rotationService): void
    {
        $this->validationError = null;

        $swapData = [];
        foreach ($this->activeSwaps as $tireId => $swap) {
            $swapData[] = [
                'retiring_tire_id' => $tireId,
                'retiring_tread' => $swap['retiring_tread'] !== '' ? (float) $swap['retiring_tread'] : null,
                'replacement_label' => trim($swap['replacement_label']),
                'replacement_brand' => trim($swap['replacement_brand']) ?: null,
                'replacement_model' => trim($swap['replacement_model']) ?: null,
                'replacement_tread' => (float) $swap['replacement_tread'],
                'replacement_tin' => trim($swap['replacement_tin']) ?: null,
                'replacement_size' => trim($swap['replacement_size']) ?: null,
                'replacement_purchased_on' => $swap['replacement_purchased_on'] ?: null,
            ];
        }

        try {
            $rotationService->saveSwap([
                'rotated_on' => $this->rotated_on,
                'odometer' => $this->odometer,
                'swaps' => $swapData,
            ], $this->vehicle());
        } catch (ValidationException $e) {
            $this->step = 'entry';
            $this->validationError = collect($e->errors())->flatten()->first();
            return;
        }

        $this->redirect(route('dashboard', $this->vehicle_id), navigate: true);
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::findOrFail($this->vehicle_id);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.rotations.swap');
    }
};

?>

<div>
    <x-slot name="header">
        <h2 class="font-display font-semibold uppercase tracking-wide text-[18px] text-ink-900">
            Retire &amp; Replace
        </h2>
        <p class="text-sm text-ink-500 mt-0.5">
            {{ $step === 'review' ? 'Step 2 of 2 — Review' : 'Step 1 of 2 — Select tires' }}
        </p>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">

            @if ($validationError)
                <x-treadmark.alert tone="danger">{{ $validationError }}</x-treadmark.alert>
            @endif

            {{-- ── STEP 1: ENTRY ── --}}
            @if ($step === 'entry')

                {{-- Date + Odometer --}}
                <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm p-5 space-y-4">
                    <h3 class="font-display font-semibold uppercase tracking-wide text-[13px] text-ink-500">Swap details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <x-treadmark.input
                            wire:model="rotated_on"
                            type="date"
                            label="Date"
                            required
                        />
                        <x-treadmark.input
                            wire:model="odometer"
                            type="number"
                            label="Odometer (mi)"
                            :placeholder="$this->lastOdometer ? number_format($this->lastOdometer) : ''"
                            required
                        />
                    </div>
                </div>

                {{-- Tire list --}}
                <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-ink-100">
                        <span class="font-display font-semibold uppercase tracking-wide text-[15px] text-ink-900">Active tires</span>
                        <p class="text-sm text-ink-400 mt-0.5">Toggle a tire to retire it and enter the replacement.</p>
                    </div>

                    <ul class="divide-y divide-ink-100">
                        @foreach ($swaps as $tireId => $swap)
                            <li class="p-5 space-y-4" wire:key="{{ $tireId }}">

                                {{-- Tire header row --}}
                                <div class="flex items-center gap-4">
                                    <x-treadmark.position-tag
                                        :position="$swap['current_position_value'] ?? 'SP'"
                                        size="md"
                                        :active="! $swap['retiring']"
                                    />
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-[14px] text-ink-900">
                                            {{ $swap['tire_label'] }}
                                            @if ($swap['tire_brand'] || $swap['tire_model'])
                                                <span class="font-normal text-ink-400">&middot; {{ trim($swap['tire_brand'].' '.$swap['tire_model']) }}</span>
                                            @endif
                                        </div>
                                        <div class="text-[12px] text-ink-400 mt-0.5">{{ $swap['current_position'] }}</div>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="$set('swaps.{{ $tireId }}.retiring', {{ $swap['retiring'] ? 'false' : 'true' }})"
                                        class="flex-none inline-flex items-center gap-1.5 font-display font-semibold uppercase tracking-wider2 text-[11px] px-3 py-1.5 rounded-control border transition-colors
                                            {{ $swap['retiring']
                                                ? 'bg-red-50 border-red-300 text-red-600 hover:bg-red-100'
                                                : 'bg-ink-50 border-ink-200 text-ink-500 hover:bg-ink-100' }}"
                                    >
                                        <x-treadmark.icon name="{{ $swap['retiring'] ? 'x' : 'trash' }}" class="w-3.5 h-3.5" />
                                        {{ $swap['retiring'] ? 'Cancel' : 'Retire' }}
                                    </button>
                                </div>

                                {{-- Replacement form (expanded when retiring) --}}
                                @if ($swap['retiring'])
                                    <div class="ml-14 space-y-3">
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 border-t border-ink-100 pt-3">Retiring tire final tread (optional)</div>
                                        <x-treadmark.input
                                            wire:model="swaps.{{ $tireId }}.retiring_tread"
                                            type="number"
                                            label="Final tread (32nds)"
                                            placeholder="e.g. 4"
                                            step="0.5"
                                            min="0"
                                            max="20"
                                        />

                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-ink-400 border-t border-ink-100 pt-3">Replacement tire</div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <x-treadmark.input
                                                wire:model="swaps.{{ $tireId }}.replacement_label"
                                                type="text"
                                                label="Label"
                                                placeholder="T6"
                                                required
                                            />
                                            <x-treadmark.input
                                                wire:model="swaps.{{ $tireId }}.replacement_tread"
                                                type="number"
                                                label="Starting tread (32nds)"
                                                placeholder="e.g. 15"
                                                step="0.5"
                                                min="1"
                                                max="20"
                                                required
                                            />
                                            <x-treadmark.input
                                                wire:model="swaps.{{ $tireId }}.replacement_brand"
                                                type="text"
                                                label="Brand"
                                                placeholder="BF Goodrich"
                                            />
                                            <x-treadmark.input
                                                wire:model="swaps.{{ $tireId }}.replacement_model"
                                                type="text"
                                                label="Model"
                                                placeholder="KO2"
                                            />
                                            <x-treadmark.input
                                                wire:model="swaps.{{ $tireId }}.replacement_tin"
                                                type="text"
                                                label="DOT / TIN"
                                                placeholder="DOT XXXX XXXX XX"
                                                maxlength="12"
                                            />
                                            <x-treadmark.input
                                                wire:model="swaps.{{ $tireId }}.replacement_size"
                                                type="text"
                                                label="Size"
                                                placeholder="275/70R18"
                                            />
                                            <x-treadmark.input
                                                wire:model="swaps.{{ $tireId }}.replacement_purchased_on"
                                                type="date"
                                                label="Purchase Date"
                                            />
                                        </div>
                                    </div>
                                @endif

                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-between gap-3">
                    <x-treadmark.button variant="ghost" href="{{ route('dashboard', $vehicle_id) }}" wire:navigate>
                        Cancel
                    </x-treadmark.button>
                    <x-treadmark.button wire:click="toReview" variant="primary">
                        Review swap &rarr;
                    </x-treadmark.button>
                </div>

            {{-- ── STEP 2: REVIEW ── --}}
            @else

                <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-ink-100">
                        <span class="font-display font-semibold uppercase tracking-wide text-[15px] text-ink-900">Confirm swap</span>
                        <p class="text-sm text-ink-400 mt-0.5">
                            {{ \Illuminate\Support\Carbon::parse($rotated_on)->format('M j, Y') }}
                            &middot;
                            {{ number_format($odometer) }} mi
                        </p>
                    </div>

                    <ul class="divide-y divide-ink-100">
                        @foreach ($this->activeSwaps as $tireId => $swap)
                            <li class="px-5 py-4 space-y-3" wire:key="review-{{ $tireId }}">

                                {{-- Retiring row --}}
                                <div class="flex items-center gap-3">
                                    <span class="flex-none w-6 h-6 rounded-full bg-red-100 flex items-center justify-center">
                                        <x-treadmark.icon name="trash" class="w-3.5 h-3.5 text-red-500" />
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-[14px] text-ink-900">{{ $swap['tire_label'] }}</span>
                                        <span class="text-ink-400 text-[13px]"> retiring from {{ $swap['current_position'] }}</span>
                                        @if ($swap['retiring_tread'] !== '')
                                            <span class="text-ink-400 text-[13px]"> &middot; final tread {{ $swap['retiring_tread'] }}/32"</span>
                                        @endif
                                    </div>
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-red-50 text-red-500 uppercase tracking-wide">Retired</span>
                                </div>

                                {{-- Replacement row --}}
                                <div class="flex items-center gap-3">
                                    <span class="flex-none w-6 h-6 rounded-full bg-fern-100 flex items-center justify-center">
                                        <x-treadmark.icon name="plus" class="w-3.5 h-3.5 text-fern-600" />
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <span class="font-semibold text-[14px] text-ink-900">{{ $swap['replacement_label'] }}</span>
                                        @if ($swap['replacement_brand'] || $swap['replacement_model'])
                                            <span class="text-ink-400 text-[13px]"> &middot; {{ trim($swap['replacement_brand'].' '.$swap['replacement_model']) }}</span>
                                        @endif
                                        <span class="text-ink-400 text-[13px]"> at {{ $swap['current_position'] }} &middot; {{ $swap['replacement_tread'] }}/32"</span>
                                    </div>
                                    <span class="text-[11px] font-mono px-2 py-0.5 rounded bg-fern-50 text-fern-600 uppercase tracking-wide">New</span>
                                </div>

                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="flex items-center justify-between gap-3">
                    <x-treadmark.button variant="ghost" wire:click="back">
                        &larr; Back
                    </x-treadmark.button>
                    <x-treadmark.button wire:click="save" variant="primary">
                        Confirm swap
                    </x-treadmark.button>
                </div>

            @endif

        </div>
    </div>
</div>
