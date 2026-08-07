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
- `publish-components` creates or subtree-publishes configured components and the meta repository. Use `--only` to target one kind.
- `submit-packagist.php` creates missing Packagist packages and refreshes existing package metadata.
- `fleet` fans a command across the component repositories once they are the source of truth — `status`, `clone`, `run`, `commit`, and a separate `tag` that only ever acts on an explicit `--only` list. This is what replaces `publish-components` after the flip; the two are opposites, one pushing the monorepo out and the other working checkouts that are already authoritative.
- `measure-coverage` resolves each package from nothing and runs its own suite under pcov, writing `storage/app/coverage.tsv` — the evidence a repository sets its coverage threshold from.

All commands are non-interactive and fail on errors. GitHub operations require an
authenticated `gh` CLI. Packagist operations read `PACKAGIST_USERNAME` and
`PACKAGIST_API_TOKEN` from the environment; never commit either credential.
