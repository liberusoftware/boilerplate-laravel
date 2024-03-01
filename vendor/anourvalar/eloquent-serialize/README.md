# Serialization for Eloquent's QueryBuilder
Supports: Laravel 6 - Laravel 11

## Installation

```bash
composer require anourvalar/eloquent-serialize
```


## Usage

### Serialize
```php
$package = \EloquentSerialize::serialize(
    \App\User::query()
        ->with('userPhones')
        ->where('id', '>', '10')
        ->limit(20)
);
```


### Unserialize
```php
$builder = \EloquentSerialize::unserialize($package);

foreach ($builder->get() as $item) {
    // ...
}
```
