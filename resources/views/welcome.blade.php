<x-public-layout>

    {{-- ── HERO ─────────────────────────────────────────────────────────────── --}}
    <section class="flex flex-col items-center justify-center text-center px-6 pt-24 pb-20">

        <div class="mb-6">
            <x-treadmark.logo tone="onDark" :size="64" />
        </div>

        <h1 class="font-display font-bold uppercase text-4xl sm:text-6xl tracking-wider text-white mt-8 max-w-2xl leading-tight">
            Track every tire.<br>
            <span class="text-blaze-400">Catch wear early.</span>
        </h1>

        <p class="mt-6 text-ink-300 text-lg max-w-lg leading-relaxed">
            Log rotations and tread readings across all 5 positions. See which corners wear fastest. Know when to replace before you're stranded.
        </p>

        <div class="mt-10 flex flex-col sm:flex-row items-center gap-4">
            @auth
                <x-treadmark.button href="{{ route('dashboard') }}" size="lg">
                    <x-treadmark.icon name="gauge" class="w-5 h-5" />
                    Go to Dashboard
                </x-treadmark.button>
            @else
                @if (Route::has('register'))
                    <x-treadmark.button href="{{ route('register') }}" size="lg">
                        <x-treadmark.icon name="arrows-clockwise" class="w-5 h-5" />
                        Start tracking free
                    </x-treadmark.button>
                @endif
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 text-sm font-semibold text-ink-300 hover:text-white transition-colors">
                    Already have an account
                    <x-treadmark.icon name="arrow-right" class="w-4 h-4" />
                </a>
            @endauth
        </div>

        {{-- feature pill strip --}}
        <div class="mt-14 flex flex-wrap justify-center gap-2">
            @foreach ([
                ['icon' => 'tire',           'text' => '5-tire rotation'],
                ['icon' => 'chart-bar',      'text' => 'Wear by position'],
                ['icon' => 'gauge',          'text' => 'Tread in 32nds'],
                ['icon' => 'warning-fill',   'text' => 'Replacement alerts'],
                ['icon' => 'calendar-check', 'text' => 'Full history'],
            ] as $pill)
                <span class="inline-flex items-center gap-2 bg-ink-800 border border-ink-700 text-ink-300 text-xs font-medium px-3.5 py-2 rounded-pill">
                    <x-treadmark.icon :name="$pill['icon']" class="w-3.5 h-3.5 text-blaze-400" />
                    {{ $pill['text'] }}
                </span>
            @endforeach
        </div>
    </section>

    {{-- ── HOW IT WORKS ─────────────────────────────────────────────────────── --}}
    <section class="px-6 py-20 border-t border-ink-700/60">
        <div class="max-w-5xl mx-auto">

            <p class="text-xs font-semibold uppercase tracking-widest text-blaze-400 text-center mb-3">How it works</p>
            <h2 class="font-display font-bold uppercase text-2xl sm:text-3xl tracking-wider text-white text-center mb-16">
                Up and running in minutes
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-0 relative">

                {{-- connector line — hidden on mobile --}}
                <div class="hidden sm:block absolute top-10 left-[calc(16.67%+1rem)] right-[calc(16.67%+1rem)] h-px bg-ink-700 z-0"></div>

                @foreach ([
                    ['step' => '01', 'icon' => 'user',              'title' => 'Create your account',  'body' => 'Sign up free. No credit card, no expiry.'],
                    ['step' => '02', 'icon' => 'jeep',              'title' => 'Add your vehicle',      'body' => 'Enter year, make, and model. Choose 4- or 5-tire rotation.'],
                    ['step' => '03', 'icon' => 'arrows-clockwise',  'title' => 'Log a rotation',        'body' => 'Record tread at each position after every rotation. The app tracks the rest.'],
                ] as $step)
                    <div class="relative z-10 flex flex-col items-center text-center px-6 pb-2">
                        <div class="w-20 h-20 rounded-full bg-ink-800 border-2 border-ink-600 flex items-center justify-center mb-5 shadow-tm-md">
                            <x-treadmark.icon :name="$step['icon']" class="w-8 h-8 text-blaze-400" />
                        </div>
                        <p class="font-mono text-xs text-ink-500 uppercase tracking-widest mb-1">Step {{ $step['step'] }}</p>
                        <h3 class="font-display font-bold uppercase tracking-wider text-lg text-white mb-2">{{ $step['title'] }}</h3>
                        <p class="text-ink-400 text-sm leading-relaxed max-w-[220px]">{{ $step['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── FEATURES ─────────────────────────────────────────────────────────── --}}
    <section class="px-6 py-20 border-t border-ink-700/60">
        <div class="max-w-5xl mx-auto">

            <p class="text-xs font-semibold uppercase tracking-widest text-blaze-400 text-center mb-3">Features</p>
            <h2 class="font-display font-bold uppercase text-2xl sm:text-3xl tracking-wider text-white text-center mb-16">
                Built for the driveway, not the dealership
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ([
                    [
                        'icon'  => 'tire',
                        'title' => '5-tire rotation support',
                        'body'  => 'FL · FR · RL · RR · SP — includes the spare in the rotation cycle, exactly how 4Runner owners run it.',
                    ],
                    [
                        'icon'  => 'chart-bar',
                        'title' => 'Wear by position',
                        'body'  => 'See which corner eats tires. Spot alignment or pressure issues before they cost you a set.',
                    ],
                    [
                        'icon'  => 'gauge',
                        'title' => 'Tread depth in 32nds',
                        'body'  => 'Center, inner, and outer groove readings per tire. Visual gauge at a glance — no mental math.',
                    ],
                    [
                        'icon'  => 'warning-fill',
                        'title' => 'Replacement alerts',
                        'body'  => 'Tires approaching the 2/32" legal limit are flagged automatically. No spreadsheet required.',
                    ],
                    [
                        'icon'  => 'calendar-check',
                        'title' => 'Full rotation history',
                        'body'  => 'Every rotation logged with odometer and date. See the complete life of each tire.',
                    ],
                    [
                        'icon'  => 'car-profile',
                        'title' => 'Multi-vehicle ready',
                        'body'  => 'Track multiple vehicles under one account. Switch between them from the dashboard.',
                    ],
                ] as $feature)
                    <div class="bg-ink-800 border border-ink-700 rounded-card p-6 hover:border-ink-500 transition-colors">
                        <div class="w-10 h-10 rounded-control bg-ink-700 flex items-center justify-center mb-4">
                            <x-treadmark.icon :name="$feature['icon']" class="w-5 h-5 text-blaze-400" />
                        </div>
                        <h3 class="font-display font-bold uppercase tracking-wider text-base text-white mb-2">
                            {{ $feature['title'] }}
                        </h3>
                        <p class="text-ink-400 text-sm leading-relaxed">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── BOTTOM CTA ───────────────────────────────────────────────────────── --}}
    @guest
    <section class="px-6 py-20 border-t border-ink-700/60">
        <div class="max-w-lg mx-auto text-center">
            <h2 class="font-display font-bold uppercase text-2xl sm:text-3xl tracking-wider text-white mb-4">
                Free. No limits.
            </h2>
            <p class="text-ink-400 text-base leading-relaxed mb-8">
                Every feature is free while TreadMark builds its user base. Sign up now — your data is yours to keep.
            </p>
            @if (Route::has('register'))
                <x-treadmark.button href="{{ route('register') }}" size="lg">
                    <x-treadmark.icon name="arrows-clockwise" class="w-5 h-5" />
                    Create your free account
                </x-treadmark.button>
            @endif
        </div>
    </section>
    @endguest

</x-public-layout>
