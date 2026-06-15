@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'border-ink-200 focus:border-blaze-500 focus:ring-blaze-500/40 rounded-control shadow-sm']) !!}>
