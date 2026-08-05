<?php

namespace Liberu\Foundation\Identity\Socialstream\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;
use JoelButcher\Socialstream\Contracts\SetsUserPasswords;

class SetUserPassword implements SetsUserPasswords
{
    /**
     * Validate and update the user's password.
     *
     * @param  array<string, mixed>  $input
     */
    public function set(mixed $user, array $input): void
    {
        if (! $user instanceof Model) {
            throw new InvalidArgumentException('Passwords can only be set on Eloquent user models.');
        }

        Validator::make($input, [
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ])->validateWithBag('setPassword');

        $user->forceFill([
            'password' => Hash::make(is_string($input['password']) ? $input['password'] : ''),
        ])->save();
    }
}
