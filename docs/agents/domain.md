# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- **`CONTEXT.md`** at the repo root, or
- **`CONTEXT-MAP.md`** at the repo root if it exists — it points at one `CONTEXT.md` per context. Read each one relevant to the topic.
- **`docs/adr/`** — read ADRs that touch the area you're about to work in.

If any of these files don't exist, **proceed silently**. Don't flag their absence; don't suggest creating them upfront. The `/domain-modeling` skill (reached via `/grill-with-docs` and `/improve-codebase-architecture`) creates them lazily when terms or decisions actually get resolved.

## File structure

This repo is **single-context**: one root glossary, one ADR directory, covering the host and
all `modules/` and `themes/` packages.

```
/
├── CONTEXT.md
├── docs/adr/
│   ├── 0001-modules-are-not-auto-discovered.md
│   └── 0002-filament-ui-lives-in-companion-packages.md
├── modules/
└── themes/
```

If the module packages ever grow their own divergent vocabularies, switch to multi-context by
adding a root `CONTEXT-MAP.md` pointing at per-package `modules/<name>/CONTEXT.md` files, with
`modules/<name>/docs/adr/` for package-scoped decisions and root `docs/adr/` for system-wide ones.

## Use the glossary's vocabulary

When your output names a domain concept (in an issue title, a refactor proposal, a hypothesis, a test name), use the term as defined in `CONTEXT.md`. Don't drift to synonyms the glossary explicitly avoids.

If the concept you need isn't in the glossary yet, that's a signal — either you're inventing language the project doesn't use (reconsider) or there's a real gap (note it for `/domain-modeling`).

## Flag ADR conflicts

If your output contradicts an existing ADR, surface it explicitly rather than silently overriding:

> _Contradicts ADR-0007 (event-sourced orders) — but worth reopening because…_
