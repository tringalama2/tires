@props([
    'label' => null,
    'value' => '',
    'unit' => null,
    'sub' => null,
    'size' => 'md',
    'tone' => 'default',
    'mono' => false,
])
@php
    $valueSize = ['sm' => 'text-[21px]', 'md' => 'text-[34px]', 'lg' => 'text-[46px]'][$size] ?? 'text-[34px]';
    $valueColor = ['default' => 'text-ink-900', 'brand' => 'text-blaze-600', 'inverse' => 'text-white'][$tone] ?? 'text-ink-900';
    $labelColor = $tone === 'inverse' ? 'text-ink-300' : 'text-ink-400';
    $valueFont = $mono ? 'font-mono' : 'font-display';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1']) }}>
    @if ($label)
        <span class="font-mono uppercase tracking-caps text-[11px] {{ $labelColor }}">{{ $label }}</span>
    @endif
    <div class="flex items-baseline gap-1.5 leading-none {{ $valueFont }} font-semibold tracking-wide {{ $valueColor }} {{ $valueSize }}">
        <span>{{ $value }}</span>
        @if ($unit)<span class="font-mono font-medium text-[0.42em] tracking-normal {{ $labelColor }}">{{ $unit }}</span>@endif
    </div>
    @if ($sub)<div class="text-[13px] {{ $tone === 'inverse' ? 'text-ink-300' : 'text-ink-500' }}">{{ $sub }}</div>@endif
</div>

{{--
  Tread Mark · StatTile — headline metric.
  <x-treadmark.stat-tile label="Odometer" value="120,495" unit="mi" mono sub="Last rotation" />
  <x-treadmark.stat-tile label="Latest tread" value="7.5" unit='/32"' tone="brand" size="lg" mono />
  size: sm·md·lg.  tone: default·brand·inverse.
--}}
