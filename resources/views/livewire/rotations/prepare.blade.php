<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Services\RotationService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')]
class extends Component {

    public string|int|null $vehicle_id = null;
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
            $id = is_string($this->vehicle_id) ? hashid_decode($this->vehicle_id) : $this->vehicle_id;
            $this->vehicle = Vehicle::findOrFail($id);
            $this->authorize('view', $this->vehicle);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->vehicle_id = $this->vehicle->id;

        if ($this->edit_rotation_id) {
            $this->isEdit = true;
            $rotationId = hashid_decode($this->edit_rotation_id) ?? $this->edit_rotation_id;
            $this->loadExistingRotation($rotationId);
        } else {
            $this->rotated_on = Carbon::today()->toDateString();
            $this->stubs = $rotationService->startNext($this->vehicle);
            $this->initTreads();
        }

        $this->restoreFromSession();
    }

    private function restoreFromSession(): void
    {
        if (session()->missing('rotation.odometer')) {
            return;
        }

        // For edits, only restore if the session belongs to the same rotation.
        if ($this->isEdit) {
            $sessionRotationId = session('rotation.rotation_id');
            $currentRotationId = $this->edit_rotation_id ? (hashid_decode($this->edit_rotation_id) ?? null) : null;
            if ($sessionRotationId !== $currentRotationId) {
                return;
            }
        } elseif (session('rotation.rotation_id') !== null) {
            // Session is from a previous edit flow, not a new rotation.
            return;
        }

        $this->rotated_on = session('rotation.rotated_on', $this->rotated_on);
        $this->odometer = session('rotation.odometer');
        $this->rotation_note = session('rotation.note');

        foreach (session('rotation.placements', []) as $pos => $placement) {
            if (! isset($this->treads[$pos])) {
                continue;
            }
            $this->treads[$pos]['tread_center'] = $placement['tread_center'];
            $this->treads[$pos]['tread_inner'] = $placement['tread_inner'];
            $this->treads[$pos]['tread_outer'] = $placement['tread_outer'];
            $this->treads[$pos]['note'] = $placement['note'];
            $this->tireFlags[$pos] = $placement['tire_flags'];
            $this->wearFlags[$pos]['is_feathering'] = $placement['is_feathering'];
        }
    }

    #[Computed]
    public function lastOdometer(): ?int
    {
        return $this->vehicle()->rotations()->max('odometer');
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::findOrFail($this->vehicle_id);
    }

    private function loadExistingRotation(string|int $rotationId): void
    {
        $rotation = $this->vehicle()->rotations()->with('placements.tire')->findOrFail($rotationId);
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
        $order = array_flip(array_map(fn($p) => $p->value, TirePosition::order()));
        usort($stubs, fn($a, $b) => $order[$a['from_position']->value] <=> $order[$b['from_position']->value]);
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
            'rotation.rotation_id' => $this->edit_rotation_id ? hashid_decode($this->edit_rotation_id) : null,
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
        <h2 class="font-semibold text-xl text-ink-800 leading-tight">
            {{ $isEdit ? __('Edit Rotation') : __('New Rotation — Step 1 of 2') }}
        </h2>
    </x-slot>

    {{-- extra bottom padding so the sticky bar never covers content --}}
    <div class="py-6 pb-28 sm:py-12 sm:pb-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 text-ink-900">

                    <div class="flex justify-between mb-5">
                        <div class="font-display font-semibold uppercase text-2xl tracking-wider text-ink-900">{{ $this->vehicle()->nickname }}</div>
                        @if ($isEdit)
                            <span class="text-sm text-amber-600 font-medium self-center">Editing existing rotation</span>
                        @endif
                    </div>

                    @if ($errors->any())
                        <div class="mb-4 p-3 bg-rust-100 border border-rust-600/30 rounded-control text-rust-600 text-sm">
                            <ul class="list-disc list-inside space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form wire:submit="next" id="prepare-form">
                        {{-- Date + Odometer --}}
                        <div class="flex flex-col sm:flex-row gap-4 mb-5">
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
                                    :placeholder="$this->lastOdometer ? number_format($this->lastOdometer) : ''"
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
                            <label for="rotation_note" class="font-sans font-semibold text-[13px] text-ink-900">
                                Rotation Note
                                <span class="text-ink-300 font-normal ml-1.5 text-[12px]">optional</span>
                            </label>
                            <textarea wire:model="rotation_note" id="rotation_note" name="rotation_note"
                                      class="mt-1.5 block w-full ring-1 ring-ink-200 rounded-control bg-white text-[15px] text-ink-900 px-3 py-2.5 placeholder:text-ink-300 focus:outline-none focus:ring-4 focus:ring-blaze-500/40"
                                      rows="2" placeholder="e.g. adjusted tire pressure to 32 PSI"></textarea>
                        </div>

                        {{-- Per-position tread cards — always 2 columns (left/right side by side) --}}
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 mb-6">
                            @foreach ($stubs as $stub)
                                @php
                                    $pos = $stub['from_position']->value;
                                    $tire = $stub['tire'];
                                    $lastTread = $stub['last_tread_center'];
                                    $hasFlags = collect($tireFlags[$pos] ?? [])->contains(true)
                                        || ($wearFlags[$pos]['is_feathering'] ?? false);
                                    $hasNote = !empty($treads[$pos]['note']);
                                    $startOpen = $hasFlags || $hasNote;
                                @endphp
                                <div class="border border-ink-200 rounded-card p-3 sm:p-4 bg-white flex flex-col"
                                     x-data="{ open: {{ $startOpen ? 'true' : 'false' }} }">

                                    {{-- Card header: position chip + tire label --}}
                                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-ink-100">
                                        <x-treadmark.position-tag :position="$pos" size="sm" />
                                        <span class="font-mono font-bold text-sm text-ink-800">{{ $tire->label }}</span>
                                    </div>

                                    {{-- Last reading badge --}}
                                    @if ($lastTread !== null)
                                        <p class="text-[11px] text-ink-400 mb-2 font-mono">Last: {{ $lastTread }}/32"</p>
                                    @endif

                                    {{-- Center tread (required) — visually prominent --}}
                                    <div class="mb-3 bg-ink-50 rounded-control p-2.5">
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

                                    {{-- Inner + Outer (optional) — min-w-0 fixes overflow --}}
                                    <div class="flex gap-2 mb-3">
                                        <div class="flex-1 min-w-0">
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
                                        <div class="flex-1 min-w-0">
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

                                    {{-- Collapsible: Note + Condition flags --}}
                                    <div class="mt-auto pt-2 border-t border-ink-100">
                                        <button type="button"
                                                x-on:click="open = !open"
                                                class="w-full flex items-center justify-between py-1 text-[11px] font-semibold uppercase tracking-wide text-ink-400 hover:text-ink-600 transition-colors">
                                            <span>Note &amp; Condition</span>
                                            <svg x-bind:class="open ? 'rotate-180' : ''"
                                                 class="w-3.5 h-3.5 transition-transform duration-150"
                                                 viewBox="0 0 16 16" fill="currentColor">
                                                <path d="M8 10.94 2.53 5.47l1.06-1.06L8 8.81l4.41-4.4 1.06 1.06L8 10.94Z"/>
                                            </svg>
                                        </button>

                                        <div x-show="open"
                                             x-transition:enter="transition ease-out duration-150"
                                             x-transition:enter-start="opacity-0 -translate-y-1"
                                             x-transition:enter-end="opacity-100 translate-y-0"
                                             x-transition:leave="transition ease-in duration-100"
                                             x-transition:leave-start="opacity-100 translate-y-0"
                                             x-transition:leave-end="opacity-0 -translate-y-1"
                                             class="pt-2 space-y-3">

                                            {{-- Note --}}
                                            <div>
                                                <label for="note_{{ $pos }}" class="font-sans font-semibold text-[13px]">
                                                    Note <span class="text-ink-300 font-normal ml-1.5 text-[12px]">optional</span>
                                                </label>
                                                <textarea
                                                    wire:model="treads.{{ $pos }}.note"
                                                    id="note_{{ $pos }}"
                                                    class="mt-1.5 block w-full ring-1 ring-ink-200 rounded-control bg-white text-[13px] text-ink-900 px-3 py-2 placeholder:text-ink-300 focus:outline-none focus:ring-4 focus:ring-blaze-500/40"
                                                    rows="2"></textarea>
                                            </div>

                                            {{-- Condition & wear flags --}}
                                            <div class="space-y-1.5">
                                                <p class="text-[11px] font-semibold text-ink-400 uppercase tracking-wide">Condition</p>
                                                @foreach ([
                                                    'has_cracking' => 'Cracking / dry rot',
                                                    'has_bulge' => 'Sidewall bulge',
                                                    'has_cupping' => 'Cupping',
                                                    'has_puncture_repair' => 'Plug / patch',
                                                ] as $flag => $label)
                                                    <label class="flex items-center gap-2 text-xs text-ink-700 cursor-pointer select-none">
                                                        <input type="checkbox" wire:model="tireFlags.{{ $pos }}.{{ $flag }}"
                                                               class="rounded border-ink-300 text-blaze-500 focus:ring-blaze-500/40 min-h-[20px] min-w-[20px]">
                                                        {{ $label }}
                                                    </label>
                                                @endforeach
                                                <label class="flex items-center gap-2 text-xs text-ink-700 cursor-pointer select-none">
                                                    <input type="checkbox" wire:model="wearFlags.{{ $pos }}.is_feathering"
                                                           class="rounded border-ink-300 text-blaze-500 focus:ring-blaze-500/40 min-h-[20px] min-w-[20px]">
                                                    Feathering / sawtooth
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Sticky submit bar — sits above the mobile nav safely --}}
    <div class="fixed bottom-0 inset-x-0 z-10 bg-white/95 backdrop-blur-sm border-t border-ink-200 px-4 py-3 flex justify-end sm:px-6">
        <x-treadmark.button form="prepare-form" type="submit">
            {{ __('Next: Assign Positions →') }}
        </x-treadmark.button>
    </div>
</div>
