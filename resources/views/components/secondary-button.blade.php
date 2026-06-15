<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-2 bg-white border border-ink-200 rounded-control font-semibold text-xs text-ink-700 uppercase tracking-wider2 shadow-sm hover:bg-ink-50 hover:border-ink-300 focus:outline-none focus:ring-4 focus:ring-blaze-500/40 disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
