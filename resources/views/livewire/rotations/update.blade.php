<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Models\Rotation;
use App\Models\Vehicle;
use App\Services\RotationService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    public string|int|null $vehicle_id = null;

    protected Vehicle $vehicle;

    /** Placements from session, keyed by from_position value. */
    public array $placements = [];

    /** to_position assignments keyed by from_position value. */
    public array $toPositions = [];

    public ?int $rotationId = null;
    public bool $isEdit = false;
    public bool $isLatestRotation = true;
    public bool $confirmEdit = false;

    /** Toggle between drag and table fallback views. */
    public bool $tableMode = false;

    public ?string $validationError = null;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (session()->missing('rotation.odometer')) {
            $params = isset($this->vehicle_id) ? ['vehicle_id' => $this->vehicle_id] : [];
            $this->redirect(route('rotations.prepare', $params), navigate: true);
            return;
        }

        if (isset($this->vehicle_id)) {
            $id = is_string($this->vehicle_id) ? hashid_decode($this->vehicle_id) : $this->vehicle_id;
            $this->vehicle = Vehicle::findOrFail($id);
            $this->authorize('view', $this->vehicle);
            $selectVehicle($this->vehicle);
        } else {
            $this->vehicle = session('vehicle');
        }

        $this->placements = session('rotation.placements', []);
        $this->rotationId = session('rotation.rotation_id');
        $this->isEdit = $this->rotationId !== null;

        if ($this->isEdit) {
            $maxOdometer = $this->vehicle->rotations()
                ->where('is_setup', false)
                ->where('id', '!=', $this->rotationId)
                ->max('odometer');

            $latestId = $this->vehicle->rotations()
                ->where('is_setup', false)
                ->orderByDesc('odometer')
                ->value('id');

            $this->isLatestRotation = $latestId === $this->rotationId;

            // Pre-fill to_positions from existing data
            foreach ($this->placements as $pos => $p) {
                $this->toPositions[$pos] = $p['to_position'] ?? $pos;
            }
        } else {
            // Default to_positions = same as from_positions (user will drag to change)
            foreach ($this->placements as $pos => $p) {
                $this->toPositions[$pos] = $pos;
            }
        }
    }

    public function save(RotationService $rotationService): void
    {
        if ($this->isEdit && ! $this->isLatestRotation && ! $this->confirmEdit) {
            $this->confirmEdit = true;
            return;
        }

        $this->validationError = null;

        $fromPositions = array_keys($this->placements);
        $toPositions = array_values($this->toPositions);

        if (! $rotationService->validatePermutation($fromPositions, $toPositions)) {
            $this->validationError = 'Each tire must be assigned exactly one position, and all positions must be filled.';
            return;
        }

        $placements = [];
        foreach ($this->placements as $pos => $p) {
            $placements[] = [
                'tire_id' => $p['tire_id'],
                'from_position' => $pos,
                'to_position' => $this->toPositions[$pos],
                'tread_center' => $p['tread_center'],
                'tread_inner' => $p['tread_inner'] ?? null,
                'tread_outer' => $p['tread_outer'] ?? null,
                'note' => $p['note'] ?? null,
                'tire_flags' => $p['tire_flags'] ?? [],
                'is_feathering' => $p['is_feathering'] ?? false,
                'is_cupped' => $p['is_cupped'] ?? false,
            ];
        }

        try {
            $rotationService->save([
                'rotated_on' => session('rotation.rotated_on'),
                'odometer' => session('rotation.odometer'),
                'note' => session('rotation.note'),
                'rotation_id' => $this->rotationId,
                'placements' => $placements,
            ], $this->vehicle);
        } catch (ValidationException $e) {
            $this->validationError = collect($e->errors())->flatten()->first();
            return;
        }

        session()->forget(['rotation.rotated_on', 'rotation.odometer', 'rotation.note', 'rotation.placements', 'rotation.rotation_id']);

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function cancelConfirm(): void
    {
        $this->confirmEdit = false;
    }

    public function toggleMode(): void
    {
        $this->tableMode = ! $this->tableMode;
    }

    /** Called from JS drag-and-drop to update a tire's to_position. */
    public function assignPosition(string $fromPosition, string $toPosition): void
    {
        if (isset($this->placements[$fromPosition])) {
            // If another tire already has this toPosition, swap them
            foreach ($this->toPositions as $fp => $tp) {
                if ($tp === $toPosition && $fp !== $fromPosition) {
                    $this->toPositions[$fp] = $this->toPositions[$fromPosition];
                    break;
                }
            }
            $this->toPositions[$fromPosition] = $toPosition;
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.rotations.update');
    }
};

?>

<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $isEdit ? __('Edit Rotation — Step 2 of 2') : __('New Rotation — Step 2 of 2') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Edit warning --}}
                    @if ($isEdit && ! $isLatestRotation)
                        <div class="mb-4 p-3 bg-amber-50 border border-amber-300 rounded text-amber-800 text-sm">
                            <strong>Warning:</strong> You are editing a past rotation. Saving will recalculate wear rates for all subsequent rotations.
                        </div>
                    @endif

                    {{-- Confirm edit dialog --}}
                    @if ($confirmEdit)
                        <div class="mb-4 p-4 bg-amber-50 border border-amber-400 rounded">
                            <p class="text-amber-800 font-medium mb-3">Editing this rotation will recalculate all wear rates for subsequent rotations. Continue?</p>
                            <div class="flex gap-3">
                                <x-treadmark.button wire:click="save" size="sm">Yes, save changes</x-treadmark.button>
                                <x-treadmark.button wire:click="cancelConfirm" variant="secondary" size="sm">Cancel</x-treadmark.button>
                            </div>
                        </div>
                    @endif

                    {{-- Validation error --}}
                    @if ($validationError)
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                            {{ $validationError }}
                        </div>
                    @endif

                    <div class="flex justify-between mb-4">
                        <div class="text-sm text-gray-500">
                            Drag each tire to its new position, or
                            <button type="button" wire:click="toggleMode" class="text-steel-600 underline hover:text-steel-700 text-sm">switch to {{ $tableMode ? 'drag' : 'table' }} view</button>.
                        </div>
                        <x-treadmark.button wire:click="save">
                            {{ __('Complete Rotation') }}
                        </x-treadmark.button>
                    </div>

                    @if ($tableMode)
                        {{-- Table fallback --}}
                        <table class="w-full border-collapse text-sm">
                            <thead>
                                <tr class="bg-gray-100 text-left">
                                    <th class="p-3 border border-gray-300">Tire</th>
                                    <th class="p-3 border border-gray-300">From</th>
                                    <th class="p-3 border border-gray-300">To</th>
                                    <th class="p-3 border border-gray-300">Tread (C/I/O)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($placements as $fromPos => $p)
                                    <tr class="odd:bg-white even:bg-gray-50">
                                        <td class="p-3 border border-gray-300 font-bold text-ink-900">{{ $p['tire_label'] ?? $fromPos }}</td>
                                        <td class="p-3 border border-gray-300">{{ TirePosition::from($fromPos)->label() }}</td>
                                        <td class="p-3 border border-gray-300">
                                            <select wire:model.live="toPositions.{{ $fromPos }}" class="border-gray-300 rounded text-sm w-full">
                                                @foreach (TirePosition::order() as $pos)
                                                    <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="p-3 border border-gray-300 text-gray-600">
                                            {{ $p['tread_center'] }}/32"
                                            @if ($p['tread_inner'] ?? null) · i:{{ $p['tread_inner'] }} @endif
                                            @if ($p['tread_outer'] ?? null) · o:{{ $p['tread_outer'] }} @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        {{-- Drag-and-drop layout --}}
                        <div
                            id="drag-root"
                            x-data="rotationDrag(@js($placements), @js($toPositions))"
                            class="grid grid-cols-4 grid-rows-3 gap-3"
                        >
                            {{-- FL --}}
                            <div
                                x-bind:class="dropZoneClass('FL')"
                                x-on:dragover.prevent="onDragOver('FL')"
                                x-on:dragleave="onDragLeave('FL')"
                                x-on:drop.prevent="onDrop('FL')"
                                x-on:touchmove.prevent="onTouchMove($event)"
                                x-on:touchend.prevent="onTouchEnd('FL')"
                                data-position="FL"
                                class="justify-self-center border-2 rounded-lg p-2 w-44 min-h-40 transition-colors"
                            >
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 border-b pb-1">Front Left</div>
                                <template x-for="(p, fromPos) in placements" :key="fromPos">
                                    <div
                                        x-show="currentPositions[fromPos] === 'FL'"
                                        draggable="true"
                                        x-on:dragstart="onDragStart(fromPos, $event)"
                                        x-on:touchstart.prevent="onTouchStart(fromPos, $event)"
                                        class="cursor-grab select-none flex flex-col items-center mt-1"
                                    >
                                        <x-phosphor-tire-duotone class="w-12 h-12 text-ink-600" />
                                        <span class="text-xs font-bold text-ink-900" x-text="p.tire_label"></span>
                                        <span class="text-xs text-gray-500">from <span x-text="p.from_position_label"></span></span>
                                        <span class="text-xs text-gray-700" x-text="p.tread_center + '/32&quot;'"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Car diagram --}}
                            <div class="row-span-2 justify-self-center self-center">
                                <x-img.car-top-view class="w-56" />
                            </div>

                            {{-- FR --}}
                            <div
                                x-bind:class="dropZoneClass('FR')"
                                x-on:dragover.prevent="onDragOver('FR')"
                                x-on:dragleave="onDragLeave('FR')"
                                x-on:drop.prevent="onDrop('FR')"
                                x-on:touchmove.prevent="onTouchMove($event)"
                                x-on:touchend.prevent="onTouchEnd('FR')"
                                data-position="FR"
                                class="justify-self-center border-2 rounded-lg p-2 w-44 min-h-40 transition-colors"
                            >
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 border-b pb-1">Front Right</div>
                                <template x-for="(p, fromPos) in placements" :key="fromPos">
                                    <div
                                        x-show="currentPositions[fromPos] === 'FR'"
                                        draggable="true"
                                        x-on:dragstart="onDragStart(fromPos, $event)"
                                        x-on:touchstart.prevent="onTouchStart(fromPos, $event)"
                                        class="cursor-grab select-none flex flex-col items-center mt-1"
                                    >
                                        <x-phosphor-tire-duotone class="w-12 h-12 text-ink-600" />
                                        <span class="text-xs font-bold text-ink-900" x-text="p.tire_label"></span>
                                        <span class="text-xs text-gray-500">from <span x-text="p.from_position_label"></span></span>
                                        <span class="text-xs text-gray-700" x-text="p.tread_center + '/32&quot;'"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Garage --}}
                            <div
                                x-bind:class="dropZoneClass('GARAGE')"
                                x-on:dragover.prevent="onDragOver('GARAGE')"
                                x-on:dragleave="onDragLeave('GARAGE')"
                                x-on:drop.prevent="onDrop('GARAGE')"
                                x-on:touchmove.prevent="onTouchMove($event)"
                                x-on:touchend.prevent="onTouchEnd('GARAGE')"
                                data-position="GARAGE"
                                class="row-span-3 justify-self-center border-2 border-dashed rounded-lg p-3 w-44 min-h-48 transition-colors"
                            >
                                <div class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2 text-center">Holding</div>
                                <template x-for="(p, fromPos) in placements" :key="fromPos">
                                    <div
                                        x-show="currentPositions[fromPos] === 'GARAGE'"
                                        draggable="true"
                                        x-on:dragstart="onDragStart(fromPos, $event)"
                                        x-on:touchstart.prevent="onTouchStart(fromPos, $event)"
                                        class="cursor-grab select-none flex flex-col items-center mb-2"
                                    >
                                        <x-phosphor-tire-duotone class="w-10 h-10 text-gray-500" />
                                        <span class="text-xs font-bold text-gray-700" x-text="p.tire_label"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- RL --}}
                            <div
                                x-bind:class="dropZoneClass('RL')"
                                x-on:dragover.prevent="onDragOver('RL')"
                                x-on:dragleave="onDragLeave('RL')"
                                x-on:drop.prevent="onDrop('RL')"
                                x-on:touchmove.prevent="onTouchMove($event)"
                                x-on:touchend.prevent="onTouchEnd('RL')"
                                data-position="RL"
                                class="justify-self-center border-2 rounded-lg p-2 w-44 min-h-40 transition-colors"
                            >
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 border-b pb-1">Rear Left</div>
                                <template x-for="(p, fromPos) in placements" :key="fromPos">
                                    <div
                                        x-show="currentPositions[fromPos] === 'RL'"
                                        draggable="true"
                                        x-on:dragstart="onDragStart(fromPos, $event)"
                                        x-on:touchstart.prevent="onTouchStart(fromPos, $event)"
                                        class="cursor-grab select-none flex flex-col items-center mt-1"
                                    >
                                        <x-phosphor-tire-duotone class="w-12 h-12 text-cyan-600" />
                                        <span class="text-xs font-bold text-cyan-700" x-text="p.tire_label"></span>
                                        <span class="text-xs text-gray-500">from <span x-text="p.from_position_label"></span></span>
                                        <span class="text-xs text-gray-700" x-text="p.tread_center + '/32&quot;'"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- RR --}}
                            <div
                                x-bind:class="dropZoneClass('RR')"
                                x-on:dragover.prevent="onDragOver('RR')"
                                x-on:dragleave="onDragLeave('RR')"
                                x-on:drop.prevent="onDrop('RR')"
                                x-on:touchmove.prevent="onTouchMove($event)"
                                x-on:touchend.prevent="onTouchEnd('RR')"
                                data-position="RR"
                                class="justify-self-center border-2 rounded-lg p-2 w-44 min-h-40 transition-colors"
                            >
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 border-b pb-1">Rear Right</div>
                                <template x-for="(p, fromPos) in placements" :key="fromPos">
                                    <div
                                        x-show="currentPositions[fromPos] === 'RR'"
                                        draggable="true"
                                        x-on:dragstart="onDragStart(fromPos, $event)"
                                        x-on:touchstart.prevent="onTouchStart(fromPos, $event)"
                                        class="cursor-grab select-none flex flex-col items-center mt-1"
                                    >
                                        <x-phosphor-tire-duotone class="w-12 h-12 text-green-600" />
                                        <span class="text-xs font-bold text-green-700" x-text="p.tire_label"></span>
                                        <span class="text-xs text-gray-500">from <span x-text="p.from_position_label"></span></span>
                                        <span class="text-xs text-gray-700" x-text="p.tread_center + '/32&quot;'"></span>
                                    </div>
                                </template>
                            </div>

                            {{-- Spare --}}
                            <div
                                x-bind:class="dropZoneClass('SP')"
                                x-on:dragover.prevent="onDragOver('SP')"
                                x-on:dragleave="onDragLeave('SP')"
                                x-on:drop.prevent="onDrop('SP')"
                                x-on:touchmove.prevent="onTouchMove($event)"
                                x-on:touchend.prevent="onTouchEnd('SP')"
                                data-position="SP"
                                class="col-start-2 justify-self-center border-2 rounded-lg p-2 w-44 min-h-40 transition-colors"
                            >
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1 border-b pb-1">Spare</div>
                                <template x-for="(p, fromPos) in placements" :key="fromPos">
                                    <div
                                        x-show="currentPositions[fromPos] === 'SP'"
                                        draggable="true"
                                        x-on:dragstart="onDragStart(fromPos, $event)"
                                        x-on:touchstart.prevent="onTouchStart(fromPos, $event)"
                                        class="cursor-grab select-none flex flex-col items-center mt-1"
                                    >
                                        <x-phosphor-tire-duotone class="w-12 h-12 text-lime-600" />
                                        <span class="text-xs font-bold text-lime-700" x-text="p.tire_label"></span>
                                        <span class="text-xs text-gray-500">from <span x-text="p.from_position_label"></span></span>
                                        <span class="text-xs text-gray-700" x-text="p.tread_center + '/32&quot;'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end mt-6">
                        <x-treadmark.button wire:click="save">
                            {{ __('Complete Rotation') }}
                        </x-treadmark.button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
Alpine.data('rotationDrag', (placements, initialPositions) => ({
    placements: placements,
    currentPositions: { ...initialPositions },
    dragging: null,
    touchGhost: null,
    hovering: null,

    dropZoneClass(pos) {
        const base = pos === 'GARAGE'
            ? 'bg-gray-50 border-gray-300'
            : 'bg-gray-100 border-gray-400';
        const highlight = 'bg-blaze-50 border-blaze-400';
        return this.hovering === pos ? highlight : base;
    },

    onDragStart(fromPos, event) {
        this.dragging = fromPos;
        event.dataTransfer.effectAllowed = 'move';
    },

    onDragOver(pos) {
        this.hovering = pos;
    },

    onDragLeave(pos) {
        if (this.hovering === pos) this.hovering = null;
    },

    onDrop(toPos) {
        this.hovering = null;
        if (!this.dragging) return;
        this.moveTire(this.dragging, toPos);
        this.dragging = null;
    },

    onTouchStart(fromPos, event) {
        this.dragging = fromPos;
        const touch = event.touches[0];

        // Create a ghost element for visual feedback
        const el = event.currentTarget.cloneNode(true);
        el.style.cssText = `position:fixed;opacity:0.7;pointer-events:none;z-index:9999;transform:translate(-50%,-50%);top:${touch.clientY}px;left:${touch.clientX}px`;
        document.body.appendChild(el);
        this.touchGhost = el;
    },

    onTouchMove(event) {
        if (!this.touchGhost) return;
        const touch = event.touches[0];
        this.touchGhost.style.top = touch.clientY + 'px';
        this.touchGhost.style.left = touch.clientX + 'px';

        // Highlight drop zone under finger
        const el = document.elementFromPoint(touch.clientX, touch.clientY);
        const zone = el?.closest('[data-position]');
        this.hovering = zone ? zone.dataset.position : null;
    },

    onTouchEnd(defaultPos) {
        if (this.touchGhost) {
            this.touchGhost.remove();
            this.touchGhost = null;
        }
        const toPos = this.hovering || defaultPos;
        if (this.dragging) {
            this.moveTire(this.dragging, toPos);
        }
        this.dragging = null;
        this.hovering = null;
    },

    moveTire(fromPos, toPos) {
        if (toPos === 'GARAGE') {
            this.currentPositions[fromPos] = 'GARAGE';
            $wire.assignPosition(fromPos, fromPos); // keeps from_pos, signals garage
            return;
        }

        // If another tire is at toPos, swap it to fromPos's current slot
        for (const [fp, cp] of Object.entries(this.currentPositions)) {
            if (cp === toPos && fp !== fromPos) {
                this.currentPositions[fp] = this.currentPositions[fromPos];
                $wire.assignPosition(fp, this.currentPositions[fromPos] === 'GARAGE' ? fp : this.currentPositions[fromPos]);
                break;
            }
        }

        this.currentPositions[fromPos] = toPos;
        $wire.assignPosition(fromPos, toPos);
    },
}));
</script>
@endscript
