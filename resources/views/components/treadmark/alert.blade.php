@props([
    'tone' => 'info',
    'title' => null,
    'icon' => null,
])
@php
    $tones = [
        'info'    => ['wrap' => 'bg-steel-50 border-steel-100',  'accent' => 'text-steel-600', 'icon' => 'info-fill'],
        'brand'   => ['wrap' => 'bg-blaze-50 border-blaze-100',  'accent' => 'text-blaze-600', 'icon' => 'arrows-clockwise'],
        'warn'    => ['wrap' => 'bg-gold-100 border-[#F2DFA0]',   'accent' => 'text-[#8A6000]', 'icon' => 'warning-fill'],
        'danger'  => ['wrap' => 'bg-rust-100 border-[#F0C9C5]',   'accent' => 'text-rust-600',  'icon' => 'warning-octagon-fill'],
        'success' => ['wrap' => 'bg-fern-100 border-[#BDE3CA]',   'accent' => 'text-[#1F6B3B]', 'icon' => 'check-circle-fill'],
    ];
    $t = $tones[$tone] ?? $tones['info'];
    $iconComponent = $icon ?? $t['icon'];
@endphp

<div role="status" {{ $attributes->merge(['class' => 'flex gap-3 items-start p-3.5 rounded-card border text-[13px] leading-normal '.$t['wrap']]) }}>
    @if ($iconComponent)
        <x-treadmark.icon :name="$iconComponent" @class(['w-5 h-5 flex-none mt-px', $t['accent']]) />
    @endif
    <div class="flex-1 min-w-0">
        @if ($title)
            <div @class(['font-display font-semibold uppercase tracking-wide text-[13px] mb-0.5', $t['accent']])>{{ $title }}</div>
        @endif
        <div class="text-ink-500 [&_b]:text-ink-900 [&_b]:font-semibold [&_strong]:text-ink-900">{{ $slot }}</div>
    </div>
</div>

{{--
  Tread Mark · Alert — inline contextual banner (full soft fill, not a left-border card).
  <x-treadmark.alert tone="warn" title="Uneven wear">Front right inner is 3/32" below outer. Check pressure.</x-treadmark.alert>
  Tones: info·brand·warn·danger·success (each sets its own inline-SVG icon). Override with :icon="'tire'".
--}}
