@props([
    'title'    => null,
    'maxWidth' => '5xl',
    'noindex'  => false,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <x-seo :title="$title" :noindex="$noindex" />

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
