@props([
    'tirePosition',
    'tire',
    'vehicle'
])


<div class="rounded-lg bg-gradient-to-tr from-gray-900 to-gray-800 text-white shadow-gray-200/20 shadow-md p-8">
    <div class="font-bold text-xl self-center border-b border-white/10 mb-4 pb-4">
        {{ $tirePosition->label() }}
    </div>
    @if($tire)
        <div {{ $attributes->class(['text-sm']) }}>
            <div class="flex">
                <div class="w-1/3">Label:</div>
                <div class="w-2/3">{{ $tire->label }}</div>
            </div>
            <div class="flex">
                <div class="w-1/3">Brand & Model:</div>
                <div class="w-2/3">{{ $tire->brand }} {{ $tire->model }}</div>
            </div>
            <div class="flex">
                <div class="w-1/3">Size:</div>
                <div class="w-2/3">{{ $tire->size }}</div>
            </div>
            <div class="flex">
                <div class="w-1/3">Purchased:</div>
                <div class="w-2/3">{{ $tire->purchased_on->format('M Y') }}</div>
            </div>
        </div>
    @else
        <a class="btn-white text-xs ms-4"
           href="{{ route('vehicles.setuptires.create', compact('vehicle', 'tirePosition')) }}">Add Tire</a>
    @endif
</div>
