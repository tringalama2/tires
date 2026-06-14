@props([
    'depth' => 0,
    'max' => 16,
    'limit' => 2,
    'label' => 'Tread',
    'size' => 'md',
    'showValue' => true,
    'showScale' => false,
])
@php
    $clamped = max(0, min((float) $depth, (float) $max));
    $pct = $max > 0 ? ($clamped / $max) * 100 : 0;
    $limitPct = $max > 0 ? ($limit / $max) * 100 : 0;
    if ($depth <= $limit)      { $color = 'var(--tread-worn)'; }
    elseif ($depth < 5)        { $color = 'var(--tread-low)'; }
    elseif ($depth < 8)        { $color = 'var(--tread-fair)'; }
    else                       { $color = 'var(--tread-good)'; }
    $trackH = ['sm' => 'h-1.5', 'md' => 'h-2.5', 'lg' => 'h-3.5'][$size] ?? 'h-2.5';
    $valSize = ['sm' => 'text-[13px]', 'md' => 'text-[16px]', 'lg' => 'text-[21px]'][$size] ?? 'text-[16px]';
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1.5']) }}>
    @if ($label || $showValue)
        <div class="flex items-baseline justify-between gap-2.5">
            @if ($label)<span class="font-mono uppercase tracking-caps text-[11px] text-ink-400">{{ $label }}</span>@endif
            @if ($showValue)
                <span class="font-mono font-semibold inline-flex items-baseline gap-0.5 {{ $valSize }}" style="color: {{ $color }}">
                    {{ $depth + 0 }}<span class="text-[0.7em] text-ink-400 font-normal">/32"</span>
                </span>
            @endif
        </div>
    @endif
    <div class="relative bg-ink-100 rounded-pill overflow-hidden {{ $trackH }}">
        <div class="h-full rounded-pill transition-[width] duration-300" style="width: {{ $pct }}%; background: {{ $color }}"></div>
        @if ($limit > 0 && $limit < $max)
            <div class="absolute -top-1 -bottom-1 w-0.5 bg-rust-600 rounded" style="left: {{ $limitPct }}%" title="{{ $limit }}/32&quot; legal limit"></div>
        @endif
    </div>
    @if ($showScale)
        <div class="flex justify-between font-mono text-[11px] text-ink-300">
            <span>0</span><span>limit {{ $limit }}</span><span>{{ $max }}/32"</span>
        </div>
    @endif
</div>

{{--
  Tread Mark · TreadGauge — signature tread-depth gauge (32nds) with limit marker.
  Fill shifts good→fair→low→worn. Requires the --tread-* vars from treadmark.css.
  <x-treadmark.tread-gauge :depth="7.5" label="Front Right" />
  <x-treadmark.tread-gauge :depth="2" size="lg" show-scale />
--}}
