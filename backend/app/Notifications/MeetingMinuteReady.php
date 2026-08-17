<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MeetingMinuteReady extends Notification
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
            'type' => 'minute_ready',
            'title' => 'Acta disponible',
            'message' => "El acta de la reunión '{$this->meeting->name}' ya está disponible.",
            'meeting_id' => $this->meeting->id,
        ];
    }
}
