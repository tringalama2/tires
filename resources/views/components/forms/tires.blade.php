@props(['vehicle', 'model' => null, 'tirePosition' => null, 'existingTire' => null])

@php
    $method = $model ? 'put' : 'post';
    $route = $model
        ? route('vehicles.setuptires.update', ['vehicle' => $vehicle, 'tire' => $model])
        : route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => $tirePosition]);
@endphp

<form method="post" action="{{ $route }}">
    @method($method)
    @csrf

    <!-- Label -->
    <div class="mt-4">
        <x-input-label for="label" :value="__('Label')"/>
        <x-text-input :value="old('label', $model?->label)" id="label" class="block mt-1 w-full" type="text" name="label" required autofocus/>
        <x-forms.input-error for="label" class="mt-2"/>
    </div>

    <!-- Brand -->
    <div class="mt-4">
        <x-input-label for="brand" :value="__('Brand')"/>
        <x-text-input :value="old('brand', $model?->brand ?? $existingTire?->brand)" id="brand" class="block mt-1 w-full" type="text" name="brand"/>
        <x-forms.input-error for="brand" class="mt-2"/>
    </div>

    <!-- Model -->
    <div class="mt-4">
        <x-input-label for="model" :value="__('Model')"/>
        <x-text-input :value="old('model', $model?->model ?? $existingTire?->model)" id="model" class="block mt-1 w-full" type="text" name="model"/>
        <x-forms.input-error for="model" class="mt-2"/>
    </div>

    <!-- Size -->
    <div class="mt-4">
        <x-input-label for="size" :value="__('Size')"/>
        <x-text-input :value="old('size', $model?->size ?? $existingTire?->size)" id="size" class="block mt-1 w-full" type="text" name="size"/>
        <x-forms.input-error for="size" class="mt-2"/>
    </div>

    <!-- TIN -->
    <div class="mt-4">
        <x-input-label for="tin" :value="__('TIN')"/>
        <x-text-input :value="old('tin', $model?->tin ?? $existingTire?->tin)" id="tin" class="block mt-1 w-full" type="text" name="tin"/>
        <x-forms.input-error for="tin" class="mt-2"/>
    </div>

    <!-- Description -->
    <div class="mt-4">
        <x-input-label for="description" :value="__('Description')"/>
        <x-text-input :value="old('description', $model?->description)" id="description" class="block mt-1 w-full" type="text" name="description"/>
        <x-forms.input-error for="description" class="mt-2"/>
    </div>

    <!-- Purchase Date -->
    <div class="mt-4">
        <x-input-label for="purchased_on" :value="__('Purchase Date')"/>
        <x-text-input :value="old('purchased_on', $model?->purchased_on->toDateString() ?? $existingTire?->purchased_on->toDateString())" id="purchased_on" class="block mt-1 w-full sm:w-48" type="date" name="purchased_on" required/>
        <x-forms.input-error for="purchased_on" class="mt-2"/>
    </div>

    <!-- Starting Tread -->
    <div class="mt-4">
        <x-input-label for="starting_tread" :value="__('Initial Tread Depth')"/>
        <div class="relative w-full sm:w-24">
            <x-text-input :value="old('starting_tread')" id="starting_tread" class="text-right block mt-1 w-full pe-12" type="text" name="starting_tread" required/>
            <div class="absolute inset-y-0 end-0 flex items-center pointer-events-none z-20 pe-4">
                <span class="text-gray-400 text-sm font-bold">/32"</span>
            </div>
        </div>
        <x-forms.input-error for="starting_tread" class="mt-2"/>
    </div>

    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="ms-4">
            {{ __('Save') }}
        </x-primary-button>
    </div>
</form>



