<button {{ $attributes->merge(['type' => 'submit', 'class' => 'cs-btn cs-btn--primary']) }}>
    {{ $slot }}
</button>
