#!/usr/bin/env bash
set -euo pipefail

release="false"
version=""
config="${META_CONFIG:-.liberu-meta.json}"

usage() {
    cat <<'EOF'
Usage: update.sh [--release VERSION] [--config FILE]

Updates locked PHP and Node dependencies and runs validation, tests, and the
frontend build. --release commits changes, creates an annotated tag, pushes it,
and creates a GitHub release only after every check succeeds.
EOF
}

while (($#)); do
    case "$1" in
        --release) release="true"; shift; version="${1:?--release requires VERSION}" ;;
        --config) shift; config="${1:?--config requires a value}" ;;
        --help|-h) usage; exit 0 ;;
        *) printf 'Unknown argument: %s\n' "$1" >&2; usage >&2; exit 2 ;;
    esac
    shift
done

command -v git >/dev/null || { printf 'git is required.\n' >&2; exit 1; }
command -v composer >/dev/null || { printf 'composer is required.\n' >&2; exit 1; }
root="$(git rev-parse --show-toplevel)"
cd "$root"
composer validate --strict
composer update --with-all-dependencies --no-interaction

if [[ -f package-lock.json ]]; then
    command -v npm >/dev/null || { printf 'npm is required.\n' >&2; exit 1; }
    npm update
    npm run build
fi

if composer run-script --list | grep -qE '^  test '; then
    composer test
elif [[ -x vendor/bin/pest ]]; then
    vendor/bin/pest
fi

[[ "$release" == "true" ]] || { printf 'Updates validated. Review and commit the worktree changes.\n'; exit 0; }
[[ "$version" =~ ^v?[0-9]+\.[0-9]+\.[0-9]+([.-][0-9A-Za-z.-]+)?$ ]] || { printf 'Release must be a semantic version.\n' >&2; exit 2; }
command -v gh >/dev/null || { printf 'gh is required to create a release.\n' >&2; exit 1; }
tag="${version#v}"
git diff --quiet && git diff --cached --quiet || { git add -A; git commit -m "Release $tag"; }
git tag -a "$tag" -m "Release $tag"
git push origin HEAD
git push origin "$tag"
gh release create "$tag" --generate-notes --verify-tag
