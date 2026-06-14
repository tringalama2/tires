<div x-data="{ show: false }" class="relative inline-flex items-center">
    <button
        type="button"
        @mouseenter="show = true"
        @mouseleave="show = false"
        @focus="show = true"
        @blur="show = false"
        @touchstart.prevent="show = !show"
        class="inline-flex items-center text-red-500 hover:text-red-700 focus:outline-none min-w-[44px] min-h-[44px] justify-center"
        aria-label="Uneven wear warning"
    >
        <x-phosphor-warning-circle-duotone class="w-4 h-4" />
    </button>
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-64 bg-gray-900 text-white text-xs rounded-lg px-3 py-2 shadow-lg z-50 pointer-events-none"
        role="tooltip"
    >
        <p class="font-semibold mb-1">Uneven wear (inner vs outer)</p>
        <p>Likely causes: low tire pressure or alignment drift. Check pressure (target 30 PSI) and consider alignment inspection.</p>
        <div class="absolute top-full left-1/2 -translate-x-1/2 border-4 border-transparent border-t-gray-900"></div>
    </div>
</div>
