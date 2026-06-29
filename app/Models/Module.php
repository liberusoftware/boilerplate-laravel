<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $version
 * @property string|null $description
 * @property bool $enabled
 * @property array<int|string, mixed>|null $dependencies
 * @property array<string, mixed>|null $config
 */
class Module extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'version',
        'description',
        'enabled',
        'dependencies',
        'config',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'dependencies' => 'array',
            'config' => 'array',
        ];
    }

    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }
}
