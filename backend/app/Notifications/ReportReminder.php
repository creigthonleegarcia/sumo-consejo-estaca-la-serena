<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportReminder extends Notification
{
    use Queueable;

    public function __construct(public Assignment $assignment) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_reminder',
            'title' => 'Recordatorio de informe',
            'message' => "Tienes un informe pendiente para: {$this->assignment->title}",
            'assignment_id' => $this->assignment->id,
        ];
    }
}
