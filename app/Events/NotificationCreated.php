<?php

namespace App\Events;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Notification $notification, private User $user) {}
}
