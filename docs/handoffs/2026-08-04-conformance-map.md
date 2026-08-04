# Handoff — Liberu boilerplate conformance effort

**Repo:** `/home/tom/code/boilerplate-laravel` · **Branch:** `chore/composable-package-readiness`
**Date:** 2026-08-04

## Status: the planning map is complete

The wayfinder map
[#612](https://github.com/liberusoftware/boilerplate-laravel/issues/612) is closed — **15/15
tickets**. Its destination was a spec, not a refactor. **Nothing was refactored.**

**Read these first, in order — do not re-derive their contents:**

1. **`docs/CONFORMANCE.md`** (in the repo, **uncommitted**) — the deliverable. Scope, every decision with its enforcement mechanism, the defect register, the step −1→9 migration sequence with gates and rollback.
2. **[The map, #612](https://github.com/liberusoftware/boilerplate-laravel/issues/612)** — Decisions-so-far indexes all 15 tickets; each ticket comment holds the full reasoning and the evidence behind it.

Everything factual about the plan lives in those two places. This document only covers what they
don't: environment state, traps, and what to do next.

## Uncommitted work in the tree

Nothing was committed. `git status` shows:

- `docs/CONFORMANCE.md` — **the deliverable**, needs review then commit.
- `docs/agents/{issue-tracker,triage-labels,domain}.md` — from an earlier `/setup-matt-pocock-skills` run.
- `CLAUDE.md` — has an `## Agent skills` section appended by that same setup.
- `.gitignore`, `public/build/manifest.json` — **modified before this session began**; not ours, leave alone.

Also live: branch **`prototype/package-testbench`** (pushed, unmerged, throwaway — the testbench
stub and two migrated packages; delete once the real testbench is built).

## One decision reversed late — check you have the right version

The vendor decision was taken as `liberu/*` early, then **reversed by the owner to keep
`liberusoftware/*`**. `docs/CONFORMANCE.md` and the map are correct. Individual ticket comments
written before the reversal carry a "Vendor correction" note appended — read those notes, and treat
any bare `liberu/...` package name in an older comment as `liberusoftware/...`.

**Live obligation this created:** `liberusoftware/documentation` mandates `liberu/*` for every
package name it specifies, including `liberu/package-testbench` and `liberu/composer-installer`.
This repo now disagrees *by intent*. A PR to the documentation repo is owed. It is recorded in the
map's Out of scope and in `docs/CONFORMANCE.md` §3.2 and §6 — do not let it evaporate.

## Environment state you are inheriting

**Container DNS was broken and is now fixed.** `aardvark-dns` was healthy but forwarding to a dead
upstream (`169.254.1.1`), so the PHP container could not resolve anything external. Two changes:

- **Persistent:** `podman network update lerd` — dropped `169.254.1.1`, added `10.255.255.254` and `1.1.1.1`. Revert with `--dns-drop`.
- **Runtime:** appended `nameserver 10.255.255.254` to the running `lerd-php85-fpm` `/etc/resolv.conf`. **Resets on container restart**, at which point the persistent config takes over. `podman network reload` and `SIGHUP` do *not* make aardvark re-read config — it only reloads when respawned, which needs every container on the `lerd` network stopped.

**Disk:** `vendor/` now exists at the repo root and in ~45 package directories from the coverage
sweep. All gitignored, several GB. Clear the package ones with:
`find modules themes -maxdepth 2 -name vendor -type d -exec rm -rf {} +`

## Traps that cost time — do not repeat them

**1. `composer install` at the root destroys the committed package source.** `/modules` and
`/themes` are Composer install targets, so a root install reinstalls every package from Packagist
over the tracked files. It deleted **110 tracked files** and modified 92, twice. Recovered both
times with `git checkout -- modules/ themes/ composer.lock`. This is not a bug to fix — it is the
finding that proves §6.2's zero-diff gate is red today. **Assume any Composer operation can clobber
the tree; check `git status` after.**

**2. Never `cd` inside a loop that runs Composer.** The second clobber came from a sweep script
losing its working directory, so `composer update` ran against the repo root. Use
`(cd "$dir" && ...)` subshells *and* verify the directory exists first.

**3. Pest colourises output — raw `grep` on its logs silently fails.** ANSI escapes sit between
`Tests:` and the number. This produced wrong coverage figures *and* two waiter loops that spun for
33 minutes on a condition that had already been met. Always `sed 's/\x1b\[[0-9;]*m//g'` first.

**4. `pgrep -f` matches your own command line.** Repeatedly gave false "still running" answers. Use
a pattern that cannot self-match, or `ps -eo args | grep -v` your own invocation.

**5. `composer update` fails inside every package** — `pestphp/pest-plugin` is blocked because
**0 of 48 packages declare `config.allow-plugins`**. Workaround for measurement only:
`--no-plugins` (then `pest --coverage-text`, since `--coverage` is ambiguous without the plugin).
The real fix is in the packages and is step 4 of the plan.

**6. `gh` needs `-R liberusoftware/boilerplate-laravel`** when the working directory is outside the repo.

## What the next session should probably do

The map is planning-complete, so the next move is a decision about **execution**, which is a
separate effort the map deliberately did not start. Options, roughly in order of value:

1. **Commit the deliverable** — review `docs/CONFORMANCE.md`, commit it and the `docs/agents/` files.
2. **Fix the blocking defects first** — `docs/CONFORMANCE.md` §4 lists eight, none optional. Three are cheap and unblock everything else: `config.allow-plugins` in all 48 packages, `plugin-modifies-install-path` on the installer, and the two architecture rules that have never actually worked.
3. **Execute step −1** — the installer. It is ~50 lines and gates the entire migration.
4. **Raise the documentation PR** (§3.2 obligation above).

Do **not** start the split, the renames, or the repo-first flip without walking `docs/CONFORMANCE.md`
§5 in order. The sequence exists specifically so each package publishes once.

## Suggested skills

- **`mattpocock-skills:wayfinder`** — only if new *decisions* surface. The existing map is closed; a fresh effort means a fresh map, not reopening #612.
- **`superpowers:writing-plans` → `superpowers:executing-plans`** — the natural pair for turning `docs/CONFORMANCE.md` §5 into an executable plan with review checkpoints.
- **`mattpocock-skills:tdd`** — for the defect fixes in §4, especially the installer's path-computation and collision behaviour, which currently has no tests at all and breaks all 40 packages when wrong.
- **`superpowers:using-git-worktrees`** — strongly recommended before any Composer-heavy work, given trap 1. An isolated worktree makes a clobber free to discard.
- **`superpowers:verification-before-completion`** — this effort produced several claims that turned out wrong on inspection (a CI gate of `--min=83` that actually measures 5.6%; twelve architecture rules of which two never worked; "standalone-testable" packages that cannot install standalone). Verify before asserting.
- **`lerd`** — for the local PHP environment; note `lerd doctor` reports 0 failures even with DNS forwarding broken, so it does not prove connectivity.

## Sensitive material

None recorded here. The user mentioned holding a Packagist API token — **it was never shared,
never stored, and is not needed until execution** (steps −1, 0, 7, 8). It belongs in
`~/.config/composer/auth.json` or a GitHub Actions secret, not in a repo or a chat log.
