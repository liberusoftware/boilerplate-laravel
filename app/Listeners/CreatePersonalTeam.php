<?php

namespace App\Listeners;

use App\Services\TeamManagementService;
use Illuminate\Auth\Events\Registered;

class CreatePersonalTeam
{
    protected $teamManagementService;

    public function __construct(TeamManagementService $teamManagementService)
    {
        $this->teamManagementService = $teamManagementService;
    }

    public function handle(Registered $event): void
    {
        $this->teamManagementService->assignUserToDefaultTeam($event->user);
    }
}