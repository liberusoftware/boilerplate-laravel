#!/usr/bin/env bash
set -euo pipefail

environment="local"
run_migrations="false"
build_assets="true"

usage() {
    cat <<'EOF'
Usage: setup.sh [--environment local|production] [--migrate] [--no-build]

Installs a PHP/Node meta repository deterministically. It copies .env.example
only when .env is absent, never destroys a database, and is safe to rerun.
EOF
}

while (($#)); do
    case "$1" in
        --environment) shift; environment="${1:?--environment requires a value}" ;;
        --migrate) run_migrations="true" ;;
        --no-build) build_assets="false" ;;
        --help|-h) usage; exit 0 ;;
        *) printf 'Unknown argument: %s\n' "$1" >&2; usage >&2; exit 2 ;;
    esac
    shift
done

[[ "$environment" =~ ^(local|production)$ ]] || { printf 'Invalid environment.\n' >&2; exit 2; }
command -v php >/dev/null || { printf 'PHP is required.\n' >&2; exit 1; }
command -v composer >/dev/null || { printf 'Composer is required: https://getcomposer.org/download/\n' >&2; exit 1; }
root="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$root"
[[ -f composer.json ]] || { printf 'composer.json was not found.\n' >&2; exit 1; }

[[ -f .env || ! -f .env.example ]] || cp .env.example .env
composer_args=(install --no-interaction --prefer-dist)
[[ "$environment" == "production" ]] && composer_args+=(--no-dev --classmap-authoritative)
composer "${composer_args[@]}"

if [[ -f package-lock.json ]]; then
    command -v npm >/dev/null || { printf 'npm is required by package-lock.json.\n' >&2; exit 1; }
    npm ci
    [[ "$build_assets" == "false" ]] || npm run build
fi

if [[ -f artisan ]]; then
    [[ -n "${APP_KEY:-}" ]] || php artisan key:generate --no-interaction
    [[ "$run_migrations" == "false" ]] || php artisan migrate --force --no-interaction
fi

printf 'Setup completed for %s.\n' "$environment"
