#!/usr/bin/env python3
"""Publish tracked module/theme snapshots, semantic releases, and Packagist entries."""
import io
import json
import os
from pathlib import Path
import re
import subprocess
import tarfile
import tempfile
from urllib.error import HTTPError
from urllib.request import Request, urlopen


SEMVER = re.compile(r"v?(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$")


def git(*args, cwd=None):
    return subprocess.check_output(['git', *args], cwd=cwd).decode().strip()


def request(url, payload=None, token=None, missing_ok=False):
    headers = {'Accept': 'application/json', 'User-Agent': 'Liberu-Package-Sync'}
    if token:
        headers['Authorization'] = f'Bearer {token}'
    data = None
    if payload is not None:
        headers['Content-Type'] = 'application/json'
        data = json.dumps(payload).encode()
    try:
        with urlopen(Request(url, data=data, headers=headers), timeout=60) as response:
            return json.load(response)
    except HTTPError as error:
        if missing_ok and error.code == 404:
            return None
        # Do not expose request headers or credentials in error output.
        raise RuntimeError(f'{url}: HTTP {error.code}') from None


def github(path, payload=None, missing_ok=False):
    return request('https://api.github.com/' + path, payload,
                   os.environ['GH_TOKEN'], missing_ok)


def version(value):
    match = SEMVER.fullmatch(value)
    return tuple(map(int, match.groups())) if match else None


def next_version(tags, declared, messages):
    versions = [v for tag in tags if (v := version(tag)) is not None]
    if not versions:
        return 'v' + '.'.join(map(str, version(declared) or (1, 0, 0)))
    base = max(versions)
    explicit = version(declared)
    if explicit and explicit > base:
        result = explicit
    elif re.search(r'(?m)^\w+(?:\([^\n)]*\))?!:|BREAKING[ -]CHANGE:', messages):
        result = (base[0] + 1, 0, 0)
    elif re.search(r'(?m)^feat(?:\([^\n)]*\))?:', messages):
        result = (base[0], base[1] + 1, 0)
    else:
        result = (base[0], base[1], base[2] + 1)
    return 'v' + '.'.join(map(str, result))


def repository_name(path):
    kind, name = path.split('/')
    prefix = 'module-' if kind == 'modules' else 'theme-'
    configuration = Path('.liberu-meta.json')
    components = json.loads(configuration.read_text()).get('components', {}) if configuration.exists() else {}
    configured_prefix = components.get(kind, {}).get('repositoryPrefix')
    name = configured_prefix + name if configured_prefix is not None else (name if name.startswith(prefix) else prefix + name)
    if not re.fullmatch(r'[a-z0-9][a-z0-9.-]*', name):
        raise ValueError(f'Invalid repository name: {name}')
    return name


def changed_packages(before, head):
    if before and set(before) != {'0'}:
        paths = git('diff', '--name-only', '--no-renames', '-z', before, head,
                    '--', 'modules/', 'themes/').split('\0')
    else:
        paths = git('ls-tree', '-r', '--name-only', '-z', head,
                    '--', 'modules/', 'themes/').split('\0')
    return sorted({'/'.join(p.split('/')[:2]) for p in paths
                   if len(p.split('/')) >= 3})


def packagist(package, repository):
    existing = request(f'https://packagist.org/packages/{package}.json', missing_ok=True)
    if existing:
        registered = existing['package']['repository'].removesuffix('.git').rstrip('/')
        if registered != repository:
            raise RuntimeError(f'{package} already belongs to {registered}')
    endpoint = 'update-package' if existing else 'create-package'
    response = request(f'https://packagist.org/api/{endpoint}',
                       {'repository': repository},
                       os.environ['PACKAGIST_USERNAME'] + ':' + os.environ['PACKAGIST_API_TOKEN'])
    if response.get('status') != 'success':
        raise RuntimeError(f'Packagist {endpoint} failed for {package}')


def publish(path, head, before):
    name = repository_name(path)
    destination = f'liberusoftware/{name}'
    url = f'https://github.com/{destination}'
    raw = git('show', f'{head}:{path}/composer.json')
    metadata = json.loads(raw)
    package = metadata.get('name', '')
    if not re.fullmatch(r'liberusoftware/[a-z0-9_.-]+', package):
        raise ValueError(f'{path}: expected a liberusoftware Composer package name')
    repo = github(f'repos/{destination}', missing_ok=True)
    if repo is None:
        repo = github('orgs/liberusoftware/repos', {
            'name': name, 'private': False, 'auto_init': False,
            'description': metadata.get('description', name)[:350]})
    if repo['private'] or repo.get('archived'):
        raise RuntimeError(f'{destination} must be public and active for Packagist')
    branch = repo['default_branch']
    with tempfile.TemporaryDirectory() as temporary:
        target = Path(temporary) / 'package'
        git('clone', '--quiet', url + '.git', str(target))
        git('config', 'user.name', 'github-actions[bot]', cwd=target)
        git('config', 'user.email', '41898282+github-actions[bot]@users.noreply.github.com', cwd=target)
        has_head = subprocess.run(['git', 'rev-parse', '--verify', 'HEAD'], cwd=target,
                                  stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL).returncode == 0
        previous = json.loads((target / 'composer.json').read_text()) if (target / 'composer.json').exists() else {}
        if previous and previous.get('name') != package:
            raise RuntimeError(f'{destination} contains a different Composer package')
        archive = subprocess.check_output(['git', 'archive', f'{head}:{path}'])
        # Only tracked paths are exported; installed vendor files and local secrets stay local.
        git('rm', '-r', '--ignore-unmatch', '.', cwd=target)
        with tarfile.open(fileobj=io.BytesIO(archive)) as source:
            source.extractall(target, filter='data')
        declared = metadata.get('version', '')
        if 'version' in previous:
            metadata['version'] = previous['version']
        else:
            metadata.pop('version', None)
        composer = target / 'composer.json'
        original = composer.read_text()
        # Preserve the source formatting while normalizing only the generated version.
        def write_metadata():
            current = json.loads(original)
            if current == metadata:
                composer.write_text(original)
            else:
                composer.write_text(json.dumps(metadata, indent=2, ensure_ascii=False) + '\n')
            if 'version' in metadata:
                for filename in ('module.json', 'theme.json'):
                    manifest_path = target / filename
                    if not manifest_path.is_file():
                        continue
                    manifest = json.loads(manifest_path.read_text())
                    if manifest.get('version') != metadata['version']:
                        manifest['version'] = metadata['version']
                        manifest_path.write_text(json.dumps(manifest, indent=2, ensure_ascii=False) + '\n')
        write_metadata()
        git('add', '-A', cwd=target)
        changed = subprocess.run(['git', 'diff', '--cached', '--quiet'], cwd=target).returncode != 0
        tags = git('tag', '--list', cwd=target).splitlines()
        explicit_bump = version(declared) is not None and version(declared) > (version(previous.get('version', '')) or (0, 0, 0))
        if changed or not has_head or explicit_bump:
            history = [f'{before}..{head}'] if before and set(before) != {'0'} else [head]
            messages = git('log', '--format=%B', *history, '--', path)
            tag = next_version(tags, declared, messages)
            if 'version' in json.loads(original):
                metadata['version'] = tag[1:]
                write_metadata()
            git('add', '-A', cwd=target)
            git('commit', '-m', f'chore: sync {path} from {os.environ["GITHUB_REPOSITORY"]}@{head}\n\nPackage-Release: {tag}', cwd=target)
        else:
            # A rerun can finish a release/Packagist request after a successful push.
            message = git('log', '-1', '--format=%B', cwd=target)
            match = re.search(r'(?m)^Package-Release: (v\d+\.\d+\.\d+)$', message)
            head_tags = git('tag', '--points-at', 'HEAD', cwd=target).splitlines()
            stable_head_tags = [t for t in head_tags if version(t)]
            tag = match[1] if match else (max(stable_head_tags, key=version) if stable_head_tags
                                        else next_version(tags, declared, ''))
        sha = git('rev-parse', 'HEAD', cwd=target)
        if tag in tags:
            if git('rev-list', '-1', tag, cwd=target) != sha:
                raise RuntimeError(f'{destination}: {tag} already points to another commit')
        else:
            git('tag', '-a', tag, '-m', f'Release {tag}', cwd=target)
        # Reject concurrent updates rather than overwriting another project's push.
        git('push', '--atomic', 'origin', f'HEAD:refs/heads/{branch}', f'refs/tags/{tag}', cwd=target)
        if github(f'repos/{destination}/releases/tags/{tag}', missing_ok=True) is None:
            github(f'repos/{destination}/releases', {
                'tag_name': tag, 'name': tag,
                'body': f'Synchronized {path} from https://github.com/{os.environ["GITHUB_REPOSITORY"]}/commit/{head}.'})
        packagist(package, url)
        print(f'{path}: published {destination} {tag}')


def main():
    for key in ('GH_TOKEN', 'PACKAGIST_USERNAME', 'PACKAGIST_API_TOKEN'):
        if not os.environ.get(key):
            raise RuntimeError(f'Missing required secret: {key}')
    head = git('rev-parse', 'HEAD')
    before = os.environ.get('SYNC_BEFORE', '')
    failures = []
    for path in changed_packages(before, head):
        if not Path(path, 'composer.json').is_file():
            print(f'{path}: removed or no composer.json; leaving published repository intact')
            continue
        try:
            publish(path, head, before)
        except Exception as error:
            print(f'::error::{path}: {error}')
            failures.append(path)
    if failures:
        raise RuntimeError('Failed packages: ' + ', '.join(failures))


if __name__ == '__main__':
    main()
