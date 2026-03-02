<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-6 py-3 font-semibold text-xs uppercase tracking-widest transition ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 btn-luxury-primary']) }}>
    {{ $slot }}
</button>
