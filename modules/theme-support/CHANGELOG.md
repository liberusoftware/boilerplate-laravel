# Changelog

## 1.3.0 - 2026-08-06

- Themes are discovered through Composer as well as the tracked tree, mirroring how modules
  resolve from `InstalledVersions`. A package can now see a theme it depends on, so the three
  guards loosened to make this package bootable — missing themes directory, missing safe
  fallback, missing parent — are all back.
- Split CI into the three workflows `CONFORMANCE.md` §3.9 asks for, rather than one calling all
  three. Only `tests` runs on every push; installing from nothing and resolving the lowest allowed
  dependencies are release questions and now run on tags. One caller meant four jobs per push, and
  a 44-repository publish sweep stalled the organisation's Actions queue.

- Discover themes through Composer as well as the tracked `themes/` tree: every installed
  `liberu-theme` package is found via `InstalledVersions`, the two sources are deduped by
  resolved real path, and a theme is resolved at the path it was discovered at rather than
  under the tracked tree. A package can therefore dev-require a theme and genuinely have one.
- Restore the three guards that were loosened to make this package bootable without a tracked
  tree: discovery refuses an application with no themes at all, the safe fallback theme must be
  installed whatever else is, and an inheritance chain is always walked.

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

- Extract reusable theme discovery and preference behavior from the host.
