<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center px-5 py-3 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-200 focus:outline-none btn-luxury-secondary']) }}>
    {{ $slot }}
</button>
