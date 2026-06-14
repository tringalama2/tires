@props([
    'tone' => 'neutral',
    'variant' => 'soft',
    'size' => 'md',
    'dot' => false,
])
@php
    $base = 'inline-flex items-center gap-1.5 font-mono font-medium uppercase tracking-wider2 rounded-pill whitespace-nowrap border border-transparent';
    $sizes = ['sm' => 'text-[11px] px-2 py-0.5', 'md' => 'text-[12px] px-2.5 py-1'];
    $soft = [
        'neutral' => 'bg-ink-100 text-ink-600',
        'brand'   => 'bg-blaze-100 text-blaze-700',
        'steel'   => 'bg-steel-100 text-steel-700',
        'success' => 'bg-fern-100 text-[#1F6B3B]',
        'gold'    => 'bg-gold-100 text-[#8A6000]',
        'danger'  => 'bg-rust-100 text-rust-600',
    ];
    $solid = [
        'neutral' => 'bg-ink-700 text-white', 'brand' => 'bg-blaze-500 text-white',
        'steel' => 'bg-steel-600 text-white', 'success' => 'bg-fern-600 text-white',
        'gold' => 'bg-gold-600 text-white', 'danger' => 'bg-rust-600 text-white',
    ];
    $outline = 'bg-transparent border-ink-200 text-ink-500';
    $tones = $variant === 'solid' ? $solid : ($variant === 'outline' ? [] : $soft);
    $toneCls = $variant === 'outline' ? $outline : ($tones[$tone] ?? $soft['neutral']);
    $classes = $base.' '.($sizes[$size] ?? $sizes['md']).' '.$toneCls;
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if ($dot)<span class="w-1.5 h-1.5 rounded-full bg-current flex-none"></span>@endif
    {{ $slot }}
</span>

{{--
  Tread Mark · Badge — mono status pill.
  <x-treadmark.badge tone="success" dot>Active</x-treadmark.badge>
  <x-treadmark.badge tone="brand" variant="solid">Fastest</x-treadmark.badge>
  Tones: neutral·brand·steel·success·gold·danger. Variants: soft·solid·outline.
--}}
