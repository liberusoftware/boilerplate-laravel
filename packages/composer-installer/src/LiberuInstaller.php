<?php

namespace Liberu\ComposerInstaller;

use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use InvalidArgumentException;

final class LiberuInstaller extends LibraryInstaller
{
    private array $targets = [];

    public function supports(string $packageType): bool
    {
        return in_array($packageType, ['liberu-module', 'liberu-theme'], true);
    }

    public function getInstallPath(PackageInterface $package): string
    {
        $extra = $package->getExtra();
        $name = $extra['liberu']['name'] ?? null;
        if (! is_string($name) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $name)) {
            throw new InvalidArgumentException("Package [{$package->getPrettyName()}] has an invalid Liberu installer name.");
        }$root = $package->getType() === 'liberu-theme' ? 'themes' : 'modules';
        $target = $root.'/'.$name;
        if (isset($this->targets[$target]) && $this->targets[$target] !== $package->getPrettyName()) {
            throw new InvalidArgumentException("Liberu installer target collision at [{$target}].");
        }$this->targets[$target] = $package->getPrettyName();

        return $target;
    }
}
