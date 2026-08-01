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

All commands are non-interactive and fail on errors. GitHub operations require an
authenticated `gh` CLI. Packagist operations read `PACKAGIST_USERNAME` and
`PACKAGIST_API_TOKEN` from the environment; never commit either credential.
