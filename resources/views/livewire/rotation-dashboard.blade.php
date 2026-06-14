<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Replacement alerts --}}
            @if ($this->replacementAlerts->isNotEmpty())
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex items-start gap-2">
                        <x-phosphor-warning-duotone class="w-5 h-5 text-red-500 shrink-0 mt-0.5" />
                        <div>
                            <h3 class="font-semibold text-red-800 text-sm mb-1">Tires nearing replacement</h3>
                            <ul class="space-y-1">
                                @foreach ($this->replacementAlerts as $alert)
                                    <li class="text-sm text-red-700">
                                        <a href="{{ route('tires.show', $alert['tire']) }}" class="font-bold hover:underline">
                                            {{ $alert['tire']->label }}
                                        </a>
                                        ({{ $alert['current_position']?->label() ?? '—' }})
                                        — ≈ {{ number_format($alert['projected_miles']) }} miles to 2/32"
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Last rotation card --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Last Rotation</h3>
                    @if ($this->latestRotation)
                        <div class="flex flex-col sm:flex-row sm:items-end gap-4">
                            <div>
                                <div class="text-3xl font-bold text-blue-700">
                                    {{ $this->latestRotation->rotated_on->format('M j, Y') }}
                                </div>
                                <div class="text-gray-500 mt-1">
                                    {{ number_format($this->latestRotation->odometer) }} miles
                                </div>
                            </div>
                            <div class="text-gray-400 text-sm sm:ml-6 self-start sm:self-auto">
                                {{ $this->daysSinceRotation }} day{{ $this->daysSinceRotation === 1 ? '' : 's' }} ago
                            </div>
                            <div class="sm:ml-auto flex gap-3">
                                <a href="{{ route('rotations.edit', $this->latestRotation->id) }}"
                                   class="text-sm text-gray-500 hover:text-gray-800 hover:underline">
                                    Edit
                                </a>
                                <a href="{{ route('rotations.prepare') }}"
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                    New Rotation
                                </a>
                            </div>
                        </div>
                        @if ($this->latestRotation->note)
                            <p class="mt-3 text-sm text-gray-600 italic">{{ $this->latestRotation->note }}</p>
                        @endif
                    @else
                        <div class="text-gray-400 text-sm">No rotations recorded yet.</div>
                        <a href="{{ route('rotations.prepare') }}"
                           class="mt-3 inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                            Record First Rotation
                        </a>
                    @endif
                </div>
            </div>

            {{-- Quick links --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <a href="{{ route('reports.by-position') }}"
                   class="bg-white shadow-sm rounded-lg p-4 hover:shadow-md transition-shadow text-center">
                    <x-phosphor-chart-bar-duotone class="w-8 h-8 text-blue-500 mx-auto mb-1" />
                    <div class="text-sm font-medium text-gray-700">Wear by Position</div>
                </a>
                <a href="{{ route('reports.by-tire') }}"
                   class="bg-white shadow-sm rounded-lg p-4 hover:shadow-md transition-shadow text-center">
                    <x-phosphor-tire-duotone class="w-8 h-8 text-blue-500 mx-auto mb-1" />
                    <div class="text-sm font-medium text-gray-700">Wear by Tire</div>
                </a>
                <a href="{{ route('tires.index') }}"
                   class="bg-white shadow-sm rounded-lg p-4 hover:shadow-md transition-shadow text-center">
                    <x-phosphor-list-duotone class="w-8 h-8 text-blue-500 mx-auto mb-1" />
                    <div class="text-sm font-medium text-gray-700">Manage Tires</div>
                </a>
            </div>

        </div>
    </div>
</div>
