@props([
    'variant' => 'ghost',
    'size' => 'md',
])
@php
    $base = 'inline-flex items-center justify-center flex-none rounded-control transition-colors duration-150 focus:outline-none focus-visible:ring-4 focus-visible:ring-blaze-500/40 active:translate-y-px disabled:opacity-40 disabled:pointer-events-none';
    $variants = [
        'solid'   => 'bg-blaze-500 text-white hover:bg-blaze-600',
        'soft'    => 'bg-ink-50 text-ink-900 hover:bg-ink-100',
        'ghost'   => 'text-ink-500 hover:bg-ink-50 hover:text-ink-900',
        'outline' => 'border border-ink-200 text-ink-900 hover:bg-ink-50',
    ];
    $sizes = [
        'sm' => 'w-8 h-8 text-[17px]',
        'md' => 'w-10 h-10 text-[20px]',
        'lg' => 'w-12 h-12 text-[23px]',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['ghost']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>

{{--
  Tread Mark · IconButton — square, icon-only. Always pass aria-label.
  <x-treadmark.icon-button aria-label="Edit"><x-treadmark.icon name="pencil-simple" class="w-5 h-5"/></x-treadmark.icon-button>
  Variants: solid · soft · ghost · outline.  Sizes: sm(32) · md(40) · lg(48).
--}}
