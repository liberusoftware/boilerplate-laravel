<?php

namespace Liberu\Localization\Contracts;

interface TranslationProviderRegistry
{
    public function register(TranslationProvider $provider): void;

    public function get(string $name): TranslationProvider;

    /** @return array<string, TranslationProvider> */
    public function all(): array;
}
