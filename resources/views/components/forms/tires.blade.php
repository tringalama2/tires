@props(['vehicle', 'model' => null, 'tirePosition' => null, 'existingTire' => null])

@php
    $method = $model ? 'put' : 'post';
    $route = $model
        ? route('vehicles.setuptires.update', ['vehicle' => $vehicle, 'tire' => $model])
        : route('vehicles.setuptires.store', ['vehicle' => $vehicle, 'tirePosition' => $tirePosition?->value ?? $tirePosition]);
@endphp

<form method="post" action="{{ $route }}" x-data="tireForm()" class="divide-y divide-ink-100">
    @method($method)
    @csrf

    <div class="px-5 py-5 space-y-4">

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

        <div class="grid grid-cols-2 gap-4">
            <x-treadmark.input
                id="brand"
                name="brand"
                type="text"
                label="Brand"
                :value="old('brand', $model?->brand ?? $existingTire?->brand)"
                :error="$errors->first('brand')"
                optional
            />
            <x-treadmark.input
                id="model"
                name="model"
                type="text"
                label="Model"
                :value="old('model', $model?->model ?? $existingTire?->model)"
                :error="$errors->first('model')"
                optional
            />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <x-treadmark.input
                id="size"
                name="size"
                type="text"
                label="Size"
                :value="old('size', $model?->size ?? $existingTire?->size)"
                :error="$errors->first('size')"
                optional
            />
            <x-treadmark.input
                id="purchased_on"
                name="purchased_on"
                type="date"
                label="Purchase date"
                :value="old('purchased_on', $model?->purchased_on?->toDateString() ?? $existingTire?->purchased_on?->toDateString())"
                :error="$errors->first('purchased_on')"
                optional
            />
        </div>

        <x-treadmark.input
            id="tin"
            name="tin"
            type="text"
            label="DOT serial (TIN)"
            :value="old('tin', $model?->tin ?? $existingTire?->tin)"
            :error="$errors->first('tin')"
            optional
            mono
        />

        <x-treadmark.input
            id="description"
            name="description"
            type="text"
            label="Notes"
            :value="old('description', $model?->description)"
            :error="$errors->first('description')"
            optional
        />

    </div>

    {{-- Tread section --}}
    <div class="px-5 py-5 space-y-3">
        <div class="font-mono text-[11px] uppercase tracking-caps text-ink-400">Starting tread depth</div>

        <div class="flex items-start gap-4">
            <div class="flex-1">
                <x-treadmark.input
                    id="starting_tread"
                    name="starting_tread"
                    type="number"
                    min="1"
                    max="16"
                    step="0.5"
                    suffix='/32"'
                    mono
                    :value="old('starting_tread')"
                    required
                    :error="$errors->first('starting_tread')"
                    x-model.number="tread"
                    x-on:input="tread = parseFloat($event.target.value) || 0"
                />
            </div>

            <div class="flex-none pt-1.5">
                <div class="bg-ink-50 border border-ink-100 rounded-control px-4 py-3 min-w-[120px]">
                    <div class="font-mono text-[10px] uppercase tracking-caps text-ink-400 mb-2">Preview</div>
                    <div class="tread-gauge-track"
                         x-bind:style="'--gauge-pct:' + Math.min(100, (tread/16)*100) + '%;--gauge-color:' + gaugeColor">
                        <div class="tread-gauge-fill"></div>
                        <div class="tread-gauge-limit"></div>
                    </div>
                    <div class="font-mono font-semibold text-[18px] mt-2 leading-none"
                         x-text="tread > 0 ? tread + ' /32″' : '—'"
                         x-bind:style="'color:' + gaugeColor">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-between gap-3 px-5 py-4 bg-ink-50">
        <x-treadmark.button
            variant="ghost"
            href="{{ route('vehicles.setuptires.index', $vehicle) }}"
        >
            Cancel
        </x-treadmark.button>
        <x-treadmark.button type="submit" variant="primary">
            Save tire
        </x-treadmark.button>
    </div>
</form>

@once
<script>
function tireForm() {
    return {
        tread: 0,
        get gaugeColor() {
            if (this.tread <= 2)  return 'var(--tread-worn)';
            if (this.tread < 5)   return 'var(--tread-low)';
            if (this.tread < 8)   return 'var(--tread-fair)';
            return 'var(--tread-good)';
        },
    };
}
</script>
@endonce
