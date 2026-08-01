<?php

namespace Liberu\Foundation\Identity\Socialstream\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Liberu\Foundation\Identity\Socialstream\Contracts\ConnectedAccountOwner;
use Liberu\Foundation\Identity\Socialstream\Models\ConnectedAccount;

class ConnectedAccountPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(ConnectedAccountOwner $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(ConnectedAccountOwner $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->ownsConnectedAccount($connectedAccount);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(ConnectedAccountOwner $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(ConnectedAccountOwner $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->ownsConnectedAccount($connectedAccount);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(ConnectedAccountOwner $user, ConnectedAccount $connectedAccount): bool
    {
        return $user->ownsConnectedAccount($connectedAccount);
    }
}
