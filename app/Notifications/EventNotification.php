<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EventNotification extends Notification
{
    use Queueable;

    // Add constants for notification types
    const TYPE_JOIN = 'participant_joined';

    const TYPE_LEAVE = 'participant_left';

    const TYPE_INVITE = 'event_invite';

    protected $event;

    protected $type;

    protected $message;

    protected $actor;  // The user who performed the action

    public function __construct(Event $event, string $type, string $message, ?User $actor = null)
    {
        $this->event = $event;
        $this->type = $type;  // 'invite', 'reminder', etc.
        $this->message = $message;
        $this->actor = $actor;
    }

    public function via($notifiable)
    {
        return ['database'];  // Only store in database for now
    }

    public function toArray($notifiable)
    {
        return [
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'type' => $this->type,
            'message' => $this->message,
            'actor' => $this->actor ? [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
            ] : null,
        ];
    }
}
