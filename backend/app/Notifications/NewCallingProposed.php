<?php

namespace App\Notifications;

use App\Models\Calling;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewCallingProposed extends Notification
{
    use Queueable;

    public function __construct(public Calling $calling) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'calling_proposed',
            'title' => 'Nuevo llamamiento propuesto',
            'message' => "{$this->calling->member_name} como {$this->calling->calling_name} — tu voto es requerido.",
            'calling_id' => $this->calling->id,
        ];
    }
}
