import importlib.util
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest
from unittest.mock import patch

spec = importlib.util.spec_from_file_location('sync_packages', Path(__file__).with_name('sync-packages.py'))
sync = importlib.util.module_from_spec(spec)
spec.loader.exec_module(sync)


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
            self.assertEqual(sync.repository_name('modules/module-manager'), 'module-module-manager')


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

    def commit(self, message):
        self.real_git('add', '-A', '--', 'modules')
        self.real_git('commit', '-q', '-m', message)
        return self.real_git('rev-parse', 'HEAD')

    def git(self, *args, cwd=None):
        if args[0] == 'clone':
            args = ('clone', '--quiet', str(self.root / 'remote.git'), args[-1])
        return self.real_git(*args, cwd=cwd)

    def github(self, path, payload=None, missing_ok=False):
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
        with patch.object(sync, 'git', self.git), patch.object(sync, 'github', self.github), patch.object(
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

    def test_untracked_files_are_not_exported(self):
        (self.package / 'secret.env').write_text('private')
        self.publish()
        files = self.real_git('--git-dir=remote.git', 'ls-tree', '-r', '--name-only', 'main')
        self.assertNotIn('secret.env', files)


class PackagistTests(unittest.TestCase):
    def test_create_and_update(self):
        url = 'https://github.com/liberusoftware/module-example'
        for existing, endpoint in [(None, 'create-package'), ({'package': {'repository': url}}, 'update-package')]:
            with patch.dict(os.environ, {'PACKAGIST_USERNAME': 'test', 'PACKAGIST_API_TOKEN': 'token'}), patch.object(
                sync, 'request', side_effect=[existing, {'status': 'success'}]
            ) as request:
                sync.packagist('liberusoftware/example', url)
                self.assertEqual(request.call_args.args, (f'https://packagist.org/api/{endpoint}', {'repository': url}, 'test:token'))

    def test_package_name_collision_fails(self):
        with patch.object(sync, 'request', return_value={'package': {'repository': 'https://github.com/other/repo'}}):
            with self.assertRaisesRegex(RuntimeError, 'already belongs'):
                sync.packagist('liberusoftware/example', 'https://github.com/liberusoftware/module-example')


if __name__ == '__main__':
    unittest.main()
