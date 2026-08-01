<?php

namespace Liberu\Foundation\Theme\Manifests;

use Liberu\Foundation\Theme\Exceptions\InvalidTheme;

final readonly class ThemeManifest
{
    private function __construct(public string $path, private array $data) {}

    public static function fromFile(string $path): self
    {
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
        foreach (['name', 'display_name', 'version', 'provider', 'type', 'parent', 'optimized_for', 'tested_with', 'required_capabilities', 'optional_capabilities', 'supports', 'assets'] as $key) {
            if (! array_key_exists($key, $data)) {
                throw new InvalidTheme("Theme manifest [{$path}] is missing [{$key}].");
            }
        }if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $data['name'])) {
            throw new InvalidTheme('Theme name is invalid.');
        }if (! in_array($data['type'], ['public', 'portal', 'admin', 'shared'], true)) {
            throw new InvalidTheme("Theme [{$data['name']}] has an invalid type.");
        }foreach (['css', 'js'] as $kind) {
            foreach ((array) ($data['assets'][$kind] ?? []) as $asset) {
                if (! is_string($asset) || str_starts_with($asset, '/') || str_contains($asset, '..') || ! is_file(dirname($path).'/'.$asset)) {
                    throw new InvalidTheme("Theme [{$data['name']}] references an invalid {$kind} asset.");
                }
            }
        }

        return new self(dirname($path), $data);
    }

    public function name(): string
    {
        return $this->data['name'];
    }

    public function displayName(): string
    {
        return $this->data['display_name'];
    }

    public function version(): string
    {
        return $this->data['version'];
    }

    public function provider(): string
    {
        return $this->data['provider'];
    }

    public function type(): string
    {
        return $this->data['type'];
    }

    public function parent(): ?string
    {
        return is_string($this->data['parent']) && $this->data['parent'] !== '' ? $this->data['parent'] : null;
    }

    public function assets(string $type): array
    {
        return array_values((array) ($this->data['assets'][$type] ?? []));
    }

    public function requiredCapabilities(): array
    {
        return array_values((array) ($this->data['required_capabilities'] ?? []));
    }

    public function optionalCapabilities(): array
    {
        return array_values((array) ($this->data['optional_capabilities'] ?? []));
    }

    public function toArray(): array
    {
        return $this->data + ['label' => $this->displayName(), 'path' => $this->path];
    }
}
