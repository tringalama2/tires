@props(['model' => null])

@php
    $method = $model ? 'put' : 'post';
    $route = $model ? route('vehicles.update', $model) : route('vehicles.store');
@endphp

<form method="post" action="{{ $route }}" class="space-y-4">
    @method($method)
    @csrf

    <div class="flex flex-col sm:flex-row gap-4">
        <div class="basis-3/12">
            <x-treadmark.input
                id="year"
                name="year"
                type="number"
                label="Year"
                :value="old('year', $model?->year)"
                min="1900"
                max="9999"
                required
                autofocus
                :error="$errors->first('year')"
            />
        </div>
        <div class="basis-4/12">
            <x-treadmark.input
                id="make"
                name="make"
                type="text"
                label="Make"
                :value="old('make', $model?->make)"
                required
                :error="$errors->first('make')"
            />
        </div>
        <div class="basis-5/12">
            <x-treadmark.input
                id="model"
                name="model"
                type="text"
                label="Model"
                :value="old('model', $model?->model)"
                required
                :error="$errors->first('model')"
            />
        </div>
    </div>

    <x-treadmark.input
        id="vin"
        name="vin"
        type="text"
        label="VIN"
        :value="old('vin', $model?->vin)"
        :error="$errors->first('vin')"
    />

    <x-treadmark.input
        id="nickname"
        name="nickname"
        type="text"
        label="Nickname"
        :value="old('nickname', $model?->nickname)"
        required
        :error="$errors->first('nickname')"
    />

    @if ($model === null)
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="basis-1/2">
                <x-treadmark.input
                    id="tire_count"
                    name="tire_count"
                    type="number"
                    label="Number of Tires to Rotate"
                    hint="4 standard, 5 includes spare"
                    :value="old('tire_count')"
                    min="4"
                    max="5"
                    step="1"
                    required
                    :error="$errors->first('tire_count')"
                />
            </div>
            <div class="basis-1/2">
                <x-treadmark.input
                    id="starting_odometer"
                    name="starting_odometer"
                    type="number"
                    label="Current Odometer"
                    suffix="mi"
                    mono
                    :value="old('starting_odometer')"
                    required
                    :error="$errors->first('starting_odometer')"
                />
            </div>
        </div>
    @endif

    <div class="flex items-center justify-end pt-2">
        <x-treadmark.button type="submit">{{ __('Save') }}</x-treadmark.button>
    </div>
</form>
