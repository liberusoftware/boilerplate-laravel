# Liberu Meta Repository Scripts

Generic release and maintenance automation for Composer-based meta repositories
whose modules, themes, and shared tooling live in independent GitHub repositories.

```bash
composer require --dev liberusoftware/boilerplate-scripts
```

Copy `.liberu-meta.json` from a meta repository and customize its organization,
genre, repository, component paths, prefixes, and additional Composer packages.

- `setup.sh` performs a repeatable local or production install without destructive migrations.
- `update.sh` updates dependencies and validates the project; `--release 1.2.3` commits, tags, pushes, and creates a GitHub release after successful checks.
- `submit-packagist.php` creates missing Packagist packages and refreshes existing package metadata.
- `fleet` fans a command across the component repositories once they are the source of truth — `status`, `clone`, `run`, `commit`, and a separate `tag` that only ever acts on an explicit `--only` list. It replaced `publish-components`, which rsynced a monorepo *into* its component repositories — the opposite direction, and meaningless once those repositories are the source.
- `set-coverage-thresholds` writes each measured figure into that package's `tests.yml`, ratcheting only upward — a package that has lost coverage keeps its threshold and is reported.
- `measure-coverage` resolves each package from nothing and runs its own suite under pcov, writing `storage/app/coverage.tsv` — the evidence a repository sets its coverage threshold from.
- `measure-phpstan` finds the highest PHPStan level each package's `src/` passes, by bisection over a standalone resolution, writing `storage/app/phpstan.tsv`. Levels are monotone, so four runs settle it instead of eleven.
- `set-phpstan-levels` writes each measured level into that package's `tests.yml` as the reusable workflow's `phpstan-level` input, ratcheting only upward and preserving the existing `coverage-threshold`.

All commands are non-interactive and fail on errors. GitHub operations require an
authenticated `gh` CLI. Packagist operations read `PACKAGIST_USERNAME` and
`PACKAGIST_API_TOKEN` from the environment; never commit either credential.
