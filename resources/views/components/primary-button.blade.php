<button {{ $attributes->merge(['type' => 'submit', 'class' => 'text-xs btn-dark-gray']) }}>
    {{ $slot }}
</button>
