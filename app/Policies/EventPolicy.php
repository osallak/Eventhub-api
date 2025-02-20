<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /**
     * Determine if the user can update the event
     */
    public function update(User $user, Event $event): bool
    {
        return $user->id === $event->creator_id;
    }

    /**
     * Determine if the user can delete the event
     */
    public function delete(User $user, Event $event): bool
    {
        return $user->id === $event->creator_id;
    }
}
