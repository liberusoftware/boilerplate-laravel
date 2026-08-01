<?php

namespace Liberu\Messaging\Core\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Liberu\Messaging\Core\Models\Message;

class MessagePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the message.
     */
    public function view(Authenticatable $user, Message $message): bool
    {
        return (int) $user->getAuthIdentifier() === (int) $message->sender_id
            || (int) $user->getAuthIdentifier() === (int) $message->recipient_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(Authenticatable $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the message.
     */
    public function update(Authenticatable $user, Message $message): bool
    {
        return (int) $user->getAuthIdentifier() === (int) $message->sender_id;
    }

    /**
     * Determine whether the user can delete the message.
     */
    public function delete(Authenticatable $user, Message $message): bool
    {
        return (int) $user->getAuthIdentifier() === (int) $message->sender_id
            || (int) $user->getAuthIdentifier() === (int) $message->recipient_id;
    }
}
