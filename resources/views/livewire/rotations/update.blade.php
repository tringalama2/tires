<?php

use App\Actions\SelectVehicle;
use App\Enums\TirePosition;
use App\Livewire\Concerns\ResolvesActiveVehicle;
use App\Models\Rotation;
use App\Services\RotationService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {
    use ResolvesActiveVehicle;

    #[Locked]
    public string|int|null $vehicle_id = null;

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

    public int $tireCount = 5;

    public ?string $validationError = null;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (session()->missing('rotation.odometer')) {
            $params = isset($this->vehicle_id) ? ['vehicle_id' => $this->vehicle_id] : [];
            $this->redirect(route('rotations.prepare', $params), navigate: true);
            return;
        }

        $vehicle = $this->resolveVehicle($selectVehicle);

        $this->tireCount = $vehicle->tire_count;
        $this->placements = session('rotation.placements', []);
        $this->rotationId = session('rotation.rotation_id');
        $this->isEdit = $this->rotationId !== null;

        if ($this->isEdit) {
            $maxOdometer = $this->vehicle()->rotations()
                ->real()
                ->where('id', '!=', $this->rotationId)
                ->max('odometer');

            $latestId = $this->vehicle()->rotations()
                ->real()
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
            ], $this->vehicle());
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
        <h2 class="font-semibold text-xl text-ink-800 leading-tight">
            {{ $isEdit ? __('Edit Rotation — Step 2 of 2') : __('New Rotation — Step 2 of 2') }}
        </h2>
    </x-slot>

    <div class="py-6 pb-28 sm:py-12 sm:pb-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-4 sm:p-6 text-ink-900">

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
                        <div class="mb-4 p-3 bg-rust-100 border border-rust-600/30 rounded-control text-rust-600 text-sm">
                            {{ $validationError }}
                        </div>
                    @endif

                    {{-- Mobile always uses table; desktop respects tableMode toggle --}}
                    <div x-data="{ mobile: window.innerWidth < 768 }">

                        <div class="mb-4 text-sm text-ink-500">
                            <span x-show="!mobile">
                                Drag each tire to its new position, or
                                <button type="button" wire:click="toggleMode" class="text-steel-600 underline hover:text-steel-700 text-sm">
                                    switch to <span x-text="$wire.tableMode ? 'drag' : 'table'"></span> view
                                </button>.
                            </span>
                            <span x-show="mobile" class="text-ink-400">Assign each tire to its new position.</span>
                        </div>

                        @php
                            $availablePositions = collect(TirePosition::order())
                                ->when($tireCount === 4, fn($c) => $c->filter(fn($p) => $p->value !== 'SP'))
                                ->values();
                            $zoneBase = 'justify-self-center border-2 rounded-card p-2 w-44 min-h-40 transition-colors';
                            $garageBase = 'row-span-3 justify-self-center border-2 border-dashed rounded-card p-3 w-44 min-h-48 transition-colors';
                        @endphp

                        {{-- Table view: always on mobile, or when tableMode on desktop --}}
                        <div x-show="mobile || $wire.tableMode">
                            <div class="border border-ink-200 rounded-card overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="bg-ink-50 border-b border-ink-200">
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-ink-400">Tire</th>
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-ink-400">From</th>
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-ink-400">To Position</th>
                                            <th class="px-4 py-2.5 text-left text-[11px] font-semibold uppercase tracking-wide text-ink-400">Tread</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-ink-100">
                                        @foreach ($placements as $fromPos => $p)
                                            <tr class="bg-white">
                                                <td class="px-4 py-3 font-mono font-bold text-ink-900">{{ $p['tire_label'] ?? $fromPos }}</td>
                                                <td class="px-4 py-3">
                                                    <x-treadmark.position-tag :position="$fromPos" size="sm" />
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="relative flex items-center bg-white rounded-control overflow-hidden ring-1 ring-ink-200 focus-within:ring-4 focus-within:ring-blaze-500/40 transition max-w-[11rem]">
                                                        <select wire:model.live="toPositions.{{ $fromPos }}"
                                                                class="appearance-none flex-1 min-w-0 border-0 outline-none bg-transparent text-[15px] text-ink-900 pl-3 pr-9 py-2.5 cursor-pointer">
                                                            @foreach ($availablePositions as $pos)
                                                                <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                                                            @endforeach
                                                        </select>
                                                        <span class="absolute right-3 pointer-events-none text-ink-400">
                                                            <x-treadmark.icon name="caret-down" class="w-4 h-4" />
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 font-mono text-[13px] text-ink-700">
                                                    {{ $p['tread_center'] }}/32"
                                                    @if ($p['tread_inner'] ?? null)
                                                        <span class="text-ink-400 ml-1">i:{{ $p['tread_inner'] }}</span>
                                                    @endif
                                                    @if ($p['tread_outer'] ?? null)
                                                        <span class="text-ink-400 ml-1">o:{{ $p['tread_outer'] }}</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Drag view: desktop only, when not tableMode --}}
                        <div x-show="!mobile && !$wire.tableMode">
                            <div
                                id="drag-root"
                                x-data="rotationDrag(@js($placements), @js($toPositions))"
                                class="grid grid-cols-4 grid-rows-3 gap-3"
                            >
                                {{-- FL --}}
                                <div x-bind:class="dropZoneClass('FL')"
                                     x-on:dragover.prevent="onDragOver('FL')" x-on:dragleave="onDragLeave('FL')"
                                     x-on:drop.prevent="onDrop('FL')" data-position="FL"
                                     class="{{ $zoneBase }}">
                                    <div class="mb-2 pb-1.5 border-b border-ink-200">
                                        <x-treadmark.position-tag position="FL" size="sm" show-label />
                                    </div>
                                    <template x-for="(p, fromPos) in placements" :key="fromPos">
                                        <div x-show="currentPositions[fromPos] === 'FL'" draggable="true"
                                             x-on:dragstart="onDragStart(fromPos, $event)"
                                             class="cursor-grab select-none bg-white border border-ink-200 rounded-control px-2 py-2 mt-1 flex flex-col items-center gap-0.5 shadow-sm">
                                            <x-phosphor-tire-duotone class="w-9 h-9 text-ink-500" />
                                            <span class="font-mono text-xs font-bold text-ink-900" x-text="p.tire_label"></span>
                                            <span class="text-[10px] text-ink-400">from <span x-text="p.from_position_label"></span></span>
                                            <span class="font-mono text-[11px] text-ink-600" x-text="p.tread_center + '/32&quot;'"></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Car diagram --}}
                                <div class="row-span-2 justify-self-center self-center">
                                    <x-img.car-top-view class="w-56" />
                                </div>

                                {{-- FR --}}
                                <div x-bind:class="dropZoneClass('FR')"
                                     x-on:dragover.prevent="onDragOver('FR')" x-on:dragleave="onDragLeave('FR')"
                                     x-on:drop.prevent="onDrop('FR')" data-position="FR"
                                     class="{{ $zoneBase }}">
                                    <div class="mb-2 pb-1.5 border-b border-ink-200">
                                        <x-treadmark.position-tag position="FR" size="sm" show-label />
                                    </div>
                                    <template x-for="(p, fromPos) in placements" :key="fromPos">
                                        <div x-show="currentPositions[fromPos] === 'FR'" draggable="true"
                                             x-on:dragstart="onDragStart(fromPos, $event)"
                                             class="cursor-grab select-none bg-white border border-ink-200 rounded-control px-2 py-2 mt-1 flex flex-col items-center gap-0.5 shadow-sm">
                                            <x-phosphor-tire-duotone class="w-9 h-9 text-ink-500" />
                                            <span class="font-mono text-xs font-bold text-ink-900" x-text="p.tire_label"></span>
                                            <span class="text-[10px] text-ink-400">from <span x-text="p.from_position_label"></span></span>
                                            <span class="font-mono text-[11px] text-ink-600" x-text="p.tread_center + '/32&quot;'"></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- Holding zone --}}
                                <div x-bind:class="dropZoneClass('GARAGE')"
                                     x-on:dragover.prevent="onDragOver('GARAGE')" x-on:dragleave="onDragLeave('GARAGE')"
                                     x-on:drop.prevent="onDrop('GARAGE')" data-position="GARAGE"
                                     class="{{ $garageBase }}">
                                    <p class="text-[11px] font-semibold text-ink-400 uppercase tracking-wide mb-2 text-center">Holding</p>
                                    <template x-for="(p, fromPos) in placements" :key="fromPos">
                                        <div x-show="currentPositions[fromPos] === 'GARAGE'" draggable="true"
                                             x-on:dragstart="onDragStart(fromPos, $event)"
                                             class="cursor-grab select-none bg-white border border-ink-200 rounded-control px-2 py-2 mb-1.5 flex flex-col items-center gap-0.5 shadow-sm">
                                            <x-phosphor-tire-duotone class="w-8 h-8 text-ink-400" />
                                            <span class="font-mono text-xs font-bold text-ink-700" x-text="p.tire_label"></span>
                                            <span class="text-[10px] text-ink-400">from <span x-text="p.from_position_label"></span></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- RL --}}
                                <div x-bind:class="dropZoneClass('RL')"
                                     x-on:dragover.prevent="onDragOver('RL')" x-on:dragleave="onDragLeave('RL')"
                                     x-on:drop.prevent="onDrop('RL')" data-position="RL"
                                     class="{{ $zoneBase }}">
                                    <div class="mb-2 pb-1.5 border-b border-ink-200">
                                        <x-treadmark.position-tag position="RL" size="sm" show-label />
                                    </div>
                                    <template x-for="(p, fromPos) in placements" :key="fromPos">
                                        <div x-show="currentPositions[fromPos] === 'RL'" draggable="true"
                                             x-on:dragstart="onDragStart(fromPos, $event)"
                                             class="cursor-grab select-none bg-white border border-ink-200 rounded-control px-2 py-2 mt-1 flex flex-col items-center gap-0.5 shadow-sm">
                                            <x-phosphor-tire-duotone class="w-9 h-9 text-ink-500" />
                                            <span class="font-mono text-xs font-bold text-ink-900" x-text="p.tire_label"></span>
                                            <span class="text-[10px] text-ink-400">from <span x-text="p.from_position_label"></span></span>
                                            <span class="font-mono text-[11px] text-ink-600" x-text="p.tread_center + '/32&quot;'"></span>
                                        </div>
                                    </template>
                                </div>

                                {{-- RR — col-start-3 when no spare to keep it in the right column --}}
                                <div x-bind:class="dropZoneClass('RR')"
                                     x-on:dragover.prevent="onDragOver('RR')" x-on:dragleave="onDragLeave('RR')"
                                     x-on:drop.prevent="onDrop('RR')" data-position="RR"
                                     class="{{ $tireCount === 4 ? 'col-start-3 ' : '' }}{{ $zoneBase }}">
                                    <div class="mb-2 pb-1.5 border-b border-ink-200">
                                        <x-treadmark.position-tag position="RR" size="sm" show-label />
                                    </div>
                                    <template x-for="(p, fromPos) in placements" :key="fromPos">
                                        <div x-show="currentPositions[fromPos] === 'RR'" draggable="true"
                                             x-on:dragstart="onDragStart(fromPos, $event)"
                                             class="cursor-grab select-none bg-white border border-ink-200 rounded-control px-2 py-2 mt-1 flex flex-col items-center gap-0.5 shadow-sm">
                                            <x-phosphor-tire-duotone class="w-9 h-9 text-ink-500" />
                                            <span class="font-mono text-xs font-bold text-ink-900" x-text="p.tire_label"></span>
                                            <span class="text-[10px] text-ink-400">from <span x-text="p.from_position_label"></span></span>
                                            <span class="font-mono text-[11px] text-ink-600" x-text="p.tread_center + '/32&quot;'"></span>
                                        </div>
                                    </template>
                                </div>

                                @if ($tireCount === 5)
                                    {{-- Spare --}}
                                    <div x-bind:class="dropZoneClass('SP')"
                                         x-on:dragover.prevent="onDragOver('SP')" x-on:dragleave="onDragLeave('SP')"
                                         x-on:drop.prevent="onDrop('SP')" data-position="SP"
                                         class="col-start-2 {{ $zoneBase }}">
                                        <div class="mb-2 pb-1.5 border-b border-ink-200">
                                            <x-treadmark.position-tag position="SP" size="sm" show-label />
                                        </div>
                                        <template x-for="(p, fromPos) in placements" :key="fromPos">
                                            <div x-show="currentPositions[fromPos] === 'SP'" draggable="true"
                                                 x-on:dragstart="onDragStart(fromPos, $event)"
                                                 class="cursor-grab select-none bg-white border border-ink-200 rounded-control px-2 py-2 mt-1 flex flex-col items-center gap-0.5 shadow-sm">
                                                <x-phosphor-tire-duotone class="w-9 h-9 text-ink-500" />
                                                <span class="font-mono text-xs font-bold text-ink-900" x-text="p.tire_label"></span>
                                                <span class="text-[10px] text-ink-400">from <span x-text="p.from_position_label"></span></span>
                                                <span class="font-mono text-[11px] text-ink-600" x-text="p.tread_center + '/32&quot;'"></span>
                                            </div>
                                        </template>
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Sticky submit bar --}}
    <div class="fixed bottom-0 inset-x-0 z-10 bg-white/95 backdrop-blur-sm border-t border-ink-200 px-4 py-3 flex items-center justify-between sm:px-6">
        <x-treadmark.button variant="ghost" href="{{ route('rotations.prepare') }}" wire:navigate>
            &larr; {{ __('Back') }}
        </x-treadmark.button>
        <x-treadmark.button wire:click="save">
            {{ __('Complete Rotation') }}
        </x-treadmark.button>
    </div>
</div>

@script
<script>
Alpine.data('rotationDrag', (placements, initialPositions) => ({
    placements: placements,
    currentPositions: { ...initialPositions },
    dragging: null,
    hovering: null,

    dropZoneClass(pos) {
        const base = pos === 'GARAGE'
            ? 'bg-ink-50 border-ink-200'
            : 'bg-ink-100 border-ink-300';
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
