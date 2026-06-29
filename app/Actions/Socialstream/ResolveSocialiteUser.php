<?php

namespace App\Actions\Socialstream;

use JoelButcher\Socialstream\Contracts\ResolvesSocialiteUsers;
use JoelButcher\Socialstream\Socialstream;
use Laravel\Socialite\AbstractUser;
use Laravel\Socialite\Contracts\User;
use Laravel\Socialite\Facades\Socialite;

class ResolveSocialiteUser implements ResolvesSocialiteUsers
{
    /**
     * Resolve the user for a given provider.
     */
    public function resolve(string $provider): User
    {
        $user = Socialite::driver($provider)->user();

        if (Socialstream::generatesMissingEmails() && $user instanceof AbstractUser) {
            $domain = config('app.domain');

            $user->email = $user->getEmail() ?? sprintf(
                '%s@%s%s',
                $user->getId(),
                $provider,
                is_string($domain) ? $domain : ''
            );
        }

        return $user;
    }
}
