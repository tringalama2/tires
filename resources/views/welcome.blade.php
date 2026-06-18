<x-public-layout>
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
</x-public-layout>
