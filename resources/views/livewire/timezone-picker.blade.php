<?php

use Illuminate\Support\Facades\DB;
use Livewire\Volt\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Modelable;
use Livewire\Attributes\Reactive;
use Illuminate\Support\Collection;

new class extends Component {

    #[Reactive]
    public $timezoneErrors;

    #[Modelable]
    public $timezone = '';

    public $countryCode = 'US';

    public function updatedCountryCode(): void
    {
        $this->timezone = null;
    }

    #[Computed]
    public function countries(): Collection
    {
        return DB::table('countries')->select(['code', 'name'])->orderBy('name')->get();
    }

    #[Computed]
    public function timezones(): array
    {
        return DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, $this->countryCode);
    }
} ?>
<div>
    <div class="mt-4">
        <x-input-label for="country" :value="__('Country')" />

        <x-select wire:model.live="countryCode">
            <option disabled value="">Select your country</option>
            @foreach($this->countries as $country)
                <option value="{{ $country->code }}">{{ $country->name }}</option>
            @endforeach
        </x-select>
    </div>
    <div class="mt-4">
        <x-input-label for="timezone" :value="__('Timezone')" />

        <x-select wire:model.live="timezone" wire:key="{{ $countryCode }}">
            <option value="">Select nearest city</option>
            @foreach($this->timezones as $timezone)
                <option value="{{ $timezone }}">{{ $timezone }}</option>
            @endforeach
        </x-select>

        <x-input-error :messages="$timezoneErrors" class="mt-2" />
    </div>
</div>
