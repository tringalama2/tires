<div>
    <x-slot name="header">
        <div class="flex items-baseline justify-between gap-4">
            <div>
                <h1 class="font-display font-semibold uppercase text-2xl tracking-wider text-ink-900">Dashboard</h1>
                <p class="text-ink-500 text-sm mt-0.5">
                    {{ session('vehicle')?->year }} {{ session('vehicle')?->make }} {{ session('vehicle')?->model }}
                    · {{ session('vehicle')?->tires()->count() }} tires in rotation
                </p>
            </div>
            <x-treadmark.button href="{{ route('rotations.prepare') }}" size="sm">
                <x-treadmark.icon name="arrows-clockwise" class="w-4 h-4" />
                Log Rotation
            </x-treadmark.button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- Replacement alerts --}}
            @if ($this->replacementAlerts->isNotEmpty())
                <x-treadmark.alert tone="danger" title="Tires nearing replacement">
                    <ul class="mt-1 space-y-1">
                        @foreach ($this->replacementAlerts as $alert)
                            <li>
                                <a href="{{ route('tires.show', $alert['tire']) }}"
                                   class="font-semibold underline underline-offset-2">{{ $alert['tire']->label }}</a>
                                ({{ $alert['current_position']?->label() ?? '—' }})
                                — ≈ {{ number_format($alert['projected_miles']) }} miles to 2/32"
                            </li>
                        @endforeach
                    </ul>
                </x-treadmark.alert>
            @endif

            {{-- Stat row --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                @if ($this->latestRotation)
                    <x-treadmark.card class="col-span-1">
                        <x-treadmark.stat-tile
                            label="Last rotation"
                            value="{{ $this->latestRotation->rotated_on->format('M j, Y') }}"
                            sub="{{ $this->daysSinceRotation }} day{{ $this->daysSinceRotation === 1 ? '' : 's' }} ago"
                        />
                    </x-treadmark.card>
                    <x-treadmark.card class="col-span-1">
                        <x-treadmark.stat-tile
                            label="Odometer"
                            value="{{ number_format($this->latestRotation->odometer) }}"
                            unit="mi"
                            mono
                        />
                    </x-treadmark.card>
                @endif

                {{-- Fastest-wear inverse tile --}}
                @php
                    $fastestAlert = $this->replacementAlerts->first();
                @endphp
                @if ($fastestAlert)
                    <x-treadmark.card tone="inverse" class="col-span-2 sm:col-span-1">
                        <x-treadmark.stat-tile
                            tone="inverse"
                            label="Needs attention"
                            value="{{ $fastestAlert['tire']->label }}"
                            sub="≈ {{ number_format($fastestAlert['projected_miles']) }} mi left"
                        />
                    </x-treadmark.card>
                @endif
            </div>

            {{-- Rotation note --}}
            @if ($this->latestRotation?->note)
                <p class="text-sm text-ink-500 italic px-1">{{ $this->latestRotation->note }}</p>
            @endif

            {{-- No rotations yet --}}
            @if (! $this->latestRotation)
                <x-treadmark.card>
                    <div class="py-8 text-center">
                        <x-treadmark.icon name="arrows-clockwise" class="w-10 h-10 text-ink-300 mx-auto mb-3" />
                        <p class="text-ink-500 mb-4">No rotations logged yet. Add your first to start tracking wear.</p>
                        <x-treadmark.button href="{{ route('rotations.prepare') }}">
                            Log First Rotation
                        </x-treadmark.button>
                    </div>
                </x-treadmark.card>
            @endif

            {{-- Quick links --}}
            <div class="grid grid-cols-3 gap-4">
                <a href="{{ route('reports.by-position') }}"
                   class="group flex flex-col items-center gap-2 bg-white border border-ink-100 rounded-card p-5 shadow-tm-sm hover:shadow-tm-md hover:-translate-y-px transition-all duration-150 text-center">
                    <x-treadmark.icon name="chart-bar" class="w-7 h-7 text-ink-400 group-hover:text-blaze-500 transition-colors" />
                    <span class="text-sm font-semibold text-ink-700 group-hover:text-ink-900">Wear by Position</span>
                </a>
                <a href="{{ route('reports.by-tire') }}"
                   class="group flex flex-col items-center gap-2 bg-white border border-ink-100 rounded-card p-5 shadow-tm-sm hover:shadow-tm-md hover:-translate-y-px transition-all duration-150 text-center">
                    <x-treadmark.icon name="tire" class="w-7 h-7 text-ink-400 group-hover:text-blaze-500 transition-colors" />
                    <span class="text-sm font-semibold text-ink-700 group-hover:text-ink-900">Wear by Tire</span>
                </a>
                <a href="{{ route('tires.index') }}"
                   class="group flex flex-col items-center gap-2 bg-white border border-ink-100 rounded-card p-5 shadow-tm-sm hover:shadow-tm-md hover:-translate-y-px transition-all duration-150 text-center">
                    <x-treadmark.icon name="list" class="w-7 h-7 text-ink-400 group-hover:text-blaze-500 transition-colors" />
                    <span class="text-sm font-semibold text-ink-700 group-hover:text-ink-900">Manage Tires</span>
                </a>
            </div>

        </div>
    </div>
</div>
