@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'block' => false,
])
@php
    $base = 'inline-flex items-center justify-center gap-2 font-display font-semibold uppercase tracking-wider2 whitespace-nowrap rounded-control transition-colors duration-150 select-none focus:outline-none focus-visible:ring-4 focus-visible:ring-blaze-500/40 active:translate-y-px disabled:opacity-45 disabled:pointer-events-none';
    $variants = [
        'primary'   => 'bg-blaze-500 text-white hover:bg-blaze-600 active:bg-blaze-700',
        'secondary' => 'bg-transparent text-ink-900 border border-ink-200 hover:bg-ink-50 hover:border-ink-300',
        'ghost'     => 'bg-transparent text-ink-500 hover:bg-ink-50 hover:text-ink-900',
        'danger'    => 'bg-rust-600 text-white hover:opacity-90',
        'inverse'   => 'bg-white text-ink-900 hover:bg-ink-100',
    ];
    $sizes = [
        'sm' => 'text-[13px] px-[13px] py-[7px]',
        'md' => 'text-[15px] px-[18px] py-[10px]',
        'lg' => 'text-[18px] px-6 py-[13px]',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']).($block ? ' w-full' : '');
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}>{{ $slot }}</button>
@endif

{{--
  Tread Mark · Button — Oswald uppercase action control.
  <x-treadmark.button>Log Rotation</x-treadmark.button>
  <x-treadmark.button variant="secondary"><x-treadmark.icon name="plus" class="w-4 h-4"/> Add Tire</x-treadmark.button>
  <x-treadmark.button variant="danger" size="sm">Retire</x-treadmark.button>
  Variants: primary · secondary · ghost · danger · inverse.  Sizes: sm · md · lg.
--}}
