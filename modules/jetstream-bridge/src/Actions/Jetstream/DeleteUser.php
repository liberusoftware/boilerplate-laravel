<?php

namespace Liberu\Foundation\JetstreamBridge\Actions\Jetstream;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Laravel\Jetstream\Contracts\DeletesTeams;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * The team deleter implementation.
     *
     * @var DeletesTeams
     */
    protected $deletesTeams;

    /**
     * Create a new action instance.
     *
     * @return void
     */
    public function __construct(DeletesTeams $deletesTeams)
    {
        $this->deletesTeams = $deletesTeams;
    }

    /**
     * Delete the given user.
     *
     * @param  mixed  $user
     * @return void
     */
    public function delete($user)
    {
        if (! $user instanceof User) {
            throw new InvalidArgumentException('Only Laravel user models can be deleted.');
        }

        DB::transaction(function () use ($user) {
            $this->deleteTeams($user);
            $user->deleteProfilePhoto();
            $user->connectedAccounts->each->delete();
            $user->tokens->each->delete();
            $user->delete();
        });
    }

    /**
     * Delete the teams and team associations attached to the user.
     *
     * @return void
     */
    protected function deleteTeams(User $user)
    {
        $user->teams()->detach();

        $user->ownedTeams->each(function ($team) {
            $this->deletesTeams->delete($team);
        });
    }
}
