<button {{ $attributes->merge(['type' => 'button', 'class' => 'cs-btn cs-btn--ghost']) }}>
    {{ $slot }}
</button>
