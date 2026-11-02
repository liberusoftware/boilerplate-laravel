#!/usr/bin/env python3
"""Publish tracked module/theme snapshots, semantic releases, and Packagist entries."""
import io
import hashlib
import argparse
from concurrent.futures import ThreadPoolExecutor
import json
import os
from pathlib import Path
import re
import subprocess
import tarfile
import tempfile
import time
from urllib.error import HTTPError
from urllib.request import Request, urlopen
from urllib.parse import urlencode


SEMVER = re.compile(r"v?(0|[1-9]\d*)\.(0|[1-9]\d*)\.(0|[1-9]\d*)$")


def git(*args, cwd=None):
    return subprocess.check_output(['git', *args], cwd=cwd, stderr=subprocess.STDOUT).decode().strip()


class PendingPublication(RuntimeError):
    def __init__(self, package, details):
        super().__init__(f'{package}: approval and required checks are needed for {details["url"]}')
        self.package = package
        self.details = details


def ensure_quality_workflow(target, rejection):
    contexts = [name for name in ('package-quality', 'Package / validate') if f'"{name}"' in rejection]
    if not contexts:
        return
    directory = target / '.github' / 'workflows'
    workflows = list(directory.glob('*.yml')) + list(directory.glob('*.yaml')) if directory.exists() else []
    if workflows:
        return
    context = contexts[0]
    directory.mkdir(parents=True, exist_ok=True)
    (directory / 'package-quality.yml').write_text('''name: Package quality

on:
  pull_request:
  push:
    branches: [main]

permissions:
  contents: read

jobs:
  package-quality:
    name: "''' + context + '''"
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@3d3c42e5aac5ba805825da76410c181273ba90b1 # v7.0.1
      - uses: shivammathur/setup-php@eb7c497e18156a6bbabfef1d3a82760b9eda3962 # 2.40.0
        with:
          php-version: '8.5'
          tools: composer:v2
      - name: Validate Composer metadata
        run: composer validate --no-check-lock --no-check-all
      - name: Lint package PHP
        run: |
          python3 - <<'PYTHON'
          from pathlib import Path
          import subprocess
          for directory in ('src', 'config', 'routes', 'database'):
              for path in Path(directory).rglob('*.php'):
                  subprocess.run(['php', '-l', str(path)], check=True)
          PYTHON
''')
    git('add', '--', '.github', cwd=target)
    git('commit', '-m', 'ci: run the required package validation check', cwd=target)


def publication_pull_request(target, destination, branch, package, path, head, tag):
    signature = source_signature(path, head)
    project = os.environ['GITHUB_REPOSITORY'].split('/')[1]
    topic = f'sync/{project}/{tag}-{signature[:12]}'
    existing = git('ls-remote', 'origin', f'refs/heads/{topic}', cwd=target)
    if existing:
        git('fetch', '--quiet', 'origin', f'refs/heads/{topic}', cwd=target)
        if git('rev-parse', 'FETCH_HEAD^{tree}', cwd=target) != git('rev-parse', 'HEAD^{tree}', cwd=target):
            topic += '-' + git('rev-parse', 'HEAD^{tree}', cwd=target)[:8]
            existing = git('ls-remote', 'origin', f'refs/heads/{topic}', cwd=target)
    if not existing:
        git('push', 'origin', f'HEAD:refs/heads/{topic}', cwd=target)
    query = urlencode({'head': f'liberusoftware:{topic}', 'base': branch, 'state': 'open'})
    pulls = github(f'repos/{destination}/pulls?{query}')
    if pulls:
        pull = pulls[0]
    else:
        pull = github(f'repos/{destination}/pulls', {
            'head': topic, 'base': branch,
            'title': f'Sync {package} {tag} from {project}',
            'body': f'Synchronize `{path}` from https://github.com/{os.environ["GITHUB_REPOSITORY"]}/commit/{head}.\n\n'
                    'The source Composer manifest and host application module discovery were validated before publication. '
                    'This pull request preserves the repository approval and required-check policies.\n\n'
                    f'Package-Release: {tag}'})
    return {'url': pull['html_url'], 'branch': topic, 'version': tag[1:],
            'repository': f'https://github.com/{destination}', 'path': path,
            'source_tree': git('rev-parse', f'{head}:{path}'), 'source_signature': signature}


def request(url, payload=None, token=None, missing_ok=False, method=None):
    headers = {'Accept': 'application/json', 'User-Agent': 'Liberu-Package-Sync'}
    if token:
        headers['Authorization'] = f'Bearer {token}'
    data = None
    if payload is not None:
        headers['Content-Type'] = 'application/json'
        data = json.dumps(payload).encode()
    for attempt in range(2):
        try:
            with urlopen(Request(url, data=data, headers=headers, method=method), timeout=60) as response:
                return json.load(response)
        except HTTPError as error:
            if missing_ok and error.code == 404:
                return None
            limited = error.headers.get('X-RateLimit-Remaining') == '0'
            retry_after = error.headers.get('Retry-After')
            if attempt == 0 and url.startswith('https://api.github.com/') and error.code in (403, 429) and (limited or retry_after):
                try:
                    delay = int(retry_after) if retry_after else int(error.headers['X-RateLimit-Reset']) - int(time.time()) + 1
                except (TypeError, ValueError, KeyError):
                    delay = 60
                delay = max(1, min(delay, 3601))
                print(f'GitHub API rate limit: retrying in {delay} seconds', flush=True)
                while delay > 0:
                    interval = min(delay, 60)
                    time.sleep(interval)
                    delay -= interval
                continue
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
    explicit = version(declared)
    compatible = [v for v in versions if explicit and v[0] == explicit[0]
                  and (explicit[0] != 0 or v[1] == explicit[1])]
    if explicit:
        versions = compatible
    if not versions:
        return 'v' + '.'.join(map(str, version(declared) or (1, 0, 0)))
    base = max(versions)
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
    prefix = configured_prefix if configured_prefix is not None else prefix
    package_file = Path(path, 'composer.json')
    package = json.loads(package_file.read_text()).get('name', '') if package_file.is_file() else ''
    additional = json.loads(configuration.read_text()).get('additionalPackages', {}) if configuration.exists() else {}
    name = additional.get(package, name if name.startswith(prefix) else prefix + name)
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


def source_signature(path, head):
    digest = hashlib.sha256()
    archive = subprocess.check_output(['git', 'archive', f'{head}:{path}'])
    with tarfile.open(fileobj=io.BytesIO(archive)) as source:
        for member in sorted(source.getmembers(), key=lambda item: item.name):
            digest.update(f'{member.name}:{member.mode}:{member.linkname}\0'.encode())
            if not member.isfile():
                continue
            data = source.extractfile(member).read()
            if member.name in ('composer.json', 'module.json', 'theme.json'):
                metadata = json.loads(data)
                metadata.pop('version', None)
                data = json.dumps(metadata, sort_keys=True).encode()
            digest.update(data)
    return digest.hexdigest()


def packagist(package, repository):
    existing = request(f'https://packagist.org/packages/{package}.json', missing_ok=True)
    if existing:
        registered = existing['package']['repository'].removesuffix('.git').rstrip('/')
        if registered != repository:
            relocated = None
            if registered.startswith('https://github.com/'):
                relocated = github('repos/' + registered.removeprefix('https://github.com/'), missing_ok=True)
            if not relocated or relocated.get('html_url') != repository:
                raise RuntimeError(f'{package} already belongs to {registered}')
            edited = request(f'https://packagist.org/api/packages/{package}', {'repository': repository},
                             os.environ['PACKAGIST_USERNAME'] + ':' + os.environ['PACKAGIST_API_TOKEN'], method='PUT')
            if edited.get('status') != 'success':
                raise RuntimeError(f'Packagist repository edit failed for {package}')
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
    registered = request(f'https://packagist.org/packages/{package}.json', missing_ok=True)
    if registered:
        registered_url = registered['package']['repository'].removesuffix('.git').rstrip('/')
        if not registered_url.startswith('https://github.com/liberusoftware/'):
            raise RuntimeError(f'{package} belongs to a repository outside liberusoftware')
        url = registered_url
        destination = url.removeprefix('https://github.com/')
        name = destination.split('/')[1]
    cache = Path('.github/package-repositories.json')
    repositories = json.loads(cache.read_text()) if cache.exists() else {}
    repo = repositories.get(name)
    if repo is None:
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
        previous_release = previous.get('version', '')
        for filename in ('module.json', 'theme.json'):
            previous_manifest = target / filename
            if not previous_release and previous_manifest.exists():
                previous_release = json.loads(previous_manifest.read_text()).get('version', '')
        if previous and previous.get('name') != package:
            raise RuntimeError(f'{destination} contains a different Composer package')
        archive = subprocess.check_output(['git', 'archive', f'{head}:{path}'])
        # Only tracked paths are exported; installed vendor files and local secrets stay local.
        git('rm', '-r', '--ignore-unmatch', '--', '.', ':(exclude).github', cwd=target)
        with tarfile.open(fileobj=io.BytesIO(archive)) as source:
            source.extractall(target, filter='data')
        declared = metadata.get('version', '')
        if not declared:
            for filename in ('module.json', 'theme.json'):
                manifest_file = target / filename
                if manifest_file.exists():
                    declared = json.loads(manifest_file.read_text()).get('version', '')
                    break
        if 'version' in metadata and previous_release:
            metadata['version'] = previous_release
        manifest_version = previous_release or declared
        composer = target / 'composer.json'
        original = composer.read_text()
        # Preserve the source formatting while normalizing only the generated version.
        def write_metadata():
            current = json.loads(original)
            if current == metadata:
                composer.write_text(original)
            else:
                composer.write_text(json.dumps(metadata, indent=2, ensure_ascii=False) + '\n')
            if manifest_version:
                for filename in ('module.json', 'theme.json'):
                    manifest_path = target / filename
                    if not manifest_path.is_file():
                        continue
                    manifest = json.loads(manifest_path.read_text())
                    if manifest.get('version') != manifest_version:
                        manifest['version'] = manifest_version
                        manifest_path.write_text(json.dumps(manifest, indent=2, ensure_ascii=False) + '\n')
        write_metadata()
        git('add', '-A', cwd=target)
        changed = subprocess.run(['git', 'diff', '--cached', '--quiet'], cwd=target).returncode != 0
        tags = git('tag', '--list', cwd=target).splitlines()
        declared_version = version(declared)
        previous_version = version(previous_release)
        incompatible = declared_version and previous_version and (
            declared_version[0] != previous_version[0] or
            (declared_version[0] == 0 and declared_version[1] != previous_version[1]))
        explicit_bump = declared_version and (incompatible or declared_version > (previous_version or (0, 0, 0)))
        if changed or not has_head or explicit_bump:
            history = [f'{before}..{head}'] if before and set(before) != {'0'} else [head]
            messages = git('log', '--format=%B', *history, '--', path)
            # Project-wide breaking releases do not change every library's public API.
            names = {path.split('/')[1], package.split('/')[1]}
            messages = re.sub(r'(?m)^(\w+)\(([^)]+)\)!:',
                              lambda match: match[0] if match[2] in names else f'{match[1]}({match[2]}):', messages)
            messages = re.sub(r'(?m)^BREAKING[ -]CHANGE:.*$|^(\w+)!:',
                              lambda match: f'{match[1]}:' if match[1] else '', messages)
            if version(declared) and version(declared)[0] == 0:
                messages = ''
            tag = next_version(tags, declared, messages)
            manifest_version = tag[1:]
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
            declared_tag = 'v' + previous_release.removeprefix('v') if version(previous_release) else None
            tag = match[1] if match else (max(stable_head_tags, key=version) if stable_head_tags
                                        else declared_tag if declared_tag and declared_tag not in tags
                                        else next_version(tags, declared, ''))
            if tag not in tags:
                manifest_version = tag[1:]
                if 'version' in metadata:
                    metadata['version'] = tag[1:]
                write_metadata()
                git('add', '-A', cwd=target)
                if subprocess.run(['git', 'diff', '--cached', '--quiet'], cwd=target).returncode:
                    git('commit', '-m', f'chore: prepare {tag}\n\nPackage-Release: {tag}', cwd=target)
        sha = git('rev-parse', 'HEAD', cwd=target)
        new_tag = tag not in tags
        if tag in tags:
            if git('rev-list', '-1', tag, cwd=target) != sha:
                raise RuntimeError(f'{destination}: {tag} already points to another commit')
        else:
            git('tag', '-a', tag, '-m', f'Release {tag}', cwd=target)
        # Reject concurrent updates rather than overwriting another project's push.
        try:
            git('push', '--atomic', 'origin', f'HEAD:refs/heads/{branch}', f'refs/tags/{tag}', cwd=target)
        except subprocess.CalledProcessError as error:
            message = (error.output or b'').decode(errors='replace')
            if 'GH006' not in message and not ('GH013' in message and 'pull request' in message.lower()):
                raise
            ensure_quality_workflow(target, message)
            details = publication_pull_request(target, destination, branch, package, path, head, tag)
            raise PendingPublication(package, details) from None
        indexed = False
        if not new_tag:
            try:
                verify_release(package, {'version': tag[1:], 'reference': sha})
                indexed = True
            except RuntimeError as error:
                if 'is not available from Packagist' not in str(error):
                    raise
        if not indexed:
            if new_tag or github(f'repos/{destination}/releases/tags/{tag}', missing_ok=True) is None:
                github(f'repos/{destination}/releases', {
                    'tag_name': tag, 'name': tag,
                    'body': f'Synchronized {path} from https://github.com/{os.environ["GITHUB_REPOSITORY"]}/commit/{head}.'})
            packagist(package, url)
        print(f'{path}: published {destination} {tag}')
        return {'path': path, 'version': tag[1:], 'reference': sha,
                'repository': url, 'source_tree': git('rev-parse', f'{head}:{path}'),
                'source_signature': source_signature(path, head)}


def package_paths(paths):
    packages = {}
    for path in paths:
        composer = Path(path, 'composer.json')
        if not composer.is_file():
            continue
        metadata = json.loads(composer.read_text())
        name = metadata['name']
        preferred = metadata.get('extra', {}).get('liberu', {}).get('name')
        if name not in packages or Path(path).name == preferred:
            packages[name] = path
    return packages


def record_releases(releases, pending=None):
    reports = {'.github/package-releases.json': releases}
    if pending is not None:
        reports['.github/package-publication-pull-requests.json'] = pending
    changed = []
    for filename, data in reports.items():
        report = Path(filename)
        content = json.dumps(data, indent=2, sort_keys=True) + '\n'
        if report.exists() and report.read_text() == content:
            continue
        report.write_text(content)
        changed.append(filename)
    if not changed:
        return
    git('config', 'user.name', 'github-actions[bot]')
    git('config', 'user.email', '41898282+github-actions[bot]@users.noreply.github.com')
    git('add', '--', *changed)
    git('commit', '-m', 'chore: record published package releases [skip actions]', '--', *changed)
    git('push', 'origin', 'HEAD:main')


def verify_release(package, release, metadata_cache=None):
    cached = metadata_cache / (package.replace('/', '__') + '.json') if metadata_cache else None
    data = json.loads(cached.read_text()) if cached and cached.exists() else None

    def matching(metadata):
        previous = {}
        for item in (metadata or {}).get('packages', {}).get(package, []):
            if (metadata or {}).get('minified'):
                current = previous.copy()
                for key, value in item.items():
                    if value == '__unset':
                        current.pop(key, None)
                    else:
                        current[key] = value
            else:
                current = item
            previous = current
            if current.get('version', '').removeprefix('v') != release['version']:
                continue
            if current.get('source', {}).get('reference') != release['reference']:
                raise RuntimeError(f'{package}: published source does not match the recorded release')
            source_url = current.get('source', {}).get('url', '').replace('git@github.com:', 'https://github.com/').removesuffix('.git').rstrip('/')
            if release.get('repository') and source_url.casefold() != release['repository'].casefold():
                raise RuntimeError(f'{package}: published repository does not match the recorded release')
            if current.get('dist', {}).get('type') != 'zip':
                raise RuntimeError(f'{package}: expected a published distribution archive')
            return True
        return False

    if matching(data):
        return
    data = request(f'https://repo.packagist.org/p2/{package}.json', missing_ok=True)
    if not matching(data):
        raise RuntimeError(f'{package}: {release["version"]} is not available from Packagist')
    if cached:
        cached.parent.mkdir(parents=True, exist_ok=True)
        cached.write_text(json.dumps(data) + '\n')


def write_json(path, metadata):
    original = path.read_text()
    if json.loads(original) == metadata:
        return
    indentation = re.search(r'\n( +)"', original)
    if '\n' not in original.strip():
        content = json.dumps(metadata, separators=(',', ':'), ensure_ascii=False) + '\n'
    else:
        content = json.dumps(metadata, indent=len(indentation[1]) if indentation else 2, ensure_ascii=False) + '\n'
    temporary = None
    try:
        with tempfile.NamedTemporaryFile(mode='w', dir=path.parent, encoding='utf-8', delete=False) as output:
            temporary = Path(output.name)
            output.write(content)
        temporary.chmod(path.stat().st_mode & 0o777)
        temporary.replace(path)
    finally:
        if temporary and temporary.exists():
            temporary.unlink()


def materialize_source_aliases(head):
    root = Path.cwd().resolve()
    aliases = [path for kind in ('modules', 'themes') if Path(kind).exists()
               for path in Path(kind).iterdir() if path.is_symlink()]
    if not aliases:
        return
    with tempfile.TemporaryDirectory(prefix='.liberu-source-copies-', dir=root) as temporary:
        prepared = []
        for index, alias in enumerate(aliases):
            target = alias.resolve(strict=True)
            if not target.is_dir() or not target.is_relative_to(root):
                raise RuntimeError(f'{alias}: expected a tracked directory inside this repository')
            archive = subprocess.check_output(['git', 'archive', f'{head}:{target.relative_to(root)}'])
            copy = Path(temporary) / str(index)
            copy.mkdir()
            with tarfile.open(fileobj=io.BytesIO(archive)) as source:
                source.extractall(copy, filter='data')
            prepared.append((alias, copy, os.readlink(alias)))
        for alias, copy, original in prepared:
            alias.unlink()
            try:
                copy.replace(alias)
            except OSError:
                alias.symlink_to(original, target_is_directory=True)
                raise


def use_packagist(metadata_cache=None):
    head = git('rev-parse', 'HEAD')
    packages = package_paths(changed_packages('', head))
    releases = json.loads(Path('.github/package-releases.json').read_text())
    missing = set(packages) - set(releases)
    if missing:
        raise RuntimeError('Unpublished packages: ' + ', '.join(sorted(missing)))
    if subprocess.run(['git', 'diff', '--quiet', 'HEAD', '--', 'modules', 'themes']).returncode:
        raise RuntimeError('Commit module/theme changes before selecting published releases')
    for package, path in packages.items():
        release = releases[package]
        if release.get('source_signature') != source_signature(path, head):
            raise RuntimeError(f'{path}: source changed after publication')
    with ThreadPoolExecutor(max_workers=10) as pool:
        list(pool.map(lambda package: verify_release(package, releases[package], metadata_cache), packages))
    materialize_source_aliases(head)
    project_file = Path('composer.json')
    project = json.loads(project_file.read_text())
    for section in ('require', 'require-dev'):
        for package in project.get(section, {}):
            if package in releases:
                project[section][package] = releases[package]['version']
    project.pop('repositories', None)
    for kind in ('modules', 'themes'):
        for composer in Path(kind).glob('*/composer.json'):
            metadata = json.loads(composer.read_text())
            release = releases.get(metadata['name'])
            if not release:
                continue
            if 'version' in metadata:
                metadata['version'] = release['version']
                write_json(composer, metadata)
            manifest_file = composer.with_name('module.json' if kind == 'modules' else 'theme.json')
            if manifest_file.exists():
                manifest = json.loads(manifest_file.read_text())
                manifest['version'] = release['version']
                write_json(manifest_file, manifest)
    write_json(project_file, project)
    print(f'Configured {len(packages)} verified Packagist packages; module/theme copies retained')


def main():
    for key in ('GH_TOKEN', 'PACKAGIST_USERNAME', 'PACKAGIST_API_TOKEN'):
        if not os.environ.get(key):
            raise RuntimeError(f'Missing required secret: {key}')
    head = git('rev-parse', 'HEAD')
    before = os.environ.get('SYNC_BEFORE', '')
    report = Path('.github/package-releases.json')
    releases = json.loads(report.read_text()) if report.exists() else {}
    pending_report = Path('.github/package-publication-pull-requests.json')
    pending = json.loads(pending_report.read_text()) if pending_report.exists() else {}
    failures = []
    paths = package_paths(changed_packages(before, head))
    for package, path in sorted(paths.items(), key=lambda item: (item[0] != 'liberusoftware/tenancy', item[0])):
        tree = git('rev-parse', f'{head}:{path}')
        previous = releases.get(package, {})
        declared = json.loads(Path(path, 'composer.json').read_text()).get('version', '')
        bump = version(declared) and version(declared) > (version(previous.get('version', '')) or (0, 0, 0))
        matching = previous.get('source_tree') == tree or previous.get('source_signature') == source_signature(path, head)
        try:
            if matching and not bump:
                try:
                    verify_release(package, previous)
                except RuntimeError as error:
                    if 'is not available from Packagist' not in str(error):
                        raise
                    packagist(package, previous['repository'])
                print(f'{path}: already published at {previous["version"]}')
            else:
                releases[package] = publish(path, head, before)
            pending.pop(package, None)
            if (len(releases) + len(pending)) % 50 == 0:
                record_releases(releases, pending)
        except PendingPublication as error:
            pending[package] = error.details
            print(f'::notice::{error}')
            failures.append(path)
        except Exception as error:
            print(f'::error::{path}: {error}')
            failures.append(path)
    record_releases(releases, pending)
    if failures:
        raise RuntimeError('Failed packages: ' + ', '.join(failures))
    project = json.loads(Path('composer.json').read_text())
    repositories = project.get('repositories', [])
    repositories = repositories.values() if isinstance(repositories, dict) else repositories
    if not any(isinstance(item, dict) and item.get('type') in ('path', 'vcs', 'package') for item in repositories):
        packagist(project['name'], 'https://github.com/' + os.environ['GITHUB_REPOSITORY'])


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--use-packagist', action='store_true')
    parser.add_argument('--metadata-cache', type=Path)
    arguments = parser.parse_args()
    if arguments.use_packagist:
        use_packagist(arguments.metadata_cache)
    else:
        main()
