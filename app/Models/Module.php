<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'name',
        'version',
        'description',
        'enabled',
        'dependencies',
        'config',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'dependencies' => 'array',
        'config' => 'array',
    ];

    public static function findByName(string $name): ?self
    {
        return static::where('name', $name)->first();
    }
}
