@props([
    'title' => null,
    'maxWidth' => '5xl',
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' — Tread·Mark' : 'Tread·Mark — Every rotation, on the record.' }}</title>

    <link rel="icon" href="{{ asset('assets/favicon/favicon.ico') }}" sizes="any">
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/favicon/favicon.svg') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('assets/favicon/favicon-16x16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/favicon/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('assets/favicon/site.webmanifest') }}">
    <meta name="theme-color" content="#0F1410">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-ink-900 text-white">

<div class="min-h-screen flex flex-col"
     style="background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.015) 0, rgba(255,255,255,0.015) 1px, transparent 0, transparent 50%); background-size: 24px 24px;">

    <header class="border-b border-ink-700">
        <div class="max-w-{{ $maxWidth }} mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/"><x-treadmark.logo tone="onDark" /></a>
            <nav class="flex items-center gap-3">
                @auth
                    <x-treadmark.button href="{{ route('dashboard') }}" variant="inverse" size="sm">Dashboard</x-treadmark.button>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-ink-300 hover:text-white transition-colors">Log in</a>
                    @if (Route::has('register'))
                        <x-treadmark.button href="{{ route('register') }}" variant="primary" size="sm">Get started</x-treadmark.button>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    {{ $slot }}

    <footer class="border-t border-ink-700 py-6 text-ink-500 text-xs tracking-wide">
        <div class="max-w-{{ $maxWidth }} mx-auto px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span>&copy; {{ date('Y') }} Tread·Mark. All rights reserved.</span>
            <nav class="flex items-center gap-5">
                <a href="{{ route('terms') }}" class="hover:text-ink-300 transition-colors">Terms</a>
                <a href="{{ route('privacy') }}" class="hover:text-ink-300 transition-colors">Privacy</a>
                <a href="mailto:hello@treadmark.app" class="hover:text-ink-300 transition-colors">Contact</a>
            </nav>
        </div>
    </footer>
</div>

</body>
</html>
