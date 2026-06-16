<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Services\RotationService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?int $vehicle_id;
    public ?string $edit_rotation_id = null;

    protected Vehicle $vehicle;

    #[Validate('required|date')]
    public string $rotated_on = '';

    #[Validate('required|integer|min:1|max:16777215')]
    public ?int $odometer = null;

    #[Validate('nullable|string|max:500')]
    public ?string $rotation_note = null;

    /** Tread readings keyed by TirePosition value (FL, FR, RL, RR, SP). */
    public array $treads = [];

    /** Tire condition flags keyed by TirePosition value. */
    public array $tireFlags = [];

    /** Placement wear flags keyed by TirePosition value. */
    public array $wearFlags = [];

    /** Stubs from RotationService::startNext(), shape: [{tire, from_position, last_tread_center}] */
    public array $stubs = [];

    public bool $isEdit = false;

    public function mount(SelectVehicle $selectVehicle, RotationService $rotationService): void
    {
        if (isset($this->vehicle_id)) {
            $this->vehicle = Vehicle::findOrFail($this->vehicle_id);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->vehicle_id = $this->vehicle->id;

        if ($this->edit_rotation_id) {
            $this->isEdit = true;
            $this->loadExistingRotation($this->edit_rotation_id);
        } else {
            $this->rotated_on = Carbon::today()->toDateString();
            $this->stubs = $rotationService->startNext($this->vehicle);
            $this->initTreads();
        }
    }

    private function loadExistingRotation(string $rotationId): void
    {
        $rotation = Rotation::with('placements.tire')->findOrFail($rotationId);
        $this->rotated_on = $rotation->rotated_on->toDateString();
        $this->odometer = $rotation->odometer;
        $this->rotation_note = $rotation->note;

        $stubs = [];
        foreach ($rotation->placements as $placement) {
            $stub = [
                'tire' => $placement->tire,
                'from_position' => $placement->from_position,
                'last_tread_center' => null,
            ];
            $stubs[] = $stub;

            $pos = $placement->from_position->value;
            $this->treads[$pos] = [
                'tread_center' => (float) $placement->tread_center,
                'tread_inner' => $placement->tread_inner !== null ? (float) $placement->tread_inner : null,
                'tread_outer' => $placement->tread_outer !== null ? (float) $placement->tread_outer : null,
                'note' => $placement->note,
                'to_position' => $placement->to_position->value,
            ];
            $this->tireFlags[$pos] = [
                'has_cracking' => (bool) $placement->tire->has_cracking,
                'has_bulge' => (bool) $placement->tire->has_bulge,
                'has_cupping' => (bool) $placement->tire->has_cupping,
                'has_puncture_repair' => (bool) $placement->tire->has_puncture_repair,
            ];
            $this->wearFlags[$pos] = [
                'is_feathering' => (bool) $placement->is_feathering,
            ];
        }

        // Sort by canonical position order
        $order = array_flip(array_map(fn ($p) => $p->value, TirePosition::order()));
        usort($stubs, fn ($a, $b) => $order[$a['from_position']->value] <=> $order[$b['from_position']->value]);
        $this->stubs = $stubs;
    }

    private function initTreads(): void
    {
        foreach ($this->stubs as $stub) {
            $pos = $stub['from_position']->value;
            $this->treads[$pos] = [
                'tread_center' => null,
                'tread_inner' => null,
                'tread_outer' => null,
                'note' => null,
                'to_position' => null,
            ];
            $this->tireFlags[$pos] = [
                'has_cracking' => (bool) $stub['tire']->has_cracking,
                'has_bulge' => (bool) $stub['tire']->has_bulge,
                'has_cupping' => (bool) $stub['tire']->has_cupping,
                'has_puncture_repair' => (bool) $stub['tire']->has_puncture_repair,
            ];
            $this->wearFlags[$pos] = ['is_feathering' => false];
        }
    }

    public function rules(): array
    {
        $rules = [
            'rotated_on' => 'required|date',
            'odometer' => 'required|integer|min:1|max:16777215',
            'rotation_note' => 'nullable|string|max:500',
        ];

        foreach ($this->stubs as $stub) {
            $pos = $stub['from_position']->value;
            $rules["treads.{$pos}.tread_center"] = 'required|numeric|min:0|max:20';
            $rules["treads.{$pos}.tread_inner"] = 'nullable|numeric|min:0|max:20';
            $rules["treads.{$pos}.tread_outer"] = 'nullable|numeric|min:0|max:20';
            $rules["treads.{$pos}.note"] = 'nullable|string|max:500';
            $rules["tireFlags.{$pos}.has_cracking"] = 'boolean';
            $rules["tireFlags.{$pos}.has_bulge"] = 'boolean';
            $rules["tireFlags.{$pos}.has_cupping"] = 'boolean';
            $rules["tireFlags.{$pos}.has_puncture_repair"] = 'boolean';
            $rules["wearFlags.{$pos}.is_feathering"] = 'boolean';
        }

        return $rules;
    }

    public function next(): void
    {
        $this->validate($this->rules());

        $placements = [];
        foreach ($this->stubs as $stub) {
            $pos = $stub['from_position']->value;
            $placements[$pos] = [
                'tire_id' => $stub['tire']->id,
                'tire_label' => $stub['tire']->label,
                'from_position' => $pos,
                'from_position_label' => $stub['from_position']->label(),
                'tread_center' => $this->treads[$pos]['tread_center'],
                'tread_inner' => $this->treads[$pos]['tread_inner'] ?: null,
                'tread_outer' => $this->treads[$pos]['tread_outer'] ?: null,
                'note' => $this->treads[$pos]['note'] ?: null,
                'to_position' => $this->isEdit ? ($this->treads[$pos]['to_position'] ?? null) : null,
                'tire_flags' => [
                    'has_cracking' => (bool) ($this->tireFlags[$pos]['has_cracking'] ?? false),
                    'has_bulge' => (bool) ($this->tireFlags[$pos]['has_bulge'] ?? false),
                    'has_cupping' => (bool) ($this->tireFlags[$pos]['has_cupping'] ?? false),
                    'has_puncture_repair' => (bool) ($this->tireFlags[$pos]['has_puncture_repair'] ?? false),
                ],
                'is_feathering' => (bool) ($this->wearFlags[$pos]['is_feathering'] ?? false),
                'is_cupped' => (bool) ($this->tireFlags[$pos]['has_cupping'] ?? false),
            ];
        }

        session([
            'rotation.rotated_on' => $this->rotated_on,
            'rotation.odometer' => $this->odometer,
            'rotation.note' => $this->rotation_note ?: null,
            'rotation.placements' => $placements,
            'rotation.rotation_id' => $this->edit_rotation_id,
        ]);

        $this->redirect(route('rotations.update'), navigate: true);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.rotations.prepare');
    }
};

?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $isEdit ? __('Edit Rotation') : __('New Rotation — Step 1 of 2') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    <div class="flex justify-between mb-4">
                        <div class="font-display font-semibold uppercase text-2xl tracking-wider text-ink-900">{{ $this->vehicle->nickname }}</div>
                        @if ($isEdit)
                            <span class="text-sm text-amber-600 font-medium self-center">Editing existing rotation</span>
                        @endif
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form wire:submit="next">
                        {{-- Date + Odometer --}}
                        <div class="flex flex-col sm:flex-row gap-4 mb-6">
                            <div class="basis-1/2">
                                <x-treadmark.input
                                    wire:model="rotated_on"
                                    id="rotated_on"
                                    type="date"
                                    label="Rotation Date"
                                    name="rotated_on"
                                    required
                                    :error="$errors->first('rotated_on')"
                                />
                            </div>
                            <div class="basis-1/2">
                                <x-treadmark.input
                                    wire:model="odometer"
                                    id="odometer"
                                    type="number"
                                    label="Odometer"
                                    name="odometer"
                                    suffix="mi"
                                    mono
                                    min="1"
                                    required
                                    :error="$errors->first('odometer')"
                                />
                            </div>
                        </div>

                        {{-- Optional rotation note --}}
                        <div class="mb-6">
                            <label for="rotation_note" class="font-sans font-semibold text-[13px] text-ink-900">Rotation Note <span class="text-ink-300 font-normal ml-1.5 text-[12px]">optional</span></label>
                            <textarea wire:model="rotation_note" id="rotation_note" name="rotation_note"
                                class="mt-1.5 block w-full border border-ink-200 rounded-control bg-white text-[15px] text-ink-900 px-3 py-2.5 placeholder:text-ink-300 focus:outline-none focus:border-blaze-500 focus:ring-4 focus:ring-blaze-500/40"
                                rows="2" placeholder="e.g. adjusted tire pressure to 32 PSI"></textarea>
                        </div>

                        {{-- Per-position tread cards --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                            @foreach ($stubs as $stub)
                                @php
                                    $pos = $stub['from_position']->value;
                                    $tire = $stub['tire'];
                                    $lastTread = $stub['last_tread_center'];
                                @endphp
                                <div class="border border-gray-300 rounded-lg p-4 bg-gray-50">
                                    <div class="flex items-center justify-between mb-3 border-b border-gray-300 pb-2">
                                        <span class="font-semibold text-sm text-gray-700 uppercase tracking-wide">{{ $stub['from_position']->label() }}</span>
                                        <span class="font-bold text-ink-900">{{ $tire->label }}</span>
                                    </div>

                                    @if ($lastTread !== null)
                                        <p class="text-xs text-gray-400 mb-2">Last: {{ $lastTread }}/32"</p>
                                    @endif

                                    {{-- Center tread (required) — text-base prevents iOS zoom on focus --}}
                                    <div class="mb-3">
                                        <x-treadmark.input
                                            wire:model="treads.{{ $pos }}.tread_center"
                                            :id="'tread_center_'.$pos"
                                            type="number"
                                            label="Center"
                                            suffix='/32"'
                                            mono
                                            step="0.5" min="0" max="20"
                                            required
                                            inputmode="decimal"
                                            :error="$errors->first('treads.'.$pos.'.tread_center')"
                                        />
                                    </div>

                                    {{-- Inner + Outer (optional) --}}
                                    <div class="flex gap-2 mb-3">
                                        <div class="flex-1">
                                            <x-treadmark.input
                                                wire:model="treads.{{ $pos }}.tread_inner"
                                                :id="'tread_inner_'.$pos"
                                                type="number"
                                                label="Inner"
                                                suffix='/32"'
                                                mono
                                                step="0.5" min="0" max="20"
                                                inputmode="decimal"
                                            />
                                        </div>
                                        <div class="flex-1">
                                            <x-treadmark.input
                                                wire:model="treads.{{ $pos }}.tread_outer"
                                                :id="'tread_outer_'.$pos"
                                                type="number"
                                                label="Outer"
                                                suffix='/32"'
                                                mono
                                                step="0.5" min="0" max="20"
                                                inputmode="decimal"
                                            />
                                        </div>
                                    </div>

                                    {{-- Note --}}
                                    <div class="mb-3">
                                        <label :for="'note_'.$pos" class="font-sans font-semibold text-[13px] text-ink-900">Note <span class="text-ink-300 font-normal ml-1.5 text-[12px]">optional</span></label>
                                        <textarea
                                            wire:model="treads.{{ $pos }}.note"
                                            :id="'note_'.$pos"
                                            class="mt-1.5 block w-full border border-ink-200 rounded-control bg-white text-[13px] text-ink-900 px-3 py-2 placeholder:text-ink-300 focus:outline-none focus:border-blaze-500 focus:ring-4 focus:ring-blaze-500/40"
                                            rows="2"></textarea>
                                    </div>

                                    {{-- Condition & wear flags --}}
                                    <div class="pt-3 border-t border-gray-200 space-y-1.5">
                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Condition</p>
                                        @foreach ([
                                            'has_cracking' => 'Cracking / dry rot',
                                            'has_bulge' => 'Sidewall bulge',
                                            'has_cupping' => 'Cupping',
                                            'has_puncture_repair' => 'Plug / patch',
                                        ] as $flag => $label)
                                            <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer select-none">
                                                <input type="checkbox" wire:model="tireFlags.{{ $pos }}.{{ $flag }}"
                                                    class="rounded border-gray-300 text-blaze-600 focus:ring-blaze-500 min-h-[20px] min-w-[20px]">
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer select-none">
                                            <input type="checkbox" wire:model="wearFlags.{{ $pos }}.is_feathering"
                                                class="rounded border-gray-300 text-blaze-600 focus:ring-blaze-500 min-h-[20px] min-w-[20px]">
                                            Feathering / sawtooth
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end">
                            <x-treadmark.button type="submit" size="lg">
                                {{ __('Next: Assign Positions →') }}
                            </x-treadmark.button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
