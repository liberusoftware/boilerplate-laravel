<?php

namespace Liberu\Foundation\Organizations\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Liberu\Foundation\Organizations\Contracts\OrganizationActor;
use Liberu\Foundation\Organizations\Models\Team;

class TeamPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(OrganizationActor $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(OrganizationActor $user, Team $team): bool
    {
        return $user->belongsToTeam($team);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(OrganizationActor $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(OrganizationActor $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can add team members.
     */
    public function addTeamMember(OrganizationActor $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can update team member permissions.
     */
    public function updateTeamMember(OrganizationActor $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can remove team members.
     */
    public function removeTeamMember(OrganizationActor $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(OrganizationActor $user, Team $team): bool
    {
        return $user->ownsTeam($team);
    }
}
