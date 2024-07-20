@props(['model' => null])

@php
    $method = $model ? 'put' : 'post';
    $route = $model ? route('tires.update', $model) : route('tires.store');
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
        <x-text-input :value="old('brand', $model?->brand)" id="brand" class="block mt-1 w-full" type="text" name="brand" autofocus/>
        <x-forms.input-error for="brand" class="mt-2"/>
    </div>

    <!-- Model -->
    <div class="mt-4">
        <x-input-label for="model" :value="__('Model')"/>
        <x-text-input :value="old('model', $model?->model)" id="model" class="block mt-1 w-full" type="text" name="model" autofocus/>
        <x-forms.input-error for="model" class="mt-2"/>
    </div>

    <!-- TIN -->
    <div class="mt-4">
        <x-input-label for="tin" :value="__('TIN')"/>
        <x-text-input :value="old('tin', $model?->tin)" id="tin" class="block mt-1 w-full" type="text" name="tin" autofocus/>
        <x-forms.input-error for="tin" class="mt-2"/>
    </div>

    <!-- Description -->
    <div class="mt-4">
        <x-input-label for="description" :value="__('Description')"/>
        <x-text-input :value="old('description', $model?->description)" id="description" class="block mt-1 w-full" type="text" name="description" autofocus/>
        <x-forms.input-error for="description" class="mt-2"/>
    </div>

    <!-- Size -->
    <div class="mt-4">
        <x-input-label for="size" :value="__('Size')"/>
        <x-text-input :value="old('size', $model?->size)" id="size" class="block mt-1 w-full" type="text" name="size" autofocus/>
        <x-forms.input-error for="size" class="mt-2"/>
    </div>

    <!-- Purchase Date -->
    <div class="mt-4">
        <x-input-label for="purchased_on" :value="__('Purchase Date')"/>
        <x-text-input :value="old('purchased_on', $model?->purchased_on)" id="purchased_on" class="block mt-1 w-full" type="date" name="purchased_on" required autofocus/>
        <x-forms.input-error for="purchased_on" class="mt-2"/>
    </div>

    <div class="flex items-center justify-end mt-4">
        <x-primary-button class="ms-4">
            {{ __('Save') }}
        </x-primary-button>
    </div>
</form>



