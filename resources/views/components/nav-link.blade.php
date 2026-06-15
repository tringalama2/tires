@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-blaze-500 text-sm font-medium leading-5 text-ink-900 focus:outline-none focus:border-blaze-600 transition duration-150 ease-in-out'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-ink-500 hover:text-ink-800 hover:border-ink-200 focus:outline-none focus:text-ink-800 focus:border-ink-200 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
