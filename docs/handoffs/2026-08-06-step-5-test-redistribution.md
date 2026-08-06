# Handoff — conformance step 5

**Repo:** `/home/tom/code/boilerplate-laravel` · **Branch:** `docs/conformance-plan` (pushed)
**Sibling checkout:** `/home/tom/code/package-testbench` (clean, pushed, **1.7.0**)
**Date:** 2026-08-06

Supersedes `2026-08-05-step-4-testbench-migration.md`, whose blocker is gone and whose
next-steps are done.

## Read first, do not re-derive

1. **`docs/CONFORMANCE.md`** — the "**Step N is done**" blocks state what was actually found,
   including where the plan was wrong. Step 4's block and the step 5 block above §5 are current.
2. **`git log 62df7c11..HEAD`** — six commits, each stating why.
3. **`CLAUDE.md`** — current for the testbench, the actor and `tests/.gitkeep`.

## State

**Steps 0, 2, 3, 4 done. Step 5 started.** Host: 248 passed, 12 skipped. All 44 package suites
green standalone. §6.2 zero-diff gate green against a real install. Branch pushed; **no PR**.

Moved so far: `settings` (3 tests), `localization-core` (locale middleware),
`localization-core-livewire` (language switcher).

## Traps this session added to the list

- **An empty `tests/` directory does not publish.** 34 packages shipped in 1.2.0 with no `tests/`
  at all; Pest aborts before any suite with `The test directory [tests] does not exist`. The
  44-package sweep passed because the directory was still on disk locally. Fixed with a tracked
  `tests/.gitkeep`. **A fleet sweep does not prove what the tarball contains — only a reinstall does.**
  This is the second time the same class of error got through.
- **Do not tag before something has run the shipped artifact.** testbench 1.3.0 and 1.4.0 went out
  with a boundary suite that could not load.
- **A package overriding `defineEnvironment()` must call `parent::defineEnvironment($app)`** or it
  loses the application key `PackageTestCase` sets (testbench 1.7.0).
- **The host goes red between a manifest bump and `composer update`** — `ModuleValidator` compares
  the manifest against `InstalledVersions`. Expected, not a fault.
- **WSL DNS drops under parallel Composer.** Sweep at `-P 2` and retry; `scripts/publish-components`
  and the sweep helpers are in the session scratchpad, the sweep loop is worth keeping.

## Next

1. **Task #8 — move `SearchService::searchPosts()`/`searchGroups()` and the two `search-api`
   controller actions into `search-demo`.** This is the next real piece of work and it is a
   three-package refactor, not a move: `searchAll()` aggregates over the types, so the honest
   design is a searcher registry in `search` that `search-demo` registers into — which is exactly
   what §3.4 objected to being hardcoded. `search`'s own tests follow it, not the other way round.
2. **Task #9** — adopt the messaging and blog tests preserved at `33550079` into their repositories.
3. **Themes cannot join step 5 until theme discovery goes through Composer.** `ThemeManager*` and
   `ThemeSwitcherTest` need installed themes, but discovery reads `base_path('themes')` rather than
   `InstalledVersions` the way `ModuleDiscovery` does, and Testbench's base path is its own skeleton.
   Fixing that also closes the `theme-support` §4 row, where three successive guards had to be
   loosened to make the package bootable at all.
4. **Package CI has not been observed green.** Runs on `liberusoftware/module-*` have been queued
   for over an hour. Every suite passes locally with the same command CI runs, but nobody has seen
   the workflows themselves pass since the migration. Check before assuming.

## Working agreements observed

- Verify before asserting; mutation-test a new rule.
- Outward-facing steps get confirmed, and the plan gets corrected in writing when it is wrong.
- No Claude attribution footers.
