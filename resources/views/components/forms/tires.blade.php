@props(['vehicle', 'model' => null, 'tirePosition' => null, 'existingTire' => null])

@php
    $method = $model ? 'put' : 'post';
    $route = $model
        ? route('vehicles.setuptires.update', ['vehicle' => $vehicle, 'tire' => $model])
        : route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => $tirePosition?->value ?? $tirePosition]);
@endphp

<form method="post" action="{{ $route }}" class="space-y-4">
    @method($method)
    @csrf

    <x-treadmark.input
        id="label"
        name="label"
        type="text"
        label="Label"
        :value="old('label', $model?->label)"
        required
        autofocus
        :error="$errors->first('label')"
    />

    <x-treadmark.input
        id="brand"
        name="brand"
        type="text"
        label="Brand"
        :value="old('brand', $model?->brand ?? $existingTire?->brand)"
        :error="$errors->first('brand')"
    />

    <x-treadmark.input
        id="model"
        name="model"
        type="text"
        label="Model"
        :value="old('model', $model?->model ?? $existingTire?->model)"
        :error="$errors->first('model')"
    />

    <x-treadmark.input
        id="size"
        name="size"
        type="text"
        label="Size"
        :value="old('size', $model?->size ?? $existingTire?->size)"
        :error="$errors->first('size')"
    />

    <x-treadmark.input
        id="tin"
        name="tin"
        type="text"
        label="TIN"
        :value="old('tin', $model?->tin ?? $existingTire?->tin)"
        :error="$errors->first('tin')"
    />

    <x-treadmark.input
        id="description"
        name="description"
        type="text"
        label="Description"
        :value="old('description', $model?->description)"
        :error="$errors->first('description')"
    />

    <x-treadmark.input
        id="purchased_on"
        name="purchased_on"
        type="date"
        label="Purchase Date"
        :value="old('purchased_on', $model?->purchased_on?->toDateString() ?? $existingTire?->purchased_on?->toDateString())"
        :error="$errors->first('purchased_on')"
    />

    <x-treadmark.input
        id="starting_tread"
        name="starting_tread"
        type="text"
        label="Initial Tread Depth"
        suffix='/32"'
        mono
        :value="old('starting_tread')"
        required
        :error="$errors->first('starting_tread')"
    />

    <div class="flex items-center justify-end pt-2">
        <x-treadmark.button type="submit">{{ __('Save') }}</x-treadmark.button>
    </div>
</form>
