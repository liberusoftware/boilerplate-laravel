# PROTOTYPE — throwaway

Answers [What does `liberu/package-testbench` own?](https://github.com/liberusoftware/boilerplate-laravel/issues/615).

Not production code. Do not merge.

    php run.php liberu/          # all packages measured against the target vendor
    php run.php liberusoftware/  # against the current vendor

- `package-testbench/` — the stub: `PackageRoot` (manifest discovery), `PackageTestCase` (Testbench base), `BoundaryAssertions`, and the shipped `tests/Boundary` suite.
- `search/` — domain package migrated in **Option B** style (zero boundary files, phpunit points into vendor).
- `identity-filament/` — presentation package migrated in **Option A** style (explicit opt-in test).
- `run.php` — offline harness. The container has no network, so `orchestra/testbench` could not be installed; this exercises discovery and the assertions only.
