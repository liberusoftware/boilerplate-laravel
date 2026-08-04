<?php
/**
 * PROTOTYPE - throwaway. One command: php run.php [vendorPrefix]
 * Exercises PackageRoot + BoundaryAssertions against every real package.
 * Testbench boot is NOT exercised (no network to install it).
 */
declare(strict_types=1);

spl_autoload_register(function (string $c): void {
    $p = __DIR__.'/package-testbench/src/'.substr($c, strrpos($c, '\\') + 1).'.php';
    if (is_file($p)) require $p;
});

// shim standing in for PHPUnit\Framework\Assert
final class Assert {
    public static array $fail = [];
    public static function assertSame($e, $a, string $m = ''): void {
        if ($e !== $a) self::$fail[] = $m ?: 'expected '.json_encode($e).' got '.json_encode($a);
    }
    public static function assertContains($n, array $h, string $m = ''): void {
        if (!in_array($n, $h, true)) self::$fail[] = $m ?: json_encode($n).' not in '.json_encode($h);
    }
    public static function assertTrue($c, string $m = ''): void { if ($c !== true) self::$fail[] = $m ?: 'not true'; }
}
class_alias(Assert::class, 'PHPUnit\\Framework\\Assert');

require __DIR__.'/package-testbench/src/PackageRoot.php';
require __DIR__.'/package-testbench/src/BoundaryAssertions.php';

use Liberu\PackageTestbench\BoundaryAssertions;
use Liberu\PackageTestbench\PackageRoot;

$vendor = $argv[1] ?? 'liberu/';
$ref = new ReflectionClass(BoundaryAssertions::class);
$repo = '/home/tom/code/boilerplate-laravel';
$roots = array_merge(glob("$repo/modules/*", GLOB_ONLYDIR), glob("$repo/themes/*", GLOB_ONLYDIR));

printf("vendor prefix under test: %s\n\n", $vendor);
$pass = $failed = 0; $report = [];
foreach ($roots as $root) {
    Assert::$fail = [];
    $name = basename($root);
    try {
        // discovery: can it find the package and read the manifest at all?
        $found = PackageRoot::locate($root.'/src');
        if ($found !== $root) Assert::$fail[] = "discovery resolved to $found";
        $m = PackageRoot::manifest($root);
        // metadata consistency, with the vendor prefix injected
        $c = PackageRoot::composer($root);
        $declared = array_filter($c['require'] ?? [], fn($v,$k) => str_starts_with($k,$vendor), ARRAY_FILTER_USE_BOTH);
        Assert::assertContains($c['type'], ['liberu-module','liberu-theme'], "type={$c['type']}");
        Assert::assertSame($m['version'] ?? null, $c['version'] ?? null, 'version mismatch');
        Assert::assertSame($m['name'] ?? null, $c['extra']['liberu']['name'] ?? null, 'installer name mismatch');
        Assert::assertSame($declared, $m['requires']['packages'] ?? [], 'declared deps mismatch');
    } catch (Throwable $e) {
        Assert::$fail[] = 'EXCEPTION: '.$e->getMessage();
    }
    if (Assert::$fail) { $failed++; $report[$name] = Assert::$fail; } else { $pass++; }
}
printf("passed %d, failed %d\n", $pass, $failed);
foreach ($report as $n => $f) printf("  %-30s %s\n", $n, implode(' | ', array_slice($f,0,2)));
