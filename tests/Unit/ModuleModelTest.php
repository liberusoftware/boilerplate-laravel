<?php

use App\Models\Module;

it('casts fields correctly', function () {
    $m = Module::create([
        'name' => 'CastModule',
        'version' => '1.2',
        'description' => 'x',
        'enabled' => true,
        'dependencies' => ['A','B'],
        'config' => ['k' => 'v'],
    ]);

    $found = Module::findByName('CastModule');
    expect($found)->not->toBeNull();
    expect($found->enabled)->toBeTrue();
    expect(is_array($found->dependencies))->toBeTrue();
    expect($found->config['k'])->toBe('v');
});
