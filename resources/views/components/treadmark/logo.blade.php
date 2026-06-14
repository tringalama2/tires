@props([
    'variant' => 'full',
    'tone' => 'onLight',
    'size' => 40,
    'showTagline' => false,
    'tagline' => 'Every rotation, on the record.',
    'href' => null,
])
@php
    $onDark = $tone === 'onDark';
    $stroke = $onDark ? '#FFFFFF' : '#0F1410';
    $accent = $onDark ? '#FF7A2E' : '#FF5400';
    $wordColor = $onDark ? 'text-white' : 'text-ink-900';
    $dotColor = $onDark ? '#FF7A2E' : '#E64A00';
    $tagColor = $onDark ? 'text-ink-300' : 'text-ink-400';
    $wordSize = round($size * 0.64);
    $tagSize = max(8, round($size * 0.21));
    $gap = round($size * 0.34);
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'inline-flex items-center no-underline leading-none']) }} style="gap: {{ $gap }}px" aria-label="Tread Mark">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 64 64" fill="none" stroke-linecap="round" stroke-linejoin="round" class="flex-none block">
        <path d="M16 46 L32 36 L48 46" stroke="{{ $stroke }}" stroke-width="7"/>
        <path d="M16 35 L32 25 L48 35" stroke="{{ $stroke }}" stroke-width="7"/>
        <path d="M16 24 L32 14 L48 24" stroke="{{ $accent }}" stroke-width="7"/>
    </svg>
    @if ($variant === 'full')
        <span class="flex flex-col leading-none">
            <span class="font-display font-bold uppercase tracking-wide whitespace-nowrap {{ $wordColor }}" style="font-size: {{ $wordSize }}px">Tread<span style="color: {{ $dotColor }}">·</span>Mark</span>
            @if ($showTagline)
                <span class="font-mono uppercase tracking-caps mt-2 {{ $tagColor }}" style="font-size: {{ $tagSize }}px">{{ $tagline }}</span>
            @endif
        </span>
    @endif
</{{ $tag }}>

{{--
  Tread Mark · Logo — chevron mark + wordmark.
  <x-treadmark.logo :size="40" />
  <x-treadmark.logo tone="onDark" show-tagline />
  <x-treadmark.logo variant="mark" :size="28" />
--}}
