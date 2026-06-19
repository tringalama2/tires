<?php

use App\Enums\TirePosition;
use App\Enums\TireStatus;
use App\Models\Tire;
use App\Models\Vehicle;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    #[Locked]
    public int $vehicle_id;

    #[Locked]
    public string $position_value;

    #[Validate('required|string|max:255')]
    public string $label = '';

    #[Validate('nullable|string|max:255')]
    public ?string $brand = null;

    #[Validate('nullable|string|max:255')]
    public ?string $model = null;

    #[Validate('nullable|string|max:255')]
    public ?string $size = null;

    #[Validate('nullable|date')]
    public ?string $purchased_on = null;

    #[Validate('nullable|string|max:12')]
    public ?string $tin = null;

    #[Validate('nullable|string|max:1000')]
    public ?string $notes = null;

    #[Validate('required|numeric|between:0,20')]
    public ?float $starting_tread = null;

    public function mount(Vehicle $vehicle, string $tirePosition): void
    {
        $this->authorize('view', $vehicle);

        try {
            $position = TirePosition::from($tirePosition);
        } catch (ValueError) {
            abort(404, 'Invalid tire position.');
        }

        $setupRotation = $vehicle->rotations()->where('is_setup', true)->first();
        if ($setupRotation && $setupRotation->placements()->where('to_position', $position->value)->exists()) {
            $this->redirectRoute('vehicles.setuptires.index', $vehicle, navigate: true);
            return;
        }

        $this->vehicle_id = $vehicle->id;
        $this->position_value = $position->value;

        $existing = $vehicle->tires()->first();
        $this->brand = $existing?->brand;
        $this->model = $existing?->model;
        $this->size = $existing?->size;
        $this->purchased_on = $existing?->purchased_on?->toDateString();
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::findOrFail($this->vehicle_id);
    }

    #[Computed]
    public function position(): TirePosition
    {
        return TirePosition::from($this->position_value);
    }

    #[Computed]
    public function duplicateLabel(): bool
    {
        $trimmed = trim($this->label);
        if ($trimmed === '') {
            return false;
        }

        return $this->vehicle()->tires()
            ->where('status', TireStatus::Active)
            ->where('label', $trimmed)
            ->exists();
    }

    public function save(): void
    {
        $this->authorize('view', $this->vehicle());
        $this->validate();

        $vehicle = $this->vehicle();
        $position = $this->position;

        $tire = $vehicle->tires()->create([
            'label' => $this->label,
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'size' => $this->size ?: null,
            'purchased_on' => $this->purchased_on ?: null,
            'tin' => $this->tin ?: null,
            'notes' => $this->notes ?: null,
            'status' => TireStatus::Active,
        ]);

        $setupRotation = $vehicle->rotations()->firstOrCreate(
            ['is_setup' => true],
            [
                'rotated_on' => $vehicle->created_at->toDateString(),
                'odometer' => $vehicle->starting_odometer,
            ]
        );

        $setupRotation->placements()->create([
            'tire_id' => $tire->id,
            'from_position' => null,
            'to_position' => $position->value,
            'tread_center' => $this->starting_tread,
        ]);

        $this->redirectRoute('vehicles.setuptires.index', $vehicle, navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.vehicles.setuptire-create');
    }
};

?>

<div>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-ink-500 mb-1">
            <a href="{{ route('vehicles.setuptires.index', $vehicle_id) }}" class="hover:text-blaze-500 transition-colors">Setup tires</a>
            <x-treadmark.icon name="caret-right" class="w-3.5 h-3.5 text-ink-300"/>
            <span class="text-ink-900 font-medium">{{ $this->position->label() }}</span>
        </div>
        <h2 class="font-display font-semibold uppercase tracking-wide text-[18px] text-ink-900">
            Add tire &mdash; {{ $this->position->label() }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl mx-auto px-4 sm:px-6">
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">

                {{-- Card header --}}
                <div class="flex items-center gap-3 px-5 py-4 border-b border-ink-100">
                    <div class="w-11 h-11 rounded-control bg-blaze-500 flex items-center justify-center flex-none">
                        <span class="font-display font-bold uppercase text-white text-[15px] tracking-wide">{{ $this->position->value }}</span>
                    </div>
                    <div>
                        <div class="font-display font-semibold uppercase tracking-wide text-[16px] text-ink-900">{{ $this->position->label() }}</div>
                        <div class="text-sm text-ink-500">Record the starting tread — we'll track wear from here.</div>
                    </div>
                </div>

                <form wire:submit="save" x-data="treadGauge()" class="divide-y divide-ink-100">

                    <div class="px-5 py-5 space-y-4">

                        <div>
                            <x-treadmark.input
                                wire:model.live="label"
                                type="text"
                                label="Label"
                                placeholder="Tire 1"
                                required
                                autofocus
                                :error="$errors->first('label')"
                            />
                            @if ($this->duplicateLabel)
                                <p class="mt-1.5 text-[12px] text-[#8A6000]">'{{ trim($label) }}' is already used by an active tire.</p>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-treadmark.input wire:model="brand" type="text" label="Brand" placeholder="BF Goodrich" :error="$errors->first('brand')" optional />
                            <x-treadmark.input wire:model="model" type="text" label="Model" placeholder="KO2" :error="$errors->first('model')" optional />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <x-treadmark.input wire:model="size" type="text" label="Size" placeholder="275/70R18" :error="$errors->first('size')" optional />
                            <x-treadmark.input wire:model="purchased_on" type="date" label="Purchase date" :error="$errors->first('purchased_on')" optional />
                        </div>

                        <x-treadmark.input wire:model="tin" type="text" label="DOT / TIN" placeholder="DOT XXXX XXXX XX" :error="$errors->first('tin')" optional mono />
                        <x-treadmark.input wire:model="notes" type="text" label="Notes" :error="$errors->first('notes')" optional />

                    </div>

                    {{-- Tread section --}}
                    <div class="px-5 py-5 space-y-3">
                        <div class="font-sans font-semibold text-[13px]">Starting tread depth<span class="text-blaze-500 ml-0.5">*</span></div>

                        <div class="flex items-start gap-4">
                            <div class="flex-1">
                                <x-treadmark.input
                                    wire:model="starting_tread"
                                    type="number"
                                    min="1"
                                    max="16"
                                    step="0.5"
                                    suffix='/32"'
                                    mono
                                    required
                                    :error="$errors->first('starting_tread')"
                                    x-model.number="tread"
                                    x-on:input="tread = parseFloat($event.target.value) || 0"
                                />
                            </div>

                            <div class="flex-none pt-1.5">
                                <div class="bg-ink-50 border border-ink-100 rounded-control px-4 py-3 min-w-[120px]">
                                    <div class="font-mono text-[10px] uppercase tracking-caps text-ink-400 mb-2">Preview</div>
                                    <div class="tread-gauge-track"
                                         x-bind:style="'--gauge-pct:' + Math.min(100, (tread/16)*100) + '%;--gauge-color:' + gaugeColor">
                                        <div class="tread-gauge-fill"></div>
                                        <div class="tread-gauge-limit"></div>
                                    </div>
                                    <div class="font-mono font-semibold text-[18px] mt-2 leading-none"
                                         x-text="tread > 0 ? tread + ' /32″' : '—'"
                                         x-bind:style="'color:' + gaugeColor">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-between gap-3 px-5 py-4 bg-ink-50">
                        <x-treadmark.button variant="ghost" href="{{ route('vehicles.setuptires.index', $vehicle_id) }}">Cancel</x-treadmark.button>
                        <x-treadmark.button type="submit" variant="primary">Save tire</x-treadmark.button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function treadGauge() {
        return {
            tread: 0,
            get gaugeColor() {
                if (this.tread <= 2) return 'var(--tread-worn)';
                if (this.tread < 5) return 'var(--tread-low)';
                if (this.tread < 8) return 'var(--tread-fair)';
                return 'var(--tread-good)';
            },
        };
    }
</script>
