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
                        <div class="text-blue-600 text-2xl font-semibold">{{ $this->vehicle->nickname }}</div>
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
                                <x-input-label for="rotated_on" :value="__('Rotation Date')" />
                                <x-text-input wire:model="rotated_on" id="rotated_on" class="block mt-1 w-full" type="date" name="rotated_on" required />
                                <x-input-error :messages="$errors->get('rotated_on')" class="mt-1" />
                            </div>
                            <div class="basis-1/2">
                                <x-input-label for="odometer" :value="__('Odometer (miles)')" />
                                <x-text-input wire:model="odometer" id="odometer" class="block mt-1 w-full" type="number" name="odometer" min="1" required />
                                <x-input-error :messages="$errors->get('odometer')" class="mt-1" />
                            </div>
                        </div>

                        {{-- Optional rotation note --}}
                        <div class="mb-6">
                            <x-input-label for="rotation_note" :value="__('Rotation Note (optional)')" />
                            <textarea wire:model="rotation_note" id="rotation_note" name="rotation_note"
                                class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
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
                                        <span class="font-bold text-blue-700">{{ $tire->label }}</span>
                                    </div>

                                    @if ($lastTread !== null)
                                        <p class="text-xs text-gray-400 mb-2">Last: {{ $lastTread }}/32"</p>
                                    @endif

                                    {{-- Center tread (required) — text-base prevents iOS zoom on focus --}}
                                    <div class="mb-3">
                                        <x-input-label :for="'tread_center_'.$pos" value="Center *" />
                                        <div class="relative">
                                            <x-text-input
                                                wire:model="treads.{{ $pos }}.tread_center"
                                                :id="'tread_center_'.$pos"
                                                class="block mt-1 w-full pr-12 text-right text-base min-h-[44px]"
                                                type="number" step="0.5" min="0" max="20" required
                                                inputmode="decimal"
                                            />
                                            <span class="absolute inset-y-0 right-3 flex items-center text-gray-400 text-sm mt-1">/32"</span>
                                        </div>
                                        <x-input-error :messages="$errors->get('treads.'.$pos.'.tread_center')" class="mt-1" />
                                    </div>

                                    {{-- Inner + Outer (optional) --}}
                                    <div class="flex gap-2 mb-3">
                                        <div class="flex-1">
                                            <x-input-label :for="'tread_inner_'.$pos" value="Inner" />
                                            <div class="relative">
                                                <x-text-input
                                                    wire:model="treads.{{ $pos }}.tread_inner"
                                                    :id="'tread_inner_'.$pos"
                                                    class="block mt-1 w-full pr-10 text-right text-base min-h-[44px]"
                                                    type="number" step="0.5" min="0" max="20"
                                                    inputmode="decimal"
                                                />
                                                <span class="absolute inset-y-0 right-2 flex items-center text-gray-400 text-sm mt-1">/32"</span>
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <x-input-label :for="'tread_outer_'.$pos" value="Outer" />
                                            <div class="relative">
                                                <x-text-input
                                                    wire:model="treads.{{ $pos }}.tread_outer"
                                                    :id="'tread_outer_'.$pos"
                                                    class="block mt-1 w-full pr-10 text-right text-base min-h-[44px]"
                                                    type="number" step="0.5" min="0" max="20"
                                                    inputmode="decimal"
                                                />
                                                <span class="absolute inset-y-0 right-2 flex items-center text-gray-400 text-sm mt-1">/32"</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Note --}}
                                    <div>
                                        <x-input-label :for="'note_'.$pos" value="Note (optional)" />
                                        <textarea
                                            wire:model="treads.{{ $pos }}.note"
                                            :id="'note_'.$pos"
                                            class="block mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-xs"
                                            rows="2"></textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="flex items-center justify-end">
                            <x-primary-button class="min-h-[48px] text-base px-6">
                                {{ __('Next: Assign Positions →') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
