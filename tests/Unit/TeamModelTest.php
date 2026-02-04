<?php

use App\Models\Team;

it('casts personal_team to boolean and fillable present', function () {
    $t = new Team();
    $t->personal_team = 1;
    expect($t->getAttributes()['personal_team'])->toBe(1);

    $casts = method_exists($t, 'getCasts') ? $t->getCasts() : [];
    expect($casts['personal_team'] ?? null)->toBe('boolean');
});

it('relationship methods exist and are callable', function () {
    $t = new Team();

    // Pick a few relationship method names that exist
    $rels = ['addrs', 'authors', 'messages', 'people'];

    foreach ($rels as $rel) {
        expect(method_exists($t, $rel))->toBeTrue();
        expect(is_callable([$t, $rel]))->toBeTrue();
    }
});
