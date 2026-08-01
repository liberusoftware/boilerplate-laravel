# Module development guide

Modules are independently versioned Composer packages installed in `modules/{name}`. Composer owns package discovery and autoloading; `module.json` owns runtime metadata, dependency capabilities, enablement defaults and optional Filament plugin declarations. The application explicitly selects enabled modules in `config/modules.php`.

## Package anatomy

```text
modules/example/
├── composer.json
├── module.json
├── README.md
├── CHANGELOG.md
├── src/
│   └── ExampleServiceProvider.php
├── config/
├── database/migrations/
├── resources/views/
├── routes/
└── tests/
```

`composer.json` must use type `liberu-module`, declare a stable package version, map the package namespace, and require every package whose namespace it imports. The manifest name and version must match Composer metadata. Runtime modules declare a provider in `extra.liberu.provider`; they must not use Laravel's `extra.laravel.providers`, because the module manager is the single provider lifecycle authority.

A typical manifest is:

```json
{
    "name": "example",
    "version": "1.0.0",
    "category": "domain",
    "description": "Example domain capability.",
    "default_enabled": false,
    "provides": ["example"],
    "requires": ["foundation"],
    "required_capabilities": ["foundation"]
}
```

Use `integration`, `domain`, `presentation`, or `theme` as the category. Keep framework presentation dependencies out of domain packages. Shared interfaces belong in a small contract package when adapters must depend on a stable boundary without importing an implementation.

## Dependency and capability rules

Declare package dependencies twice where they serve different purposes:

- Composer `require` controls installation, autoloading and compatible package versions.
- Manifest `requires` and `required_capabilities` control the enabled runtime graph.

`module:validate` checks that Liberu package dependencies are represented consistently, required versions are installed, providers are valid, capability providers are unambiguous and the enabled graph has no missing dependencies or cycles.

Do not query another module's tables or import its models as an informal integration API. Prefer a contract, application service, event or explicitly documented schema extension. A module that alters another module's tables must declare that owner as a dependency and document the extension in its README.

## Enablement and boot

Bundled packages default to disabled in their manifests. The application enables its selected composition explicitly:

```php
// config/modules.php
'enabled' => [
    'module-manager',
    'foundation',
    'example',
],
```

The root application bootstraps only the module manager directly. It discovers installed `liberu-module` packages through Composer, validates the selected graph, orders providers by dependencies and registers only enabled modules. Package auto-discovery must not bypass this process.

Useful commands are:

```bash
php artisan module:validate
php artisan foundation:doctor
composer validate --no-check-publish
```

## Filament presentation adapters

Filament code belongs in a companion `*-filament` presentation module rather than its domain module. The presentation manifest declares which panel receives its plugin:

```json
{
    "name": "example-filament",
    "category": "presentation",
    "default_enabled": false,
    "requires": ["example"],
    "filament_plugins": {
        "admin": ["Liberu\\ExampleFilament\\ExampleFilamentPlugin"]
    }
}
```

Supported panel keys are `admin` and `app`. `App\Filament\ModulePlugins` composes plugin instances from enabled manifests, so panel providers do not hard-code module plugins. The companion package must require the domain package and consume its public services or contracts. It must also provide metadata, tests and a service provider like every runtime module.

Use an `app` declaration for tenant/user-facing functionality and `admin` for operational management. If both surfaces are required, declare both. Plugin identifiers must be unique within a panel.

## Routes, views, configuration and migrations

Each package service provider owns its resources and loads them only when the module manager registers that package. Namespace views and translations with the module name. Keep migration ownership clear; schema extensions of another package must be additive, dependency-declared and documented.

Avoid hidden filesystem scanning conventions for application behavior. Composer metadata, the manifest and explicit application enablement should be sufficient to reproduce the composition from the lockfile.

## Testing expectations

Every module has package-owned tests under `modules/{name}/tests`. At minimum, metadata tests verify Composer/manifest agreement, dependencies and the provider. Add focused unit or feature tests for the package's actual behavior, contracts, routes, migrations and presentation plugin where applicable.

The root suites also enforce cross-package boundaries and exercise the assembled application:

```bash
vendor/bin/pest --testsuite=Modules
vendor/bin/pest --testsuite=Architecture
vendor/bin/pest
```

Before releasing a module, update its changelog, validate the complete graph, run its focused tests and the root suite, then release it from its package repository and update the consuming application's Composer constraint and lockfile.
