import importlib.util
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch
from urllib.error import HTTPError
import io

spec = importlib.util.spec_from_file_location('sync_packages', Path(__file__).with_name('sync-packages.py'))
sync = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sync)


class RequestTests(unittest.TestCase):
    def test_github_rate_limit_waits_until_reset_and_retries(self):
        error = HTTPError('https://api.github.com/test', 403, 'rate limit',
                          {'X-RateLimit-Remaining': '0', 'X-RateLimit-Reset': '1002'}, None)
        with patch.object(sync, 'urlopen', side_effect=[error, io.BytesIO(b'{"ok": true}')]), \
                patch.object(sync.time, 'time', return_value=1000), patch.object(sync.time, 'sleep') as sleep:
            self.assertEqual(sync.request('https://api.github.com/test'), {'ok': True})
        sleep.assert_called_once_with(3)

    def test_permission_denial_does_not_retry(self):
        error = HTTPError('https://api.github.com/test', 403, 'forbidden', {}, None)
        with patch.object(sync, 'urlopen', side_effect=error), patch.object(sync.time, 'sleep') as sleep:
            with self.assertRaisesRegex(RuntimeError, 'HTTP 403'):
                sync.request('https://api.github.com/test')
        sleep.assert_not_called()


class VersionTests(unittest.TestCase):
    def test_release_numbers(self):
        self.assertEqual(sync.next_version([], '', ''), 'v1.0.0')
        self.assertEqual(sync.next_version([], '2.4.0', ''), 'v2.4.0')
        self.assertEqual(sync.next_version(['v1.9.0', 'v1.10.0', 'v2.0.0-rc1'], '', 'fix: bug'), 'v1.10.1')
        self.assertEqual(sync.next_version(['v1.0.0'], '', 'feat(auth): add login'), 'v1.1.0')
        self.assertEqual(sync.next_version(['v1.0.0'], '', 'feat(auth)!: new API'), 'v2.0.0')
        self.assertEqual(sync.next_version(['v1.0.0'], '', 'fix: change\n\nBREAKING CHANGE: removed API'), 'v2.0.0')
        self.assertEqual(sync.next_version(['v1.0.0'], '1.4.0', 'fix: bug'), 'v1.4.0')

    def test_prefixes_are_not_duplicated(self):
        with patch.object(sync.Path, 'exists', return_value=False):
            self.check_default_prefixes()

    def check_default_prefixes(self):
        for path, expected in [('modules/auth', 'module-auth'), ('modules/module-cms-api', 'module-cms-api'),
                               ('themes/default', 'theme-default'), ('themes/theme-dark', 'theme-dark')]:
            self.assertEqual(sync.repository_name(path), expected)

    def test_existing_design_prefix_is_authoritative(self):
        with patch.object(sync.Path, 'exists', return_value=True), patch.object(
            sync.Path, 'read_text', return_value=json.dumps({'components': {'modules': {'repositoryPrefix': 'module-'}}})
        ):
            self.assertEqual(sync.repository_name('modules/module-manager'), 'module-manager')

    def test_declared_major_can_release_a_compatible_maintenance_version(self):
        self.assertEqual(sync.next_version(['v1.4.1', 'v2.0.0'], '1.4.1', 'fix: repair'), 'v1.4.2')
        self.assertEqual(sync.next_version(['v1.0.0'], '0.1.0', 'fix: repair'), 'v0.1.0')
        self.assertEqual(sync.next_version(['v0.1.2', 'v0.2.0'], '0.1.0', 'fix: repair'), 'v0.1.3')


class PublishTests(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.old_cwd = Path.cwd()
        self.root = Path(self.temporary.name)
        os.chdir(self.root)
        self.addCleanup(os.chdir, self.old_cwd)
        self.real_git = sync.git
        self.real_git('init', '-q', '-b', 'main')
        self.real_git('config', 'user.email', 'test@example.com')
        self.real_git('config', 'user.name', 'Test')
        self.real_git('init', '-q', '--bare', '-b', 'main', str(self.root / 'remote.git'))
        self.package = self.root / 'modules' / 'example'
        self.package.mkdir(parents=True)
        (self.package / 'composer.json').write_text(json.dumps({'name': 'liberusoftware/example', 'version': '1.0.0'}))
        (self.package / 'source.php').write_text('first')
        self.commit('feat: initial package')
        self.environ = patch.dict(os.environ, {'GITHUB_REPOSITORY': 'liberusoftware/test'})
        self.environ.start()
        self.addCleanup(self.environ.stop)
        self.releases = {}
        self.repo_exists = False
        self.fail_release = False
        self.packagist_calls = []
        self.pull_requests = []

    def commit(self, message):
        self.real_git('add', '-A', '--', 'modules')
        self.real_git('commit', '-q', '-m', message)
        return self.real_git('rev-parse', 'HEAD')

    def git(self, *args, cwd=None):
        if args[0] == 'clone':
            args = ('clone', '--quiet', str(self.root / 'remote.git'), args[-1])
        return self.real_git(*args, cwd=cwd)

    def github(self, path, payload=None, missing_ok=False):
        if '/pulls?' in path:
            return self.pull_requests
        if path.endswith('/pulls'):
            pull = {'html_url': 'https://github.com/liberusoftware/module-example/pull/1', **payload}
            self.pull_requests.append(pull)
            return pull
        if path == 'orgs/liberusoftware/repos':
            self.repo_exists = True
            self.assertEqual(payload['name'], 'module-example')
            return {'default_branch': 'main', 'private': False}
        if '/releases/tags/' in path:
            return self.releases.get(path.rsplit('/', 1)[-1])
        if path.endswith('/releases'):
            if self.fail_release:
                raise RuntimeError('Simulated release failure')
            self.releases[payload['tag_name']] = payload
            return payload
        return {'default_branch': 'main', 'private': False} if self.repo_exists else None

    def publish(self, before=''):
        with patch.object(sync, 'request', return_value=None), patch.object(sync, 'git', self.git), patch.object(sync, 'github', self.github), patch.object(
            sync, 'packagist', lambda *args: self.packagist_calls.append(args)
        ):
            sync.publish('modules/example', self.real_git('rev-parse', 'HEAD'), before)

    def test_create_update_delete_and_noop(self):
        before = self.real_git('rev-parse', 'HEAD')
        self.publish()
        self.assertIn('v1.0.0', self.releases)
        self.publish()
        self.assertEqual(len(self.releases), 1)
        (self.package / 'source.php').unlink()
        (self.package / 'new.php').write_text('new')
        self.commit('feat: replace implementation')
        self.assertEqual(sync.changed_packages(before, self.real_git('rev-parse', 'HEAD')), ['modules/example'])
        self.publish(before)
        self.assertIn('v1.1.0', self.releases)
        files = self.real_git('--git-dir=remote.git', 'ls-tree', '-r', '--name-only', 'main')
        self.assertNotIn('source.php', files)
        self.assertIn('new.php', files)
        self.publish(before)
        self.assertEqual(len(self.releases), 2)
        self.assertEqual(self.packagist_calls[-1], ('liberusoftware/example', 'https://github.com/liberusoftware/module-example'))

    def test_retry_after_push_does_not_bump_again(self):
        self.fail_release = True
        with self.assertRaisesRegex(RuntimeError, 'Simulated'):
            self.publish()
        self.fail_release = False
        self.publish()
        self.assertEqual(list(self.releases), ['v1.0.0'])
        self.assertEqual(self.real_git('--git-dir=remote.git', 'rev-list', '--count', 'main'), '1')

    def test_protected_branch_creates_a_reviewable_pull_request_without_releasing(self):
        self.publish()
        before = self.real_git('rev-parse', 'HEAD')
        (self.package / 'source.php').write_text('updated')
        self.commit('feat: update implementation')
        original_git = self.git

        def protected(*args, cwd=None):
            if args[0] == 'push' and 'HEAD:refs/heads/main' in args:
                raise subprocess.CalledProcessError(1, ['git', *args], output=b'GH006: Protected branch update failed')
            return original_git(*args, cwd=cwd)

        with patch.object(self, 'git', side_effect=protected):
            with self.assertRaises(sync.PendingPublication) as pending:
                self.publish(before)
        self.assertEqual(pending.exception.details['url'], self.pull_requests[0]['html_url'])
        self.assertEqual(list(self.releases), ['v1.0.0'])
        self.assertNotIn('v1.1.0', self.real_git('--git-dir=remote.git', 'tag', '--list'))
        self.assertEqual(self.real_git('--git-dir=remote.git', 'rev-list', '--count', 'main'), '1')

    def test_explicit_version_only_change(self):
        before = self.real_git('rev-parse', 'HEAD')
        self.publish()
        composer = self.package / 'composer.json'
        metadata = json.loads(composer.read_text())
        metadata['version'] = '2.0.0'
        composer.write_text(json.dumps(metadata))
        self.commit('chore: release version')
        self.publish(before)
        self.assertEqual(list(self.releases), ['v1.0.0', 'v2.0.0'])
        self.publish(before)
        self.assertEqual(self.real_git('--git-dir=remote.git', 'rev-list', '--count', 'main'), '2')

    def test_package_without_composer_version(self):
        composer = self.package / 'composer.json'
        composer.write_text(json.dumps({'name': 'liberusoftware/example'}))
        self.commit('chore: use tag versions')
        self.publish()
        self.publish()
        self.assertEqual(list(self.releases), ['v1.0.0'])
        metadata = json.loads(self.real_git('--git-dir=remote.git', 'show', 'main:composer.json'))
        self.assertNotIn('version', metadata)

    def test_repository_ci_files_survive_source_sync(self):
        self.publish()
        checkout = self.root / 'ci-checkout'
        self.real_git('clone', '--quiet', str(self.root / 'remote.git'), str(checkout))
        self.real_git('config', 'user.email', 'test@example.com', cwd=checkout)
        self.real_git('config', 'user.name', 'Test', cwd=checkout)
        (checkout / '.github').mkdir()
        (checkout / '.github' / 'CODEOWNERS').write_text('* @maintainers\n')
        self.real_git('add', '.github', cwd=checkout)
        self.real_git('commit', '-m', 'ci: preserve ownership rules', cwd=checkout)
        self.real_git('push', 'origin', 'main', cwd=checkout)
        self.publish()
        self.assertEqual(self.real_git('--git-dir=remote.git', 'show', 'main:.github/CODEOWNERS'), '* @maintainers')
        metadata = json.loads(self.real_git('--git-dir=remote.git', 'show', 'main:composer.json'))
        self.assertEqual(metadata['version'], '1.0.1')

    def test_merged_release_metadata_is_tagged_without_another_version_bump(self):
        self.publish()
        checkout = self.root / 'merged-checkout'
        self.real_git('clone', '--quiet', str(self.root / 'remote.git'), str(checkout))
        self.real_git('config', 'user.email', 'test@example.com', cwd=checkout)
        self.real_git('config', 'user.name', 'Test', cwd=checkout)
        metadata = json.loads((checkout / 'composer.json').read_text())
        metadata['version'] = '1.1.0'
        (checkout / 'composer.json').write_text(json.dumps(metadata, indent=2) + '\n')
        self.real_git('add', 'composer.json', cwd=checkout)
        self.real_git('commit', '-m', 'Merge approved package release', cwd=checkout)
        self.real_git('push', 'origin', 'main', cwd=checkout)
        self.publish()
        self.assertIn('v1.1.0', self.releases)
        self.assertEqual(self.real_git('--git-dir=remote.git', 'rev-list', '--count', 'main'), '2')

    def test_release_keeps_composer_and_manifests_in_agreement(self):
        for filename in ('module.json', 'theme.json'):
            (self.package / filename).write_text(json.dumps({'name': 'example', 'version': '1.0.0'}))
        before = self.commit('fix: add package manifests')
        self.publish()
        (self.package / 'source.php').write_text('updated')
        self.commit('fix: update implementation')
        self.publish(before)
        for filename in ('composer.json', 'module.json', 'theme.json'):
            metadata = json.loads(self.real_git('--git-dir=remote.git', 'show', 'main:' + filename))
            self.assertEqual(metadata['version'], '1.0.1')
        self.publish(before)
        self.assertEqual(list(self.releases), ['v1.0.0', 'v1.0.1'])

    def test_untracked_files_are_not_exported(self):
        (self.package / 'secret.env').write_text('private')
        self.publish()
        files = self.real_git('--git-dir=remote.git', 'ls-tree', '-r', '--name-only', 'main')
        self.assertNotIn('secret.env', files)

    def test_source_signature_ignores_only_generated_versions(self):
        head = self.real_git('rev-parse', 'HEAD')
        original = sync.source_signature('modules/example', head)
        composer = self.package / 'composer.json'
        metadata = json.loads(composer.read_text())
        metadata['version'] = '1.0.1'
        composer.write_text(json.dumps(metadata))
        head = self.commit('chore: record generated version')
        self.assertEqual(original, sync.source_signature('modules/example', head))
        (self.package / 'source.php').write_text('changed')
        head = self.commit('fix: change implementation')
        self.assertNotEqual(original, sync.source_signature('modules/example', head))

    def test_tag_version_is_written_to_a_manifest_without_adding_a_composer_version(self):
        (self.package / 'composer.json').write_text(json.dumps({'name': 'liberusoftware/example'}))
        (self.package / 'module.json').write_text(json.dumps({'name': 'example', 'version': '1.0.0'}))
        before = self.commit('fix: use tag versions')
        self.publish()
        (self.package / 'source.php').write_text('updated')
        self.commit('fix: update implementation')
        self.publish(before)
        manifest = json.loads(self.real_git('--git-dir=remote.git', 'show', 'main:module.json'))
        composer = json.loads(self.real_git('--git-dir=remote.git', 'show', 'main:composer.json'))
        self.assertEqual(manifest['version'], '1.0.1')
        self.assertNotIn('version', composer)
        self.publish(before)
        self.assertEqual(len(self.releases), 2)

    def test_duplicate_directories_select_the_manifest_install_path(self):
        duplicate = self.root / 'modules' / 'module-example'
        duplicate.mkdir()
        metadata = {'name': 'liberusoftware/example', 'extra': {'liberu': {'name': 'example'}}}
        for path in (self.package, duplicate):
            (path / 'composer.json').write_text(json.dumps(metadata))
        self.assertEqual(sync.package_paths(['modules/example', 'modules/module-example']),
                         {'liberusoftware/example': 'modules/example'})

    def prepare_consumer(self):
        root = {'name': 'liberusoftware/test-project', 'require': {'liberusoftware/example': '^1.0'},
                'repositories': [{'type': 'path', 'url': 'modules/*'}]}
        Path('composer.json').write_text(json.dumps(root))
        report = {'liberusoftware/example': {'version': '1.0.0', 'reference': 'published-sha',
                                           'source_signature': sync.source_signature('modules/example', self.real_git('rev-parse', 'HEAD'))}}
        Path('.github').mkdir(exist_ok=True)
        Path('.github/package-releases.json').write_text(json.dumps(report))
        return root

    def test_packagist_consumer_requires_matching_public_distribution(self):
        self.prepare_consumer()
        metadata = {'packages': {'liberusoftware/example': [{'version': 'v1.0.0',
                     'source': {'reference': 'published-sha'}, 'dist': {'type': 'zip'}}]}}
        with patch.object(sync, 'request', return_value=metadata):
            sync.use_packagist()
        project = json.loads(Path('composer.json').read_text())
        self.assertNotIn('repositories', project)
        self.assertEqual(project['require']['liberusoftware/example'], '1.0.0')
        self.assertTrue((self.package / 'source.php').exists())

    def test_failed_publication_verification_preserves_composer_configuration(self):
        original = self.prepare_consumer()
        with patch.object(sync, 'request', return_value=None):
            with self.assertRaisesRegex(RuntimeError, 'not available'):
                sync.use_packagist()
        self.assertEqual(json.loads(Path('composer.json').read_text()), original)

    def test_source_aliases_become_real_copies_without_removing_the_original(self):
        alias = self.root / 'modules' / 'module-example'
        alias.symlink_to('example', target_is_directory=True)
        self.commit('fix: retain a source alias')
        sync.materialize_source_aliases(self.real_git('rev-parse', 'HEAD'))
        self.assertFalse(alias.is_symlink())
        self.assertEqual((alias / 'source.php').read_text(), 'first')
        self.assertEqual((self.package / 'source.php').read_text(), 'first')


class PackagistTests(unittest.TestCase):
    def test_minified_metadata_preserves_inherited_distribution_fields(self):
        metadata = {'minified': 'composer/2.0', 'packages': {'liberusoftware/example': [
            {'version': '1.0.1', 'source': {'reference': 'new'}, 'dist': {'type': 'zip'}},
            {'version': '1.0.0', 'source': {'reference': 'old'}}]}}
        with patch.object(sync, 'request', return_value=metadata):
            sync.verify_release('liberusoftware/example', {'version': '1.0.0', 'reference': 'old'})

    def test_published_repository_must_match_the_recorded_repository(self):
        metadata = {'packages': {'liberusoftware/example': [{'version': '1.0.0',
                    'source': {'reference': 'sha', 'url': 'https://github.com/other/example.git'},
                    'dist': {'type': 'zip'}}]}}
        with patch.object(sync, 'request', return_value=metadata):
            with self.assertRaisesRegex(RuntimeError, 'repository does not match'):
                sync.verify_release('liberusoftware/example', {'version': '1.0.0', 'reference': 'sha',
                                    'repository': 'https://github.com/liberusoftware/module-example'})

    def test_create_and_update(self):
        url = 'https://github.com/liberusoftware/module-example'
        for existing, endpoint in [(None, 'create-package'), ({'package': {'repository': url}}, 'update-package')]:
            with patch.dict(os.environ, {'PACKAGIST_USERNAME': 'test', 'PACKAGIST_API_TOKEN': 'token'}), patch.object(
                sync, 'request', side_effect=[existing, {'status': 'success'}]
            ) as request:
                sync.packagist('liberusoftware/example', url)
                self.assertEqual(request.call_args.args, (f'https://packagist.org/api/{endpoint}', {'repository': url}, 'test:token'))

    def test_package_name_collision_fails(self):
        with patch.object(sync, 'request', return_value={'package': {'repository': 'https://github.com/other/repo'}}), \
                patch.object(sync, 'github', return_value={'html_url': 'https://github.com/other/repo'}):
            with self.assertRaisesRegex(RuntimeError, 'already belongs'):
                sync.packagist('liberusoftware/example', 'https://github.com/liberusoftware/module-example')

    def test_repository_move_requires_github_to_confirm_the_same_destination(self):
        old = 'https://github.com/old-owner/example'
        new = 'https://github.com/liberusoftware/module-example'
        with patch.dict(os.environ, {'PACKAGIST_USERNAME': 'test', 'PACKAGIST_API_TOKEN': 'token'}), \
                patch.object(sync, 'github', return_value={'html_url': new}), \
                patch.object(sync, 'request', side_effect=[{'package': {'repository': old}},
                                                         {'status': 'success'}, {'status': 'success'}]) as request:
            sync.packagist('liberusoftware/example', new)
        self.assertEqual(request.call_args_list[1].kwargs, {'method': 'PUT'})

    def test_explicit_same_organization_relocation_updates_registration(self):
        old = 'https://github.com/liberusoftware/module-example'
        new = 'https://github.com/liberusoftware/module-foundation-example'
        with patch.dict(os.environ, {'PACKAGIST_USERNAME': 'test', 'PACKAGIST_API_TOKEN': 'token'}), \
                patch.object(sync, 'configured_repository', return_value=new), \
                patch.object(sync, 'request', side_effect=[{'package': {'repository': old}},
                                                         {'status': 'success'}, {'status': 'success'}]) as request:
            sync.packagist('liberusoftware/example', new)
        self.assertEqual(request.call_args_list[1].kwargs, {'method': 'PUT'})


class IndependentChangeTests(unittest.TestCase):
    def setUp(self):
        temporary = tempfile.TemporaryDirectory()
        self.addCleanup(temporary.cleanup)
        self.root = Path(temporary.name)
        self.addCleanup(os.chdir, Path.cwd())
        os.chdir(self.root)
        self.remote = self.root / 'remote.git'
        self.run_git('init', '-q', '-b', 'main')
        self.identify(self.root)
        self.run_git('init', '-q', '--bare', '-b', 'main', str(self.remote))
        self.package = self.root / 'modules' / 'example'
        self.package.mkdir(parents=True)
        self.write('composer.json', json.dumps({'name': 'liberusoftware/example', 'version': '1.0.0'}))
        self.write('src/Example.php', 'first')
        self.write('README.md', 'readme')
        self.commit('feat: initial package')
        environment = patch.dict(os.environ, {'GITHUB_REPOSITORY': 'liberusoftware/test', 'GH_TOKEN': 'token'})
        environment.start()
        self.addCleanup(environment.stop)
        self.releases = {}
        self.exists = False
        self.on_push = None
        self.renamed = {}
        self.recorded = {}

    def run_git(self, *args, cwd=None):
        return subprocess.check_output(['git', *args], cwd=cwd, stderr=subprocess.STDOUT).decode().strip()

    def identify(self, directory):
        self.run_git('config', 'user.email', 'test@example.com', cwd=directory)
        self.run_git('config', 'user.name', 'Test', cwd=directory)

    def write(self, name, content):
        (self.package / name).parent.mkdir(parents=True, exist_ok=True)
        (self.package / name).write_text(content)

    def commit(self, message):
        self.run_git('add', '-A', '--', 'modules')
        self.run_git('commit', '-q', '-m', message)

    def git(self, *args, cwd=None):
        if args[0] == 'clone':
            args = ('clone', '--quiet', str(self.remote), args[-1])
        if args[0] == 'push' and self.on_push:
            hook, self.on_push = self.on_push, None
            hook()
        return self.run_git(*args, cwd=cwd)

    def github(self, path, payload=None, missing_ok=False):
        if path == 'orgs/liberusoftware/repos':
            self.exists = True
            return {'default_branch': 'main', 'private': False}
        if '/releases/tags/' in path:
            return self.releases.get(path.rsplit('/', 1)[-1])
        if path.endswith('/releases'):
            self.releases[payload['tag_name']] = payload
            return payload
        if path in self.renamed:
            return {'full_name': self.renamed[path]}
        return {'default_branch': 'main', 'private': False} if self.exists else None

    def publish(self, source='liberusoftware/test'):
        with patch.object(sync, 'request', return_value=None), patch.object(sync, 'git', self.git), \
                patch.object(sync, 'github', self.github), patch.object(sync, 'packagist', lambda *args: None), \
                patch.dict(os.environ, {'GITHUB_REPOSITORY': source}):
            Path('.github').mkdir(exist_ok=True)
            Path('.github/package-releases.json').write_text(json.dumps(self.recorded.get(source, {})))
            release = sync.publish('modules/example', self.run_git('rev-parse', 'HEAD'), '')
        self.recorded[source] = {'liberusoftware/example': release}

    def in_destination(self, change, message='fix: patch the package directly'):
        checkout = self.root / f'destination-{len(list(self.root.glob("destination-*")))}'
        self.run_git('clone', '--quiet', str(self.remote), str(checkout))
        self.identify(checkout)
        change(checkout)
        self.run_git('add', '-A', cwd=checkout)
        self.run_git('commit', '-q', '-m', message, cwd=checkout)
        self.run_git('push', '-q', 'origin', 'main', cwd=checkout)

    def remote_state(self):
        return (self.run_git('--git-dir', str(self.remote), 'rev-parse', 'main'),
                self.run_git('--git-dir', str(self.remote), 'tag', '--list'), dict(self.releases))

    def assert_refused(self, *paths, source='liberusoftware/test', blames=()):
        before = self.remote_state()
        with self.assertRaisesRegex(RuntimeError, 'would overwrite') as refused:
            self.publish(source)
        for name in (*paths, *blames):
            self.assertIn(name, str(refused.exception))
        self.assertEqual(self.remote_state(), before)

    def test_source_changes_publish_when_the_destination_has_none(self):
        self.publish()
        self.write('src/Example.php', 'second')
        (self.package / 'README.md').unlink()
        self.commit('fix: change implementation')
        self.publish()
        files = self.run_git('--git-dir', str(self.remote), 'ls-tree', '-r', '--name-only', 'main')
        self.assertNotIn('README.md', files)
        self.assertEqual(self.run_git('--git-dir', str(self.remote), 'show', 'main:src/Example.php'), 'second')
        self.assertEqual(len(self.releases), 2)

    def test_a_destination_edit_the_sync_would_revert_is_refused(self):
        self.publish()
        self.in_destination(lambda checkout: (checkout / 'src' / 'Example.php').write_text('hotfix'))
        self.write('README.md', 'unrelated source change')
        self.commit('fix: update readme')
        self.assert_refused('src/Example.php')

    def test_a_destination_only_file_is_never_deleted(self):
        self.publish()
        self.in_destination(lambda checkout: (checkout / 'src' / 'Added.php').write_text('added'))
        self.assert_refused('src/Added.php')

    def test_a_destination_change_brought_into_the_source_publishes(self):
        self.publish()
        self.in_destination(lambda checkout: (checkout / 'src' / 'Example.php').write_text('hotfix'))
        self.write('src/Example.php', 'hotfix')
        self.write('src/Other.php', 'more')
        self.commit('fix: adopt the hotfix')
        self.publish()
        self.assertEqual(self.run_git('--git-dir', str(self.remote), 'show', 'main:src/Other.php'), 'more')

    def test_a_destination_version_bump_is_not_independent_work(self):
        self.publish()

        def bump(checkout):
            metadata = json.loads((checkout / 'composer.json').read_text())
            metadata['version'] = '1.1.0'
            (checkout / 'composer.json').write_text(json.dumps(metadata))
        self.in_destination(bump, 'chore: release 1.1.0')
        self.write('src/Example.php', 'second')
        self.commit('fix: change implementation')
        self.publish()
        self.assertEqual(self.run_git('--git-dir', str(self.remote), 'show', 'main:src/Example.php'), 'second')

    def test_history_never_synced_is_refused_unless_identical(self):
        def seed(content):
            def change(checkout):
                (checkout / 'composer.json').write_text(json.dumps({'name': 'liberusoftware/example', 'version': '1.0.0'}))
                (checkout / 'src').mkdir(exist_ok=True)
                (checkout / 'src' / 'Example.php').write_text(content)
                (checkout / 'README.md').write_text('readme')
            return change
        self.in_destination(seed('maintained elsewhere'), 'feat: initial package')
        self.exists = True
        self.assert_refused('src/Example.php')
        self.in_destination(seed('first'), 'fix: match the source')
        self.publish()

    def test_a_concurrent_push_is_rejected_without_tags_or_releases(self):
        self.publish()
        self.write('src/Example.php', 'second')
        self.commit('fix: change implementation')
        self.on_push = lambda: self.in_destination(lambda checkout: (checkout / 'NOTES.md').write_text('raced'))
        with self.assertRaises(subprocess.CalledProcessError):
            self.publish()
        self.assertEqual(self.run_git('--git-dir', str(self.remote), 'show', 'main:NOTES.md'), 'raced')
        self.assertNotIn('v1.0.1', self.run_git('--git-dir', str(self.remote), 'tag', '--list'))
        self.assertEqual(sorted(self.releases), ['v1.0.0'])
        self.assert_refused('NOTES.md')

    def test_sync_commits_name_their_source(self):
        with patch.dict(os.environ, {'GITHUB_REPOSITORY': 'liberusoftware/app-a'}):
            self.assertEqual(sync.sync_source('chore: sync modules/x from liberusoftware/app-b@0a1b2c\n\nPackage-Release: v1.0.0'),
                             'liberusoftware/app-b')
            self.assertEqual(sync.sync_source('Sync liberusoftware/x v1.2.0 from app-c (#4)\n\nbody'), 'liberusoftware/app-c')
            self.assertIsNone(sync.sync_source('fix: mention chore: sync inline\n\nPackage-Release: v1.0.0'))

    def test_another_source_change_is_refused_and_named(self):
        self.publish('liberusoftware/app-a')
        self.publish('liberusoftware/app-b')
        self.write('src/Example.php', 'improved by a')
        self.commit('fix: improve in app a')
        self.publish('liberusoftware/app-a')
        self.write('src/Example.php', 'first')
        self.write('README.md', 'edited by b')
        self.commit('docs: edit readme in app b')
        self.assert_refused('src/Example.php', source='liberusoftware/app-b', blames=('liberusoftware/app-a',))

    def test_a_first_sync_from_a_new_source_must_match_what_others_published(self):
        self.publish('liberusoftware/app-a')
        self.write('src/Example.php', 'different copy')
        self.commit('fix: diverge in app b')
        self.assert_refused('src/Example.php', source='liberusoftware/app-b', blames=('liberusoftware/app-a',))

    def test_each_source_compares_against_its_own_last_sync(self):
        self.publish('liberusoftware/app-a')
        self.publish('liberusoftware/app-b')
        self.write('src/Example.php', 'improved by a')
        self.commit('fix: improve in app a')
        self.publish('liberusoftware/app-a')
        self.write('src/Extra.php', 'added by b')
        self.commit('feat: adopt a and extend in app b')
        self.publish('liberusoftware/app-b')
        self.write('src/Example.php', 'improved again by a')
        (self.package / 'src' / 'Extra.php').unlink()
        self.commit('fix: improve again in app a')
        self.assert_refused('src/Extra.php', source='liberusoftware/app-a', blames=('liberusoftware/app-b',))
        self.write('src/Extra.php', 'added by b')
        self.commit('feat: adopt b in app a')
        self.publish('liberusoftware/app-a')
        self.assertEqual(self.run_git('--git-dir', str(self.remote), 'show', 'main:src/Example.php'), 'improved again by a')

    def test_a_renamed_source_keeps_its_baseline(self):
        self.publish('liberusoftware/old-a')
        self.in_destination(lambda checkout: (checkout / 'NOTES.md').write_text('direct'))
        self.write('NOTES.md', 'direct')
        self.write('src/Example.php', 'second')
        self.commit('fix: change after the rename')
        self.assert_refused('src/Example.php', source='liberusoftware/app-a')
        self.renamed['repos/liberusoftware/old-a'] = 'liberusoftware/app-a'
        self.publish('liberusoftware/app-a')
        self.assertEqual(self.run_git('--git-dir', str(self.remote), 'show', 'main:src/Example.php'), 'second')

    def test_an_identical_first_sync_records_a_baseline(self):
        self.publish('liberusoftware/app-a')
        self.publish('liberusoftware/app-b')
        self.write('README.md', 'edited by b')
        self.commit('docs: edit readme in app b')
        self.publish('liberusoftware/app-b')
        self.assertEqual(self.run_git('--git-dir', str(self.remote), 'show', 'main:README.md'), 'edited by b')


if __name__ == '__main__':
    unittest.main()
