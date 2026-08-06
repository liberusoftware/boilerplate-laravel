# Handoff — Liberu boilerplate conformance effort

**Repo:** `/home/tom/code/boilerplate-laravel`
**Branch:** `docs/conformance-plan` (branched off `chore/composable-package-readiness`, pushed, **no PR**)
**Date:** 2026-08-04

## Status: planning complete, committed, nothing refactored

The wayfinder map
[#612](https://github.com/liberusoftware/boilerplate-laravel/issues/612) is closed — **15/15
tickets**. Its destination was a spec. **No production code was changed.**

**Read these first, in order — do not re-derive their contents:**

1. **`docs/CONFORMANCE.md`** — the deliverable. Scope, every decision with its enforcement mechanism, the defect register, the step −1→9 migration sequence with gates and rollback.
2. **[The map, #612](https://github.com/liberusoftware/boilerplate-laravel/issues/612)** — Decisions-so-far indexes all 15 tickets; each ticket comment holds the reasoning and the evidence.

Everything factual about the plan lives there. This document covers only what those cannot:
environment state, traps, and what to do next.

## What is on the branch

Commit `29074458` — *Add conformance spec, agent docs and handoff*, 19 files:

- `docs/CONFORMANCE.md`, `docs/handoffs/`, `docs/agents/*.md`
- `CLAUDE.md` — new `## Agent skills` and `### Handoffs` sections
- Agent config: `.claude/`, `.cursor/`, `.gemini/`, `.junie/`, `.vscode/`, `.mcp.json`, `AGENTS.md`, `GEMINI.md`, copilot instructions
- `.gitignore` and `public/build/manifest.json` — **modified before this effort began**, bundled in because the commit was asked to include all changes. Split them out if that was not wanted; nothing depends on the commit yet.

Also live: branch **`prototype/package-testbench`** (pushed, unmerged, throwaway — testbench stub
plus two migrated packages; delete once the real testbench is built).

## One decision reversed late — check you have the right version

The vendor decision was taken as `liberu/*` early, then **reversed by the owner to keep
`liberusoftware/*`**. `docs/CONFORMANCE.md` and the map are correct. Ticket comments written before
the reversal carry a "Vendor correction" note appended — read those notes, and treat any bare
`liberu/...` package name in an older comment as `liberusoftware/...`.

**Live obligation this created:** `liberusoftware/documentation` mandates `liberu/*` for every
package name it specifies, including `liberu/package-testbench` and `liberu/composer-installer`.
This repo now disagrees *by intent*. A PR to the documentation repo is owed — recorded in the map's
Out of scope and in `docs/CONFORMANCE.md` §3.2 and §6. Do not let it evaporate.

## Environment state you are inheriting

**Container DNS was broken and is now fixed.** `aardvark-dns` was healthy but forwarding to a dead
upstream (`169.254.1.1`), so the PHP container could not resolve anything external. Two changes:

- **Persistent:** `podman network update lerd` — dropped `169.254.1.1`, added `10.255.255.254` and `1.1.1.1`. Revert with `--dns-drop`.
- **Runtime:** appended `nameserver 10.255.255.254` to the running `lerd-php85-fpm` `/etc/resolv.conf`. **Resets on container restart**, at which point the persistent config takes over. `podman network reload` and `SIGHUP` do *not* make aardvark re-read config — it only reloads when respawned, which needs every container on the `lerd` network stopped.

**Disk:** `vendor/` exists at the repo root and in ~45 package directories from the coverage sweep.
Gitignored, several GB. Clear the package ones with
`find modules themes -maxdepth 2 -name vendor -type d -exec rm -rf {} +`.

## Traps that cost time — do not repeat them

**1. `composer install` at the root destroys the committed package source.** `/modules` and
`/themes` are Composer install targets, so a root install reinstalls every package from Packagist
over the tracked files — **110 tracked files deleted, 92 modified**, twice. This is not a bug to
fix; it is the finding proving §6.2's zero-diff gate is red today. Assume any Composer operation
can clobber the tree, and check `git status` after.

**2. `git checkout` does not undo the whole clobber.** It restores tracked files but leaves the
**untracked** files the published packages brought in. **103** of them survived — `tests/Pest.php`,
`tests/TestCase.php`, `themes/*/tests/Architecture/PackageMetadataTest.php`, and stray `src/` files
(`ModuleValidationGuard.php`, `AnyTeamRoleLookup.php`, `developer-experience/src/Support/`). They
were within one command of being committed as if they were real work. Full recovery is:

```
git checkout -- modules/ themes/ composer.lock && git clean -fd modules/ themes/
```

**3. Never `cd` inside a loop that runs Composer.** The second clobber came from a sweep script
losing its working directory, so `composer update` ran against the repo root. Use
`(cd "$dir" && ...)` subshells and verify the directory exists first.

**4. Pest colourises output — raw `grep` on its logs silently fails.** ANSI escapes sit between
`Tests:` and the number. This produced wrong coverage figures *and* two waiter loops that spun 33
minutes on a condition already met. Always `sed 's/\x1b\[[0-9;]*m//g'` first.

**5. `pgrep -f` matches your own command line.** Repeatedly gave false "still running" answers. Use
a pattern that cannot self-match.

**6. `composer update` fails inside every package** — `pestphp/pest-plugin` is blocked because
**0 of 48 packages declare `config.allow-plugins`**. Measurement workaround only: `--no-plugins`,
then `pest --coverage-text` (`--coverage` is ambiguous without the plugin). The real fix is step 4
of the plan.

**7. `gh` needs `-R liberusoftware/boilerplate-laravel`** when the working directory is outside the repo.

## What the next session should probably do

The map is planning-complete, so the next move is about **execution**, which the map deliberately
did not start. Roughly in order of value:

1. **Fix the blocking defects** — `docs/CONFORMANCE.md` §4 lists eight, none optional. Three are cheap and unblock everything: `config.allow-plugins` across all 48 packages, `plugin-modifies-install-path` on the installer, and the two architecture rules that have never worked.
2. **Execute step −1** — the installer. ~50 lines, and it gates the entire migration.
3. **Raise the documentation PR** (obligation above).
4. **Decide the fate of `chore/composable-package-readiness`** — this branch was cut from it and the work is docs-only, so it merges cleanly either way.

Do **not** start the split, the renames, or the repo-first flip without walking
`docs/CONFORMANCE.md` §5 in order. The sequence exists so each package publishes once.

## Suggested skills

- **`superpowers:writing-plans` → `superpowers:executing-plans`** — the natural pair for turning `docs/CONFORMANCE.md` §5 into an executable plan with review checkpoints.
- **`mattpocock-skills:tdd`** — for the §4 defect fixes, especially the installer's path computation and collision behaviour: no tests today, and wrong behaviour breaks all 40 packages at once.
- **`superpowers:using-git-worktrees`** — strongly recommended before any Composer-heavy work. Given traps 1 and 2, an isolated worktree makes a clobber free to discard.
- **`superpowers:verification-before-completion`** — this effort produced several confident claims that were wrong on inspection: a CI gate of `--min=83` that actually measures 5.6%; twelve architecture rules of which two never worked; "standalone-testable" packages that cannot install standalone. Verify before asserting.
- **`mattpocock-skills:wayfinder`** — only if new *decisions* surface. #612 is closed; a fresh effort means a fresh map, not reopening it.
- **`lerd`** — for the local PHP environment. Note `lerd doctor` reports 0 failures even with DNS forwarding broken, so it does not prove connectivity.

## Sensitive material

None recorded here. The user mentioned holding a Packagist API token — **it was never shared,
never stored, and is not needed until execution** (steps −1, 0, 7, 8). It belongs in
`~/.config/composer/auth.json` or a GitHub Actions secret, not in a repo or a chat log.
