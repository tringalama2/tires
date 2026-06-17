<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm text-ink-500 mb-1">
            <a href="{{ route('vehicles.setuptires.index', $vehicle) }}" class="hover:text-blaze-500 transition-colors">Setup tires</a>
            <x-treadmark.icon name="caret-right" class="w-3.5 h-3.5 text-ink-300"/>
            <span class="text-ink-900 font-medium">{{ $position->label() }}</span>
        </div>
        <h2 class="font-display font-semibold uppercase tracking-wide text-[18px] text-ink-900">
            Add tire &mdash; {{ $position->label() }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl mx-auto px-4 sm:px-6">
            <div class="bg-white border border-ink-100 rounded-card shadow-tm-sm overflow-hidden">

                {{-- Card header --}}
                <div class="flex items-center gap-3 px-5 py-4 border-b border-ink-100">
                    <div class="w-11 h-11 rounded-control bg-blaze-500 flex items-center justify-center flex-none">
                        <span class="font-display font-bold uppercase text-white text-[15px] tracking-wide">{{ $position->value }}</span>
                    </div>
                    <div>
                        <div class="font-display font-semibold uppercase tracking-wide text-[16px] text-ink-900">{{ $position->label() }}</div>
                        <div class="text-sm text-ink-500">Record the starting tread — we'll track wear from here.</div>
                    </div>
                </div>

                <x-forms.tires :vehicle="$vehicle" :tirePosition="$position" :existingTire="$existingTire"/>
            </div>
        </div>
    </div>
</x-app-layout>
