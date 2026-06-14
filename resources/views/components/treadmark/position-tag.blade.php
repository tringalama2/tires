@props([
    'position' => 'FL',
    'variant' => 'soft',
    'active' => false,
    'size' => 'md',
    'showLabel' => false,
])
@php
    $labels = ['FL' => 'Front Left', 'FR' => 'Front Right', 'RL' => 'Rear Left', 'RR' => 'Rear Right', 'SP' => 'Spare'];
    $code = strtoupper($position);
    $pad = ['sm' => 'px-2 py-1', 'md' => 'px-2.5 py-1.5', 'lg' => 'px-3.5 py-2'][$size] ?? 'px-2.5 py-1.5';
    $codeSize = ['sm' => 'text-[13px]', 'md' => 'text-[16px]', 'lg' => 'text-[21px]'][$size] ?? 'text-[16px]';
    $labSize = ['sm' => 'text-[12px]', 'md' => 'text-[13px]', 'lg' => 'text-[15px]'][$size] ?? 'text-[13px]';
    if ($active) {
        $skin = 'bg-blaze-500'; $codeColor = 'text-white'; $labColor = 'text-white';
    } elseif ($variant === 'outline') {
        $skin = 'border border-ink-200'; $codeColor = 'text-ink-900'; $labColor = 'text-ink-500';
    } else {
        $skin = 'bg-ink-50'; $codeColor = 'text-ink-900'; $labColor = 'text-ink-500';
    }
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 rounded-control font-display uppercase leading-none $pad $skin"]) }}>
    <span class="font-bold tracking-wide {{ $codeColor }} {{ $codeSize }}">{{ $code }}</span>
    @if ($showLabel)<span class="font-sans font-medium normal-case {{ $labColor }} {{ $labSize }}">{{ $labels[$code] ?? '' }}</span>@endif
</span>

{{--
  Tread Mark · PositionTag — FL·FR·RL·RR·SP chip.
  <x-treadmark.position-tag position="FR" active />
  <x-treadmark.position-tag position="RL" show-label />
--}}
