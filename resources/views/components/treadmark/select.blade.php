@props([
    'label' => null,
    'hint' => null,
    'error' => null,
    'required' => false,
    'placeholder' => null,
    'options' => [],
    'id' => null,
])
@php
    $id = $id ?? 'tm-sel-'.\Illuminate\Support\Str::random(6);
    $ring = $error
        ? 'border-rust-600 focus-within:ring-rust-600/30'
        : 'border-ink-200 focus-within:border-blaze-500 focus-within:ring-blaze-500/40';
@endphp

<div class="flex flex-col gap-1.5">
    @if ($label)
        <label for="{{ $id }}" class="font-sans font-semibold text-[13px] text-ink-900">
            {{ $label }}@if ($required)<span class="text-blaze-500 ml-0.5">*</span>@endif
        </label>
    @endif
    <div class="relative flex items-center bg-white border rounded-control transition focus-within:ring-4 {{ $ring }}">
        <select id="{{ $id }}" {{ $attributes->merge(['class' => 'appearance-none flex-1 min-w-0 border-0 outline-none bg-transparent text-[15px] text-ink-900 pl-3 pr-9 py-2.5 cursor-pointer rounded-control']) }}>
            @if ($placeholder)<option value="" disabled selected>{{ $placeholder }}</option>@endif
            @foreach ($options as $key => $opt)
                @php [$val, $lab] = is_array($opt) ? [$opt['value'], $opt['label']] : (is_int($key) ? [$opt, $opt] : [$key, $opt]); @endphp
                <option value="{{ $val }}">{{ $lab }}</option>
            @endforeach
            {{ $slot ?? '' }}
        </select>
        <span class="absolute right-3 pointer-events-none text-ink-400"><x-treadmark.icon name="caret-down" class="w-4 h-4"/></span>
    </div>
    @if ($error)<span class="text-[12px] text-rust-600">{{ $error }}</span>
    @elseif ($hint)<span class="text-[12px] text-ink-400">{{ $hint }}</span>@endif
</div>

{{--
  Tread Mark · Select — styled native select with inline-SVG caret.
  <x-treadmark.select label="Status" :options="['Active','Retired']" />
  <x-treadmark.select label="Move to" placeholder="Choose" :options="[['value'=>'FL','label'=>'Front Left']]" />
--}}
