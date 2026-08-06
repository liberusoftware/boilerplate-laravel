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

## 1.0.4 - 2026-08-01

- Ship the standalone Testbench metadata and provider test suite in package releases.

## 1.0.1 - 2026-08-01

- Preserve the empty view override directory in packaged releases.

## 1.0.0 - 2026-08-01

- Add shared accessible layout, semantic tokens, resilience states, and progressive asset entry points.
