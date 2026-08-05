# Handoff — conformance steps 2–4

**Repo:** `/home/tom/code/boilerplate-laravel`
**Branch:** `docs/conformance-plan`
**Date:** 2026-08-05
**Sibling checkout:** `/home/tom/code/package-testbench` (its own repo, clean, pushed)

## Read first, do not re-derive

1. **`docs/CONFORMANCE.md`** — the plan and the record. Steps 0, 2 and 3 each end in a
   "**Step N is done**" block stating what was actually found, including where the plan was
   wrong. §5's opening paragraphs carry the corrections. Read those blocks before believing
   any earlier prose in the same document.
2. **`git log 8a341dbc..HEAD`** — nine commits covering steps 2 and 3. Each message states
   why, not what.
3. **`CLAUDE.md`** — updated for the new enablement and testing model. It is current.

## State

**Steps 0, 2, 3 are done. Step 4 is roughly half done and blocked.**

`git status` is clean apart from `scripts/migrate-testbench`, added by this session and
uncommitted (see *Uncommitted* below).

**Nine commits are unpushed.** `origin/docs/conformance-plan` is at `8a341dbc`; local `HEAD`
is `2b5b6987`. Nothing is lost, but a fresh clone will not see steps 2–3. No PR exists.

### What is live outside this repo

| Where | State |
| --- | --- |
| 44 package repos + 4 theme repos | all tagged **1.1.0**, all matching this tree |
| `liberusoftware/package-testbench` | **1.2.0** tagged and pushed |
| `liberusoftware/.github` | three reusable workflows live under `.github/workflows/` |
| Packagist | **9 packages missing** — this is the blocker |

## The blocker

Nine packages are 404 on Packagist: `package-testbench` and the eight renamed or created in
step 2 (`application`, `identity-core`, `identity-core-filament`, `localization-core`,
`localization-core-livewire`, `module-manager-filament`, `sessions-devices-filament`,
`theme-support-livewire`).

Step 4 needs every package to `require-dev` `package-testbench` and resolve it **standalone**,
so this blocks the whole fleet migration. The user chose "register them on Packagist" over
adding `repositories` entries, then reported they are waiting on **maintainer access to the
`liberusoftware` Packagist namespace**. That wait is external and nothing in the repo moves it.

**If the access does not arrive**, the agreed fallback is a `repositories` vcs entry in each
package's `composer.json`. It unblocks immediately and costs a cleanup release wave later.
Confirm with the user before switching — they picked the other option deliberately.

The user holds a Packagist API token. **It must never be pasted into the session, including
via the `!` prefix** — that writes it into the transcript. Registration is theirs to do at
<https://packagist.org/packages/submit> or in their own terminal.

## Traps that cost time this session

- **`modules/` is Composer output.** `vendor/composer/installed.json` owns the autoload map and
  was written from each package's *published* `composer.json`. File contents are read live, but
  renaming a class or package makes the host unbootable until the wave is published. Verified
  twice. §5 documents it; the practical rule is that the gate for a rename is each package's
  **own** suite, never the host's.
- **Publishing mirrors all 44 packages, not the ones you changed.** Three untouched packages
  went out stale this session and one was a regression that reached GitHub. Sweeping only
  changed packages is how it slipped through. Sweep everything: `scripts/audit-divergence`, or
  loop `composer update && vendor/bin/pest` per package.
- **A reconciliation not committed to the tracked source does not hold.** §3.1 records fixing
  `module-manager` upstream; the monorepo drifted back and re-published the break.
- **`composer.json` `version` fields are load-bearing here.** `ModuleValidator` compares each
  manifest against `Composer\InstalledVersions`, so a bumped manifest fails the boot until the
  package is published, tagged **and** `composer update`d. Expect a red host between those.
- **A tag whose commit predates a fix is worse than no tag.** Composer resolves the tag, not
  `main`. Bumping and re-tagging is what closed the §6.2 gate.
- **`gh api` prints a 404 body on stdout and exits non-zero.** Capturing stdout alone reads
  "Not Found" as a SHA. Test the exit status. This produced 25 bogus `TAG_CONFLICT`s.
- **`gh api` listings lag pushes by seconds.** Verify with `git ls-remote`, not the contents API.
- **`rsync -an` prints nothing without `-v`.** A "verification" without it proves nothing; this
  produced a false pass before being caught.
- **No SSH key on this machine.** `gh` is authenticated over https and `gh auth setup-git` has
  been run. `scripts/publish-components` now follows `gh config get git_protocol`.
- **Pest reports a test with zero assertions as risky.** An assertion that early-returns needs
  `expect(...)->not->toThrow(...)` so the negative case is real.

## Uncommitted

`scripts/migrate-testbench` — the fleet migration, written and **piloted but not run fleet-wide**.
Moved out of the scratchpad deliberately: WSL wipes `/tmp` on restart. It is idempotent. Commit
it whether or not step 4 proceeds.

Piloted on `analytics-core` with a temporary path repository: 6 passed — 3 of its own plus the
3 shipped boundary tests, replacing the 2 hand-written files it stops shipping. The pilot was
reverted.

## Next

1. **Commit `scripts/migrate-testbench`.** One commit, no dependencies.
2. **Push the branch** — nine commits of finished work exist only locally.
3. **When Packagist resolves** (`curl -o /dev/null -w '%{http_code}' https://repo.packagist.org/p2/liberusoftware/package-testbench.json` returns 200):
   run `scripts/migrate-testbench`, then sweep every package standalone, then publish and tag
   the wave. Expect the host red between the manifest bump and `composer update`.
4. **Then delete the 7 host architecture rules** §3.8 schedules for the move. They were
   deliberately kept in step 3 because the testbench had no equivalents; **1.2.0 now implements
   all seven**, so the deletion is finally safe. `tests/Architecture/ModuleBoundariesTest.php`
   holds 15 rules; the survivors are the whole-graph ones — theme parents resolving, Composer
   owning every autoload boundary, cross-package namespace dependencies, one Composer vendor,
   and the two enablement rules.
5. **Open tasks unrelated to step 4:** move `SearchService`'s demo-shaped `searchPosts`/
   `searchGroups` and their two controller actions into `search-demo`; adopt the messaging and
   blog tests preserved at `33550079` into their repositories at step 5; the documentation PR
   for the `liberusoftware/` vs `liberu/` vendor disagreement (§3.2).

## Suggested skills

- **`mattpocock-skills:implement`** — the mode this work has run in. It resumes from
  `docs/CONFORMANCE.md` and the step blocks.
- **`superpowers:systematic-debugging`** — if a package suite goes red after migration. The
  failures here have consistently had a cause one layer below the symptom.
- **`mattpocock-skills:wayfinder`** — only if the destination changes. Map
  [#612](https://github.com/liberusoftware/boilerplate-laravel/issues/612) is closed, 15/15.

**Do not** invoke `/handoff` into `/tmp`. `CLAUDE.md` overrides that skill's default: handoffs
live in `docs/handoffs/`, because WSL wipes `/tmp` exactly when the next session needs it.

## Working agreements observed this session

- Verify before asserting. Mutation-test every new rule — this session dropped two rules that
  passed only because a runtime guard fired first, and caught one "verification" that had been
  reading empty output as success.
- Outward-facing steps get confirmed before they run.
- Commit messages carry the why and state corrections plainly. No Claude attribution footers.
