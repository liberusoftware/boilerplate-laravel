# Changelog

## 1.2.0 - 2026-08-06

- Move the boundary suites to `liberusoftware/package-testbench`: this repository no longer
  ships a test bootstrap or its own metadata and provider tests, and its `phpunit.xml` points
  at the shipped suite instead. A new boundary rule is now a testbench release rather than a
  change made by hand in every package repository.
- Call the reusable workflows in `liberusoftware/.github` rather than restating CI setup.

## 1.1.0 - 2026-08-05

- Conformance wave: package and namespace renames, manifest categories and `default_enabled`
  settled against `liberusoftware/documentation`. See the host's `docs/CONFORMANCE.md` §3.

## 1.0.6 - 2026-08-01

- Correct Testbench base-path configuration through Laravel's supported application API.

## 1.0.5 - 2026-08-01

- Make standalone Testbench booting independent of the host application's base path.

## 1.0.4 - 2026-08-01

- Add a validated, machine-readable feature catalog for runtime discovery and diagnostics.
- Add standalone tests that keep feature metadata consistent and unique.

## 1.0.4 - 2026-08-01

- Add startup compatibility validation, status diagnostics, and atomic production registry caching.

## 1.0.0 - 2026-08-01

- Add canonical manifest discovery, validation, enablement, dependency sorting, and provider registration.
