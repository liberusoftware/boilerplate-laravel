# Liberu Software — Laravel 12 SaaS Boilerplate

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
![PHP 8.4](https://img.shields.io/badge/PHP-8.4-informational?style=flat&logo=php&color=4f5b93)
![Laravel 12](https://img.shields.io/badge/Laravel-12-informational?style=flat&logo=laravel&color=ef3b2d)
![Filament 4.0](https://img.shields.io/badge/Filament-4.0-informational?style=flat)
![Livewire 3.5](https://img.shields.io/badge/Livewire-3.5-informational?style=flat&color=fb70a9)

[![Install](https://github.com/liberusoftware/boilerplate-laravel/actions/workflows/install.yml/badge.svg)](hhttps://github.com/liberusoftware/boilerplate-laravel/actions/workflows/install.yml)
[![Tests](https://github.com/liberusoftware/boilerplate-laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/liberusoftware/boilerplate-laravel/workflows/tests.yml)
[![Docker](https://github.com/liberusoftware/boilerplate-laravel/actions/workflows/main.yml/badge.svg)](https://github.com/liberusoftware/boilerplate-laravel/actions/workflows/main.yml)
[![codecov](https://codecov.io/gh/liberusoftware/boilerplate-laravel/graph/badge.svg?token=K7TWB1QF1L)](https://codecov.io/gh/liberusoftware/boilerplate-laravel)

A production-ready SaaS starter built with Laravel 12, PHP 8.4, Filament, Livewire, Jetstream and Socialite — designed to kickstart multi-tenant or single-tenant SaaS applications.

Website: https://www.liberu.co.uk

---

Table of contents
- Overview
- Key features
- Prerequisites
- Standard (local) install
- Docker / Sail install
- Running tests
- Troubleshooting
- Contributing
- License

---

Overview
--------
This repository provides a modern Laravel-based boilerplate with common SaaS building blocks: authentication (Jetstream), admin (Filament), real-time interactions (Livewire), social login (Socialite), user profiles, notifications, messaging and more. It's structured to be extensible and production-oriented.

Key features
------------
- Jetstream authentication and user profiles
- Filament admin panel for resource management
- Livewire-powered UI for reactive components
- Social login via Socialite
- Database seeders and example data (optional)
- Docker and Laravel Sail support for containerized development

Prerequisites
-------------
- PHP 8.4
- Composer
- Node.js (recommended: LTS) and npm or yarn (for front-end assets)
- MySQL / PostgreSQL or another supported DB
- Docker (if using Docker or Sail)

Standard (local) install
------------------------
These steps assume you want to run the application on your machine (not in Docker). They are intentionally clear and safe — back up any existing .env before overriding.

1. Clone the repo
   ```bash
   git clone https://github.com/liberusoftware/boilerplate-laravel.git
   cd boilerplate-laravel
   ```

2. Install PHP dependencies
   ```bash
   composer install
   ```

3. Copy the example env and configure
   ```bash
   cp .env.example .env
   # Edit .env to set DB_*, APP_URL and other settings
   ```

4. Generate application key
   ```bash
   php artisan key:generate
   ```

5. Install front-end dependencies (if you plan to build assets)
   ```bash
   npm install
   # or
   yarn
   ```

6. Build front-end assets (development or production)
   ```bash
   npm run dev   # development
   npm run build # production
   ```

7. Run migrations and seeders
   - IMPORTANT: Seeders will add example data. Skip seeding if you don't want that.
   ```bash
   php artisan migrate
   # When you want seed data:
   php artisan migrate --seed
   ```

8. Run the application
   ```bash
   php artisan serve --host=127.0.0.1 --port=8000
   ```
   Open: http://127.0.0.1:8000 (or your configured APP_URL)

Optional: Setup script
- This repository includes a convenience script `setup.sh` that runs common steps automatically. The script will prompt before overwriting `.env`. Use it only if you accept the actions it performs.
```bash
./setup.sh
```

Notes
- Configure mail and social provider settings in `.env` for production use.
- If you use a different DB (e.g., PostgreSQL), update `.env` accordingly.

Docker install
--------------
Two recommended Docker approaches are provided: manual Docker image and Laravel Sail.

A. Using the repository Dockerfile (image build)
1. Build the image from the project root:
   ```bash
   docker build -t boilerplate-laravel .
   ```
2. Create an env file for the container or use your `.env`:
   ```bash
   # Ensure .env contains correct DB and APP_URL values
   ```
3. Run the container (example: mapped port 8000):
   ```bash
   docker run --name boilerplate-app --env-file .env -p 8000:8000 -d boilerplate-laravel
   ```
4. Run migrations inside the running container:
   ```bash
   docker exec -it boilerplate-app php artisan migrate --seed
   ```
5. Visit: http://localhost:8000

Notes for Docker image:
- When building a standalone image, ensure your Dockerfile handles running queue workers, scheduler, and any entrypoint tasks you need. For development, using docker run with volume mounts can be more convenient.

B. Recommended: Use Laravel Sail (Docker Compose wrapper)
1. Start Sail from project root:
   ```bash
   # Linux / macOS
   ./vendor/bin/sail up -d
   # Windows (PowerShell)
   vendor/bin/sail up -d
   ```
2. Run migrations and seeders using Sail:
   ```bash
   ./vendor/bin/sail artisan migrate --seed
   ```
3. Build front-end assets inside Sail (if needed):
   ```bash
   ./vendor/bin/sail npm install
   ./vendor/bin/sail npm run dev
   ```
4. Visit: http://localhost

Sail notes:
- Sail creates a complete development environment with services (DB, Redis, mailhog) and is the recommended containerized development workflow.

Running tests
-------------
This repository includes automated tests (see /tests). Run tests with:

```bash
# Local (uses Pest)
composer install --dev
vendor/bin/pest

# Or via Laravel's test runner which proxies to Pest
php artisan test

# With Sail
./vendor/bin/sail test
```

Troubleshooting
---------------
- "Permission denied" when running storage or bootstrap cache: adjust filesystem ownership
  ```bash
  sudo chown -R $USER:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
  ```
- DB connection errors: verify `.env` DB_* values and ensure the DB service is running (Sail or local).
- If assets not updating: clear caches
  ```bash
  php artisan config:clear
  php artisan cache:clear
  php artisan view:clear
  ```

Contributing
------------
Contributions are welcome. If you'd like to contribute:
1. Fork the repository
2. Create a feature branch
3. Make changes and add tests where applicable
4. Open a pull request describing your changes

Please follow the repository's code style and include clear commit messages.

License
-------
This project is licensed under the MIT License — use it in personal or commercial projects.

Credits & Related Projects
--------------------------
- Liberu Software: https://www.liberu.co.uk

## Related projects

The Liberu ecosystem contains a number of companion repositories and packages that extend or demonstrate functionality used in this boilerplate. Below is a concise, professional list of those projects with quick descriptions — follow the links to learn more or to contribute.

| Project | Repository | Short description |
|---|---:|---|
| Accounting | [liberu-accounting/accounting-laravel](https://github.com/liberu-accounting/accounting-laravel) | Accounting and invoicing features tailored for Laravel applications. |
| Automation | [liberu-automation/automation-laravel](https://github.com/liberu-automation/automation-laravel) | Automation tooling and workflow integrations for Laravel projects. |
| Billing | [liberu-billing/billing-laravel](https://github.com/liberu-billing/billing-laravel) | Subscription and billing management integrations (payments, invoices). |
| Boilerplate (core) | [liberusoftware/boilerplate](https://github.com/liberusoftware/boilerplate) | Core starter and shared utilities used across Liberu projects. |
| Browser Game | [liberu-browser-game/browser-game-laravel](https://github.com/liberu-browser-game/browser-game-laravel) | Example Laravel-based browser game platform and mechanics. |
| CMS | [liberu-cms/cms-laravel](https://github.com/liberu-cms/cms-laravel) | Content management features and modular page administration. |
| Control Panel | [liberu-control-panel/control-panel-laravel](https://github.com/liberu-control-panel/control-panel-laravel) | Administration/control-panel components for managing services. |
| CRM | [liberu-crm/crm-laravel](https://github.com/liberu-crm/crm-laravel) | Customer relationship management features and integrations. |
| E‑commerce | [liberu-ecommerce/ecommerce-laravel](https://github.com/liberu-ecommerce/ecommerce-laravel) | E‑commerce storefront, product and order management. |
| Genealogy | [liberu-genealogy/genealogy-laravel](https://github.com/liberu-genealogy/genealogy-laravel) | Family tree and genealogy features built on Laravel. |
| Maintenance | [liberu-maintenance/maintenance-laravel](https://github.com/liberu-maintenance/maintenance-laravel) | Scheduling, tracking and reporting for maintenance tasks. |
| Real Estate | [liberu-real-estate/real-estate-laravel](https://github.com/liberu-real-estate/real-estate-laravel) | Property listings and real-estate management features. |
| Social Network | [liberu-social-network/social-network-laravel](https://github.com/liberu-social-network/social-network-laravel) | Social features, profiles, feeds and messaging for Laravel apps. |

If you maintain or use one of these projects and would like a more detailed description or a different categorisation, open an issue or submit a pull request and we'll update the list. Contributions and cross-repo collaboration are warmly encouraged.
