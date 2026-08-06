<?php

namespace Liberu\Foundation\Localization\Translation;

use InvalidArgumentException;
use Liberu\Localization\Contracts\TranslationProvider;
use Liberu\Localization\Contracts\TranslationProviderRegistry;

final class TranslationRegistry implements TranslationProviderRegistry
{
    /** @var array<string, TranslationProvider> */
    private array $providers = [];

    public function register(TranslationProvider $provider): void
    {
        if (isset($this->providers[$provider->name()])) {
            throw new InvalidArgumentException("Duplicate translation provider [{$provider->name()}].");
        }
        $this->providers[$provider->name()] = $provider;
    }

    public function get(string $name): TranslationProvider
    {
        return $this->providers[$name] ?? throw new InvalidArgumentException("Unknown translation provider [{$name}].");
    }

    public function all(): array
    {
        return $this->providers;
    }
}
