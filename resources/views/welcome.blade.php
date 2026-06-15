<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tread·Mark — Every rotation, on the record.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-ink-900 text-white">

<div class="min-h-screen flex flex-col"
     style="background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.015) 0, rgba(255,255,255,0.015) 1px, transparent 0, transparent 50%); background-size: 24px 24px;">

    <header class="border-b border-ink-700">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <x-treadmark.logo tone="onDark" />
            <nav class="flex items-center gap-3">
                @auth
                    <x-treadmark.button href="{{ route('dashboard') }}" variant="inverse" size="sm">
                        Dashboard
                    </x-treadmark.button>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-medium text-ink-300 hover:text-white transition-colors">Log in</a>
                    @if (Route::has('register'))
                        <x-treadmark.button href="{{ route('register') }}" variant="primary" size="sm">
                            Get started
                        </x-treadmark.button>
                    @endif
                @endauth
            </nav>
        </div>
    </header>

    <main class="flex-1 flex flex-col items-center justify-center px-6 py-24 text-center">
        <div class="mb-6">
            <x-treadmark.logo tone="onDark" :size="72" />
        </div>

        <h1 class="font-display font-bold uppercase text-4xl sm:text-5xl tracking-wider text-white mt-10 max-w-2xl leading-tight">
            Track every tire.<br>
            <span class="text-blaze-400">Catch wear early.</span>
        </h1>

        <p class="mt-6 text-ink-300 text-lg max-w-xl leading-relaxed">
            Log rotations and tread readings across all 5 positions — FL, FR, RL, RR, and spare.
            See which corners wear fastest. Know when to replace.
        </p>

        <div class="mt-10 flex flex-col sm:flex-row items-center gap-4">
            @auth
                <x-treadmark.button href="{{ route('dashboard') }}" size="lg">
                    <x-treadmark.icon name="gauge" class="w-5 h-5" /> Go to Dashboard
                </x-treadmark.button>
            @else
                <x-treadmark.button href="{{ route('login') }}" size="lg">
                    <x-treadmark.icon name="arrows-clockwise" class="w-5 h-5" /> Log in
                </x-treadmark.button>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 text-sm font-semibold text-ink-300 hover:text-white transition-colors">
                        Create account
                        <x-treadmark.icon name="arrow-right" class="w-4 h-4" />
                    </a>
                @endif
            @endauth
        </div>

        <div class="mt-20 flex flex-wrap justify-center gap-3">
            @foreach ([
                ['icon' => 'tire',           'text' => '5-tire rotation support'],
                ['icon' => 'chart-bar',      'text' => 'Wear by position & tire'],
                ['icon' => 'gauge',          'text' => 'Tread depth in 32nds'],
                ['icon' => 'warning-fill',   'text' => 'Replacement alerts'],
                ['icon' => 'calendar-check', 'text' => 'Full rotation history'],
            ] as $feature)
                <span class="inline-flex items-center gap-2 bg-ink-800 border border-ink-600 text-ink-200 text-sm px-4 py-2 rounded-pill">
                    <x-treadmark.icon :name="$feature['icon']" class="w-4 h-4 text-blaze-400" />
                    {{ $feature['text'] }}
                </span>
            @endforeach
        </div>
    </main>

    <footer class="border-t border-ink-700 py-6 text-center text-ink-500 text-xs tracking-wide">
        Tread·Mark &mdash; Every rotation, on the record.
    </footer>
</div>
</body>
</html>
