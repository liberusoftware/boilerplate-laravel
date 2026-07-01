<?php

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

function passwordFails(string $password): bool
{
    return Validator::make(['password' => $password], ['password' => Password::default()])->fails();
}

it('rejects short passwords (min 12)', function () {
    expect(passwordFails('Ab1!short'))->toBeTrue();   // 9 chars, under the 12 minimum
    expect(passwordFails('Ab1!eleven!'))->toBeTrue(); // 11 chars — just under
    expect(passwordFails('Ab1!twelve!X'))->toBeFalse(); // 12 chars, complex — the boundary passes
});

it('rejects passwords missing complexity', function () {
    expect(passwordFails('alllowercaseletters'))->toBeTrue();   // no upper/number/symbol
    expect(passwordFails('NoNumbersOrSymbols'))->toBeTrue();     // no number/symbol
    expect(passwordFails('nosymbols1234abcd'))->toBeTrue();      // no symbol
});

it('accepts a strong password', function () {
    expect(passwordFails('Str0ng-Passphrase!9'))->toBeFalse();
});
