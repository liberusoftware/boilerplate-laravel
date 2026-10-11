# Contributing to Liberu Boilerplate

Thank you for your interest in contributing to Liberu Boilerplate. This document outlines the process for contributing to the modular Laravel foundation used by other Liberu applications.

## Before You Start

- **Branch from `main`**: Always create a new branch from the latest `main` branch for your work.
- **Focused changes**: Keep pull requests focused on a single feature or fix. Separate concerns into separate PRs.
- **Search first**: Check existing issues and PRs to avoid duplicate work.

## Development Setup

1. **Clone the repository**:
   ```bash
   git clone git@github.com:liberusoftware/boilerplate-laravel.git
   cd boilerplate-laravel
   ```

2. **Install PHP dependencies**:
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**:
   ```bash
   npm install
   ```

4. **Environment configuration**:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Run migrations and seeders**:
   ```bash
   php artisan migrate --seed
   ```

6. **Build frontend assets**:
   ```bash
   npm run dev
   ```

## Running Tests

### PHP Tests
```bash
vendor/bin/pest
```

### Code Style
```bash
vendor/bin/pint --test
```

To automatically fix code style issues:
```bash
vendor/bin/pint
```

### Static Analysis
```bash
vendor/bin/phpstan analyse
```

## Code Style

- **PHP**: Follow PSR-12 coding standards, enforced by Laravel Pint.
- **Frontend**: Follow the project's existing conventions for Vue/React/TypeScript (if applicable).
- **Naming**: Use descriptive variable and method names. Follow Laravel naming conventions for models, controllers, and migrations.
- **Documentation**: Add PHPDoc blocks for classes and complex methods. Document module boundaries and extension points clearly to prevent regressions.

## Pull Request Process

1. **Push your branch** to the repository.
2. **Open a pull request** against the `main` branch.
3. **Fill in the PR template** with a clear description of changes, including:
   - What was changed and why
   - Related issue numbers
   - Any migration or configuration changes required
4. **Ensure CI passes**: All tests and static analysis must pass before review.
5. **Address review feedback**: Respond to comments and make requested changes promptly.
6. **Approval**: At least one maintainer approval is required before merge.

## Documentation Requirements

- **README updates**: If your change adds or changes user-facing features, update the README.
- **Inline comments**: Add comments for complex module logic, especially around service provider registration and module discovery.
- **CHANGELOG**: Add an entry under the `[Unreleased]` section describing your change.

## Security Reporting

Please see [SECURITY.md](./SECURITY.md) for information on reporting security vulnerabilities. Do not report security issues through public GitHub issues.
