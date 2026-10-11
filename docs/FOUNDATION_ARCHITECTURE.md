# Foundation Architecture

## Overview

The boilerplate serves as the reference implementation for the Laravel platform. It provides the application composition pattern, module system, theme system, authentication, audit logging, settings, search, notifications, import/export, webhooks, and API layer that all other projects build upon.

## Application Composition Pattern

The application follows a modular monolith pattern with three layers:

```
┌─────────────────────────────────────────┐
│                  Host                    │
│  ┌─────────────┐  ┌──────────────────┐  │
│  │   Module A  │  │    Module B      │  │
│  │  ┌────────┐ │  │  ┌────────────┐  │  │
│  │  │ Theme  │ │  │  │   Theme    │  │  │
│  │  └────────┘ │  │  └────────────┘  │  │
│  └─────────────┘  └──────────────────┘  │
└─────────────────────────────────────────┘
```

- **Host**: The root application that boots modules and themes
- **Module**: A self-contained domain unit with routes, views, models, and services
- **Theme**: A presentation layer that can be inherited and overridden

## Module System

### Discovery

Modules are auto-discovered from the `modules/` directory. Each module contains a `module.json` manifest:

```json
{
    "name": "Blog",
    "slug": "blog",
    "version": "1.0.0",
    "dependencies": ["media"],
    "providers": ["BlogServiceProvider"]
}
```

### Registration

Modules register themselves through service providers that are loaded by the module manager during application boot.

### Dependencies

Module dependencies are resolved at boot time. Circular dependencies are detected and rejected with a clear error message.

### Module Structure

```
modules/Blog/
├── module.json
├── src/
│   ├── BlogServiceProvider.php
│   ├── Models/
│   ├── Http/
│   ├── Services/
│   └── database/
├── resources/
│   ├── views/
│   └── lang/
└── routes/
    ├── web.php
    └── api.php
```

## Theme System

### Inheritance

Themes support inheritance chains. A child theme overrides parent templates by placing files at matching paths:

```
themes/child/views/blog/index.blade.php  → overrides parent
```

### Fallback Resolution

View resolution follows the chain: child theme → parent theme → module default → host default.

### Assets

Theme assets are published to `public/themes/{theme}/` and versioned for cache busting.

### Configuration

Theme configuration is stored in `themes/{theme}/theme.json` and merged with module and host config at runtime.

## Authentication and Authorization

### Jetstream Integration

Authentication is handled by Laravel Jetstream with support for:

- Email/password login
- Two-factor authentication
- Session management
- API token management via Sanctum

### Roles and Permissions

Role-based access control is implemented with a `roles` and `permissions` pivot structure:

```php
$user->assignRole('editor');
$user->hasPermission('posts.create');
```

### Teams

Multi-tenancy is supported through Jetstream teams. Each team has its own role assignments and resource scoping.

## Audit Trail and Activity Logging

### Activity Log

All significant actions are logged to an `activity_log` table:

| Column | Description |
|--------|-------------|
| `causer_id` | User who performed the action |
| `subject_type` | Polymorphic subject model |
| `subject_id` | Subject model ID |
| `event` | Action name (created, updated, deleted) |
| `properties` | JSON snapshot of changes |
| `ip_address` | Request IP |
| `user_agent` | Request user agent |

### Querying

```php
Activity::forSubject($post)->get();
Activity::causedBy($user)->where('event', 'deleted')->get();
```

## Settings and Feature Flags

### Settings

Settings are stored in a key-value store with type casting and caching:

```php
Setting::set('site.name', 'My Application');
Setting::get('site.name', 'Default Name');
```

### Feature Flags

Feature flags support boolean, string, and JSON values with environment and user-targeting:

```php
Feature::enable('new_dashboard', for: $user);
Feature::isEnabled('new_dashboard');
```

## Search Architecture

### Driver Abstraction

```php
interface SearchDriverInterface
{
    public function index(string $type, array $document): void;
    public function search(string $query, array $filters = []): SearchResults;
    public function delete(string $type, string $id): void;
}
```

### Supported Drivers

- **Database**: Full-text search using native DB indexes
- **Meilisearch**: External search server with typo tolerance
- **Elasticsearch**: Enterprise search with aggregations

### Indexing

Models implement `SearchableInterface` to define their indexable fields. Indexing is queued and can be reindexed in bulk.

## Notification System

### Channels

Notifications support multiple delivery channels:

| Channel | Provider |
|---------|----------|
| Mail | SMTP, SES, Mailgun |
| Database | Stored in notifications table |
| Broadcast | Pusher, Ably |
| SMS | Twilio |
| Slack | Incoming webhooks |

### Templates

Notification templates are stored in the database with variable interpolation and per-channel formatting.

### Preferences

Users configure notification preferences per channel and notification type.

## Import/Export Framework

### Import

```php
Importer::make('users')
    ->file($path)
    ->map(['email' => 'Email Address', 'name' => 'Full Name'])
    ->validateWith(UserImportValidator::class)
    ->queue();
```

### Export

```php
Exporter::make('invoices')
    ->format('xlsx')
    ->filter(['status' => 'paid'])
    ->queue();
```

### Supported Formats

CSV, XLSX, JSON, XML with chunked processing for large datasets.

## Webhook System

### Outbound Webhooks

```php
Webhook::dispatch('order.created', $order)
    ->to('https://partner.example.com/webhook')
    ->withRetry(3)
    ->withSignature('secret');
```

### Inbound Webhooks

Inbound webhooks are registered as API endpoints with signature verification, deduplication, and event dispatch.

## API Layer

### Sanctum Authentication

API authentication uses Laravel Sanctum with token-based and SPA cookie-based auth.

### Versioning

API routes are versioned via URL prefix (`/api/v1/`, `/api/v2/`) with deprecation headers and sunset dates.

### Rate Limiting

Rate limits are configurable per API key, user, and endpoint group:

```php
RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()));
```

## Module Boundaries and Extension Points

| Boundary | Extension Point | Mechanism |
|----------|----------------|-----------|
| Modules | Custom modules | `module.json` + service provider |
| Themes | Custom themes | Theme directory + inheritance chain |
| Auth | Custom guards, providers | Laravel auth extension |
| Audit | Custom activity loggers | `ActivityLoggerInterface` binding |
| Settings | Custom setting types | `SettingTypeInterface` binding |
| Search | Custom drivers | `SearchDriverInterface` binding |
| Notifications | Custom channels | `NotificationChannelInterface` binding |
| Import/Exporters | Custom formats | `ImportHandlerInterface` / `ExportHandlerInterface` binding |
| Webhooks | Custom webhook events | Event dispatcher + webhook registry |
| API | Custom API resources | Route registration + resource classes |
