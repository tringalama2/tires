@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-blaze-500 text-start text-base font-medium text-ink-900 bg-blaze-50 focus:outline-none focus:text-ink-900 focus:bg-blaze-50 focus:border-blaze-600 transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-ink-600 hover:text-ink-800 hover:bg-ink-50 hover:border-ink-200 focus:outline-none focus:text-ink-800 focus:bg-ink-50 focus:border-ink-200 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
