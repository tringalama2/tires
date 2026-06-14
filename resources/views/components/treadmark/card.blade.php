@props([
    'tone' => 'default',
    'pad' => '5',
    'interactive' => false,
    'flat' => false,
    'title' => null,
    'header' => null,
    'footer' => null,
])
@php
    $surfaces = [
        'default' => 'bg-white border-ink-100 '.($flat ? '' : 'shadow-tm-sm'),
        'sunken'  => 'bg-ink-50 border-ink-100',
        'inverse' => 'bg-ink-900 border-ink-600 text-white shadow-tm-md',
    ];
    $pads = ['0' => 'p-0', '3' => 'p-3', '4' => 'p-4', '5' => 'p-5', '6' => 'p-6'];
    $wrap = 'rounded-card border overflow-hidden transition '.($surfaces[$tone] ?? $surfaces['default'])
        .($interactive ? ' cursor-pointer hover:shadow-tm-lg hover:-translate-y-0.5' : '');
    $headBorder = $tone === 'inverse' ? 'border-ink-600' : 'border-ink-100';
@endphp

<div {{ $attributes->merge(['class' => $wrap]) }}>
    @if ($title || $header)
        <div class="flex items-center justify-between gap-3 px-[18px] py-4 border-b {{ $headBorder }}">
            @if ($header){{ $header }}@else
                <span class="font-display font-semibold uppercase tracking-wide text-[16px]">{{ $title }}</span>
            @endif
        </div>
    @endif
    <div class="{{ $pads[$pad] ?? 'p-5' }}">{{ $slot }}</div>
    @if ($footer)
        <div class="px-[18px] py-3.5 border-t {{ $headBorder }} {{ $tone === 'inverse' ? '' : 'bg-ink-50' }}">{{ $footer }}</div>
    @endif
</div>

{{--
  Tread Mark · Card — base surface. tone: default·sunken·inverse. pad: 0·3·4·5·6.
  <x-treadmark.card title="Wear by position">…</x-treadmark.card>
  <x-treadmark.card tone="inverse" :pad="6">…</x-treadmark.card>
  Pass :header / :footer slots for custom chrome; :interactive for hover-lift.
--}}
