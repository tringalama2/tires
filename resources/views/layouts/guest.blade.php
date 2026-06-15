<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Tread·Mark') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans text-ink-900 antialiased">

{{-- Pine-graphite auth surface with chevron-tread texture feel --}}
<div class="min-h-screen flex flex-col items-center justify-center bg-ink-900 px-4 py-12"
     style="background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.02) 0, rgba(255,255,255,0.02) 1px, transparent 0, transparent 50%); background-size: 20px 20px;">

    {{-- Logo --}}
    <a href="/" wire:navigate class="mb-8">
        <x-treadmark.logo tone="onDark" />
    </a>

    <div class="w-full max-w-sm bg-ink-800 border border-ink-600 rounded-card shadow-tm-lg overflow-hidden">
        <div class="px-6 py-7">
            {{ $slot }}
        </div>
    </div>

    <p class="mt-8 text-ink-400 text-xs tracking-wide">Every rotation, on the record.</p>
</div>

@livewireScripts
</body>
</html>
