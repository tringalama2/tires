<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-seo noindex />

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

    <div class="w-full max-w-sm bg-ink-800 border border-ink-600 rounded-card shadow-tm-lg overflow-hidden text-ink-100">
        <div class="px-6 py-7">
            {{ $slot }}
        </div>
    </div>

    <p class="mt-8 text-ink-400 text-xs tracking-wide">Every rotation, on the record.</p>

    <a href="/" wire:navigate class="mt-4 inline-flex items-center gap-1.5 text-xs text-ink-500 hover:text-ink-300 transition-colors">
        <x-treadmark.icon name="arrow-left" class="w-3 h-3" />
        Back to home
    </a>
</div>

@livewireScripts
</body>
</html>
