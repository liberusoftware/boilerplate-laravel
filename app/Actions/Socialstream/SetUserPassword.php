<?php

namespace App\Actions\Socialstream;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
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
        assert($user instanceof User);

        Validator::make($input, [
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ])->validateWithBag('setPassword');

        $user->forceFill([
            'password' => Hash::make(is_string($input['password']) ? $input['password'] : ''),
        ])->save();
    }
}
