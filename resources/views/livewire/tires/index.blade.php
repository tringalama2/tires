<?php

use App\Actions\SelectVehicle;
use App\Enums\TireStatus;
use App\Models\Tire;
use App\Models\Vehicle;
use App\Services\TireService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.app')] class extends Component {

    public ?int $vehicle_id;

    public bool $showAddForm = false;

    // Add-tire form fields
    #[Validate('required|string|max:50')]
    public string $label = '';

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

    #[Validate('required|integer|in:1,2')]
    public int $status = TireStatus::Active->value;

    public function mount(SelectVehicle $selectVehicle): void
    {
        if (isset($this->vehicle_id)) {
            $vehicle = Vehicle::findOrFail($this->vehicle_id);
            $selectVehicle($vehicle);
        } else {
            $vehicle = session('vehicle');
        }
        $this->vehicle_id = $vehicle->id;
    }

    private function vehicle(): Vehicle
    {
        return Vehicle::findOrFail($this->vehicle_id);
    }

    #[Computed]
    public function tires(): \Illuminate\Support\Collection
    {
        $tireService = app(TireService::class);

        return $this->vehicle()->tires()
            ->orderBy('label')
            ->get()
            ->map(function (Tire $tire) use ($tireService) {
                $pos = $tireService->currentPosition($tire);
                $latestTread = $tire->placements()
                    ->join('rotations', 'rotations.id', '=', 'placements.rotation_id')
                    ->where('rotations.is_setup', false)
                    ->orderByDesc('rotations.odometer')
                    ->value('placements.tread_center');

                return [
                    'tire' => $tire,
                    'current_position' => $pos?->label() ?? '—',
                    'latest_tread' => $latestTread !== null ? (float) $latestTread : null,
                ];
            });
    }

    public function openAddForm(): void
    {
        $this->resetAddForm();
        $this->showAddForm = true;
    }

    public function cancelAdd(): void
    {
        $this->showAddForm = false;
        $this->resetAddForm();
    }

    public function addTire(): void
    {
        $this->validate();

        Tire::create([
            'vehicle_id' => $this->vehicle_id,
            'label' => $this->label,
            'brand' => $this->brand ?: null,
            'model' => $this->model ?: null,
            'tin' => $this->tin ?: null,
            'size' => $this->size ?: null,
            'purchased_on' => $this->purchased_on ?: null,
            'status' => TireStatus::from((int) $this->status),
        ]);

        $this->showAddForm = false;
        $this->resetAddForm();
        unset($this->tires);
    }

    public function toggleStatus(string $tireId): void
    {
        $tire = Tire::findOrFail($tireId);
        $this->authorize('update', $tire);

        $tire->update([
            'status' => $tire->status === TireStatus::Active
                ? TireStatus::Retired
                : TireStatus::Active,
        ]);

        unset($this->tires);
    }

    private function resetAddForm(): void
    {
        $this->label = '';
        $this->brand = null;
        $this->model = null;
        $this->tin = null;
        $this->size = null;
        $this->purchased_on = null;
        $this->status = TireStatus::Active->value;
        $this->resetValidation();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.tires.index');
    }
};

?>

<div>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tires') }}</h2>
            @unless ($showAddForm)
                <button wire:click="openAddForm"
                    class="inline-flex items-center px-3 py-1.5 bg-blaze-500 text-white text-sm font-semibold rounded-control hover:bg-blaze-600 transition-colors">
                    + Add Tire
                </button>
            @endunless
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Add tire form --}}
            @if ($showAddForm)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="font-semibold text-gray-700 mb-4">Add Tire</h3>
                        <form wire:submit="addTire" class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                            <div>
                                <x-input-label value="Label *" />
                                <x-text-input wire:model="label" class="mt-1 block w-full" type="text" placeholder="T6" required />
                                <x-input-error :messages="$errors->get('label')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label value="Brand" />
                                <x-text-input wire:model="brand" class="mt-1 block w-full" type="text" />
                            </div>
                            <div>
                                <x-input-label value="Model" />
                                <x-text-input wire:model="model" class="mt-1 block w-full" type="text" />
                            </div>
                            <div>
                                <x-input-label value="DOT / TIN" />
                                <x-text-input wire:model="tin" class="mt-1 block w-full" type="text" maxlength="12" />
                            </div>
                            <div>
                                <x-input-label value="Size" />
                                <x-text-input wire:model="size" class="mt-1 block w-full" type="text" placeholder="275/70R18" />
                            </div>
                            <div>
                                <x-input-label value="Purchase Date" />
                                <x-text-input wire:model="purchased_on" class="mt-1 block w-full" type="date" />
                            </div>
                            <div>
                                <x-input-label value="Status" />
                                <select wire:model="status" class="mt-1 block w-full border-ink-200 rounded-control shadow-sm text-sm focus:ring-blaze-500/40 focus:border-blaze-500">
                                    <option value="{{ TireStatus::Active->value }}">Active</option>
                                    <option value="{{ TireStatus::Retired->value }}">Retired</option>
                                </select>
                            </div>
                            <div class="col-span-2 sm:col-span-3 flex gap-3 pt-2">
                                <x-primary-button type="submit">Add Tire</x-primary-button>
                                <button type="button" wire:click="cancelAdd" class="text-sm text-gray-500 hover:underline">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            {{-- Tire list --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 overflow-x-auto">
                    @if ($this->tires->isEmpty())
                        <p class="text-sm text-gray-400">No tires yet.</p>
                    @else
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left border-b border-gray-200">
                                    <th class="pb-3 font-semibold text-gray-600">Label</th>
                                    <th class="pb-3 font-semibold text-gray-600">Brand / Model</th>
                                    <th class="pb-3 font-semibold text-gray-600">Position</th>
                                    <th class="pb-3 font-semibold text-gray-600 text-right">Latest Tread</th>
                                    <th class="pb-3 font-semibold text-gray-600">Status</th>
                                    <th class="pb-3 font-semibold text-gray-600">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($this->tires as $row)
                                    @php $tire = $row['tire']; @endphp
                                    <tr class="hover:bg-gray-50 {{ $tire->status === \App\Enums\TireStatus::Retired ? 'opacity-60' : '' }}">
                                        <td class="py-3 font-bold text-ink-900">
                                            <a href="{{ route('tires.show', $tire) }}" class="hover:underline">{{ $tire->label }}</a>
                                        </td>
                                        <td class="py-3 text-gray-700">
                                            {{ implode(' ', array_filter([$tire->brand, $tire->model])) ?: '—' }}
                                        </td>
                                        <td class="py-3 text-gray-600">{{ $row['current_position'] }}</td>
                                        <td class="py-3 text-right font-mono text-gray-700">
                                            {{ $row['latest_tread'] !== null ? $row['latest_tread'].'/32"' : '—' }}
                                        </td>
                                        <td class="py-3">
                                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $tire->status === \App\Enums\TireStatus::Active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                                {{ $tire->status->label() }}
                                            </span>
                                        </td>
                                        <td class="py-3 flex gap-3">
                                            <a href="{{ route('tires.show', $tire) }}" class="text-sm text-steel-600 hover:text-steel-800 hover:underline">Edit</a>
                                            <button wire:click="toggleStatus('{{ $tire->id }}')"
                                                class="text-sm text-gray-500 hover:underline">
                                                {{ $tire->status === \App\Enums\TireStatus::Active ? 'Retire' : 'Reactivate' }}
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
