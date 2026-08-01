<?php

namespace Liberu\Foundation\Organizations\Contracts;

interface OrganizationActor
{
    /** @param mixed $team */
    public function belongsToTeam($team);

    /** @param mixed $team */
    public function ownsTeam($team);
}
