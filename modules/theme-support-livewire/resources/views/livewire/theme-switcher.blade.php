<div class="theme-switcher">
    @if (session()->has('message'))
        <p role="status">{{ session('message') }}</p>
    @endif
    <details>
        <summary>{{ __('Theme: :theme', ['theme' => $availableThemes[$currentTheme]['display_name'] ?? ucfirst($currentTheme)]) }}</summary>
        <ul role="list">
            @foreach ($availableThemes as $themeKey => $themeData)
                @continue(($themeData['type'] ?? null) === 'shared')
                <li>
                    <button type="button" wire:click="switchTheme('{{ $themeKey }}')" @if ($currentTheme === $themeKey) aria-current="true" @endif>
                        {{ $themeData['display_name'] ?? ucfirst($themeKey) }}
                    </button>
                </li>
            @endforeach
        </ul>
    </details>
</div>
