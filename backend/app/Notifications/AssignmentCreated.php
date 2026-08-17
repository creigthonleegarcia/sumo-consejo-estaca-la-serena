<?php

namespace App\Notifications;

use App\Models\Assignment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AssignmentCreated extends Notification
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
            'type' => 'assignment_created',
            'title' => 'Nueva asignación',
            'message' => "Se te ha asignado: {$this->assignment->title}",
            'assignment_id' => $this->assignment->id,
        ];
    }
}
