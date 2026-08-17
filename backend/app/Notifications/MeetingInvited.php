<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MeetingInvited extends Notification
{
    use Queueable;

    public function __construct(public Meeting $meeting) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'meeting_invited',
            'title' => 'Invitación a reunión',
            'message' => "Has sido invitado a: {$this->meeting->name} — {$this->meeting->scheduled_at->format('d/m/Y H:i')}",
            'meeting_id' => $this->meeting->id,
        ];
    }
}
