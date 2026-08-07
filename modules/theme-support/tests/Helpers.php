<?php

use Liberu\PackageTestbench\PackageTestCase as TestCase;

/**
 * Write a throwaway theme manifest and return its path.
 *
 * Two suites use this, and `ThemeManagerCoverageBranchesTest` sorts before
 * `ThemeManifestCoverageTest`, so defining it inside either one makes the other
 * depend on file load order. `autoload-dev.files` loads it before any test runs.
 * It cannot live in `tests/Pest.php`: `scripts/migrate-testbench` rewrites that
 * file for a package with no TestCase of its own, which is how the definition
 * was lost in the first place.
 *
 * @param  array<string, mixed>  $changes  Manifest overrides; a null value removes the key.
 */
function writeCoverageTheme(array $changes = [], bool $asset = true): string
{
    $dir = sys_get_temp_dir().'/liberu-theme-'.bin2hex(random_bytes(5));
    mkdir($dir, 0777, true);
    if ($asset) {
        file_put_contents($dir.'/theme.css', '');
    }
    $data = array_replace([
        'name' => 'covered', 'display_name' => 'Covered', 'version' => '1.0.0',
        'provider' => TestCase::class, 'type' => 'shared', 'parent' => '',
        'optimized_for' => [], 'tested_with' => [], 'required_capabilities' => ['one'],
        'optional_capabilities' => ['two'], 'supports' => [],
        'assets' => ['css' => ['theme.css'], 'js' => []],
    ], $changes);
    foreach ($changes as $key => $value) {
        if ($value === null) {
            unset($data[$key]);
        }
    }
    file_put_contents($dir.'/theme.json', json_encode($data, JSON_THROW_ON_ERROR));

    return $dir.'/theme.json';
}

/**
 * Write a throwaway theme *package* — the directory, not the manifest inside it.
 *
 * `ThemeDiscovery` takes package directories and dedupes them against the tracked
 * tree by `realpath`, so its tests need a path they can both hand to the discovery
 * and `rename()` into a tracked root. Every call makes a distinct directory, which
 * is what lets one test put two different packages behind the same theme name.
 */
function coverageThemePackage(string $name): string
{
    $dir = dirname(writeCoverageTheme(['name' => $name]));

    // Discovery rejects a package without a composer.json, and rejects one whose
    // type is not liberu-theme or whose extra.liberu.name disagrees with the
    // manifest — that pair is what the installer computes the install path from.
    file_put_contents($dir.'/composer.json', json_encode([
        'name' => 'liberusoftware/theme-'.$name,
        'type' => 'liberu-theme',
        'extra' => ['liberu' => ['name' => $name]],
    ], JSON_THROW_ON_ERROR));

    return $dir;
}
