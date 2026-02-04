<?php

namespace App\Actions\Jetstream;

// This file exists for backwards compatibility with stubbed installers that copy
// the Jetstream "DeleteUserWithTeams" stub. The canonical class is
// App\Actions\Jetstream\DeleteUser which implements the Teams-aware behavior.

class DeleteUserWithTeams extends DeleteUser
{
    // No-op; extends the canonical DeleteUser implementation.
}
