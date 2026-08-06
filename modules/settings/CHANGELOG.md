# Changelog

## 1.3.2 - 2026-08-06

- Split CI into the three workflows `CONFORMANCE.md` §3.9 asks for, rather than one calling all
  three. Only `tests` runs on every push; installing from nothing and resolving the lowest allowed
  dependencies are release questions and now run on tags. One caller meant four jobs per push, and
  a 44-repository publish sweep stalled the organisation's Actions queue.

## 1.3.1 - 2026-08-06

- Require `package-testbench` ^1.7 and call its `defineEnvironment()`, so the suite keeps the
  application key the base case sets.

## 1.3.0 - 2026-08-06

- Adopt the host tests that belong to this package (step 5). They ran in the composition only
  because the base `users` table lives there; `liberusoftware/package-testbench` 1.6.0 now ships
  a test actor, so they run against this package alone.

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

- Add a validated, machine-readable feature catalog for runtime discovery and diagnostics.
- Add standalone tests that keep feature metadata consistent and unique.

## 1.0.0 - 2026-08-01

- Extract typed site settings and their migrations from the host application.
