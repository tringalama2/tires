@use(App\Enums\TirePosition)
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display font-semibold uppercase tracking-wide text-[18px] text-ink-900">
            Setup tires
        </h2>
        <p class="text-sm text-ink-500 mt-0.5">{{ $vehicle->yearMakeModel }} &middot; <span class="italic">{{ $vehicle->nickname }}</span></p>
    </x-slot>

    <div class="py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 space-y-4">

            @if (session('status'))
                <x-treadmark.alert tone="success">{{ session('status') }}</x-treadmark.alert>
            @endif

            {{-- Hint --}}
            <div class="flex items-start gap-3 bg-white border border-ink-100 rounded-card p-4">
                <span class="flex-none w-8 h-8 rounded-control bg-blaze-50 text-blaze-500 flex items-center justify-center">
                    <x-treadmark.icon name="info-fill" class="w-4 h-4"/>
                </span>
                <p class="text-sm text-ink-500 leading-relaxed">
                    <span class="font-semibold text-ink-900">Add one tire per position.</span>
                    Record the brand, model, and starting tread depth — we'll track wear from this baseline at every rotation.
                </p>
            </div>

            {{-- Position list card --}}
            @php
                $allPositions = [
                    TirePosition::FrontLeft->value  => ['enum' => TirePosition::FrontLeft,  'tire' => $frontLeftTire,  'label' => 'Front Left'],
                    TirePosition::FrontRight->value => ['enum' => TirePosition::FrontRight, 'tire' => $frontRightTire, 'label' => 'Front Right'],
                    TirePosition::RearLeft->value   => ['enum' => TirePosition::RearLeft,   'tire' => $rearLeftTire,   'label' => 'Rear Left'],
                    TirePosition::RearRight->value  => ['enum' => TirePosition::RearRight,  'tire' => $rearRightTire,  'label' => 'Rear Right'],
                    TirePosition::Spare->value      => ['enum' => TirePosition::Spare,      'tire' => $spareTire,      'label' => 'Spare'],
                ];
                $visiblePositions = $vehicle->tire_count == 5
                    ? $allPositions
                    : array_filter($allPositions, fn($k) => $k !== TirePosition::Spare->value, ARRAY_FILTER_USE_KEY);
                $filled = collect($visiblePositions)->filter(fn($p) => $p['tire'] !== null)->count();
                $total  = count($visiblePositions);
                $allDone = $filled === $total;
                $setupRotation = $vehicle->rotations()->setup()->with('placements')->first();
                $placements = $setupRotation?->placements->keyBy('to_position') ?? collect();
            @endphp

            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">

                {{-- Header + progress --}}
                <div class="flex items-center justify-between gap-3 px-5 py-4 border-b border-ink-100">
                    <span class="font-display font-semibold uppercase tracking-wide text-[15px] text-ink-900">Tire positions</span>
                    <span class="inline-flex items-center font-mono text-[12px] font-semibold px-2.5 py-1 rounded-pill
                        {{ $allDone ? 'bg-fern-100 text-fern-600' : 'bg-blaze-50 text-blaze-500' }}">
                        {{ $filled }} of {{ $total }} added
                    </span>
                </div>
                <div class="h-1 bg-ink-100">
                    <div class="h-1 bg-blaze-500 transition-all duration-300" style="width: {{ $total > 0 ? round($filled / $total * 100) : 0 }}%"></div>
                </div>

                {{-- Rows --}}
                <ul class="divide-y divide-ink-100">
                    @foreach($visiblePositions as $posValue => $meta)
                        @php $tire = $meta['tire']; $position = $meta['enum']; @endphp
                        <li class="flex items-center gap-4 px-5 py-4">

                            {{-- Position tag --}}
                            <div class="flex flex-col items-center gap-1 flex-none">
                                <x-treadmark.position-tag :position="$posValue" size="md" :active="(bool)$tire"/>
                                <span class="font-mono text-[10px] uppercase tracking-caps text-ink-400">{{ explode(' ', $meta['label'])[0] }}</span>
                            </div>

                            {{-- Tire info or empty state --}}
                            <div class="flex-1 min-w-0">
                                @if($tire)
                                    <div class="font-semibold text-[14px] text-ink-900 truncate">
                                        <span class="font-mono">{{ $tire->label }}</span>
                                        @if($tire->brand || $tire->model)
                                            <span class="font-normal text-ink-500">&middot; {{ trim($tire->brand . ' ' . $tire->model) }}</span>
                                        @endif
                                    </div>
                                    @if($tire->size)
                                        <div class="font-mono text-[12px] text-ink-400 mt-0.5">{{ $tire->size }}</div>
                                    @endif
                                @else
                                    <span class="text-[14px] text-ink-300">No tire added yet</span>
                                @endif
                            </div>

                            {{-- Tread or Add button --}}
                            @if($tire)
                                @php $tread = $placements->get($posValue)?->tread_center; @endphp
                                @if($tread !== null)
                                    <div class="flex flex-col items-end gap-1.5 flex-none w-24">
                                        <x-treadmark.tread-gauge :depth="$tread" label="" size="sm" :show-value="true"/>
                                    </div>
                                @endif
                                <div class="flex-none w-6 h-6 rounded-full bg-fern-100 flex items-center justify-center">
                                    <x-treadmark.icon name="check" class="w-3.5 h-3.5 text-fern-600"/>
                                </div>
                            @else
                                <a href="{{ route('vehicles.setuptires.create', ['vehicle' => $vehicle, 'tirePosition' => $posValue]) }}"
                                   class="flex-none inline-flex items-center gap-1.5 font-display font-semibold uppercase tracking-wider2 text-[12px]
                                          text-blaze-500 bg-blaze-50 border border-blaze-300 hover:bg-blaze-100
                                          px-3 py-1.5 rounded-control transition-colors">
                                    <x-treadmark.icon name="plus" class="w-3.5 h-3.5"/>
                                    Add tire
                                </a>
                                <div class="flex-none w-6 h-6 rounded-full border-2 border-dashed border-ink-200 flex items-center justify-center">
                                    <x-treadmark.icon name="dots-three" class="w-3 h-3 text-ink-300"/>
                                </div>
                            @endif
                        </li>
                    @endforeach
                </ul>

                {{-- Footer action --}}
                <div class="flex items-center justify-between gap-3 px-5 py-4 border-t border-ink-100 bg-ink-50">
                    <span class="text-sm text-ink-400">
                        @if($allDone)
                            All tires are set up. Ready to log your first rotation.
                        @else
                            {{ $total - $filled }} position{{ $total - $filled !== 1 ? 's' : '' }} still need{{ $total - $filled === 1 ? 's' : '' }} a tire.
                        @endif
                    </span>
                    @if($allDone)
                        <x-treadmark.button href="{{ route('dashboard', $vehicle) }}" variant="primary" size="sm">
                            Go to dashboard
                        </x-treadmark.button>
                    @else
                        <x-treadmark.button variant="primary" size="sm" disabled class="opacity-40 pointer-events-none">
                            Go to dashboard
                        </x-treadmark.button>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
