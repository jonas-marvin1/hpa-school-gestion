<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-hpa-orange border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-opacity-90 focus:bg-opacity-90 active:bg-orange-800 focus:outline-none focus:ring-2 focus:ring-hpa-orange focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
