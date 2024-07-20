@props(['model' => null])

@php
    $method = $model ? 'put' : 'post';
    $route = $model ? route('vehicles.update', $model) : route('vehicles.store');

@endphp
<form method="post" action="{{ $route }}">
    @method($method)
    @csrf

    <!-- Year -->
    <div class="mt-4">
        <x-input-label for="year" :value="__('Year')"/>
        <x-text-input :value="old('year', $model?->year)" id="year" class="block mt-1 w-full" type="number" min="1900" max="9999" name="year" required autofocus/>
        <x-forms.input-error for="year" class="mt-2"/>
    </div>

    <!-- Make -->
    <div class="mt-4">
        <x-input-label for="make" :value="__('Make')"/>
        <x-text-input :value="old('make', $model?->make)" id="make" class="block mt-1 w-full" type="text" name="make" required/>
        <x-forms.input-error for="make" class="mt-2"/>
    </div>

    <!-- Model -->
    <div class="mt-4">
        <x-input-label for="model" :value="__('Model')"/>
        <x-text-input :value="old('model', $model?->model)" id="model" class="block mt-1 w-full" type="text" name="model" required/>
        <x-forms.input-error for="model" class="mt-2"/>
    </div>

    <!-- VIN -->
    <div class="mt-4">
        <x-input-label for="vin" :value="__('VIN')"/>
        <x-text-input :value="old('vin', $model?->vin)" id="vin" class="block mt-1 w-full" type="text" name="vin" required/>
        <x-forms.input-error for="vin" class="mt-2"/>
    </div>

    <!-- Nickname -->
    <div class="mt-4">
        <x-input-label for="nickname" :value="__('Nickname')"/>
        <x-text-input :value="old('nickname', $model?->nickname)" id="nickname" class="block mt-1 w-full" type="text" name="nickname" required/>
        <x-forms.input-error for="nickname" class="mt-2"/>
    </div>

    <!-- Tires to Rotate -->
    <div class="mt-4">
        <x-input-label for="tire_count" :value="__('Number of Tires to Rotate')"/>
        <x-text-input type="number" min="4" max="5" step="1"
                      :value="old('tire_count', $model?->tire_count)" id="tire_count"
                      class="block mt-1 w-full" name="tire_count"
                      :disabled="$model" :required="!$model"
        />
        <x-forms.input-error for="tire_count" class="mt-2"/>
    </div>


    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="ms-4">
            {{ __('Save') }}
        </x-primary-button>
    </div>
</form>



