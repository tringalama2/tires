@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'optional' => false,
    'suffix' => null,
    'prefix' => null,
    'mono' => false,
    'id' => null,
])
@php
    $id = $id ?? 'tm-in-'.\Illuminate\Support\Str::random(6);
    $ring = $error
        ? 'border-rust-600 focus-within:ring-rust-600/30'
        : 'border-ink-200 focus-within:border-blaze-500 focus-within:ring-blaze-500/40';
    $inputCls = 'flex-1 min-w-0 border-0 outline-none bg-transparent text-[15px] text-ink-900 placeholder:text-ink-300 px-3 py-2.5'
        .($mono ? ' font-mono text-right text-[13px]' : '');
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="font-sans font-semibold text-[13px]">
            {{ $label }}
            @if ($required)<span class="text-blaze-500 ml-0.5">*</span>@endif
            @if ($optional)<span class="text-ink-300 font-normal ml-1.5 text-[12px]">optional</span>@endif
        </label>
    @endif
    <div class="flex items-center bg-white border rounded-control transition focus-within:ring-4 {{ $ring }}">
        @if ($prefix)<span class="flex-none font-mono text-[13px] text-ink-400 pl-3">{{ $prefix }}</span>@endif
        <input id="{{ $id }}" {{ $attributes->merge(['class' => $inputCls]) }} />
        @if ($suffix)<span class="flex-none font-mono text-[13px] text-ink-400 px-3 whitespace-nowrap">{{ $suffix }}</span>@endif
    </div>
    @if ($error)<span class="text-[12px] text-rust-600">{{ $error }}</span>
    @elseif ($hint)<span class="text-[12px] text-ink-400">{{ $hint }}</span>@endif
</div>

{{--
  Tread Mark · Input — labeled field with unit affix.
  <x-treadmark.input label="Center tread" type="number" step="0.5" mono suffix='/32"' required />
  <x-treadmark.input label="Odometer" type="number" mono suffix="mi" wire:model="odometer" />
--}}
