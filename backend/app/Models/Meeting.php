<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Meeting extends Model
{
    protected $fillable = [
        'created_by',
        'type',
        'modality',
        'location_or_url',
        'platform',
        'name',
        'agenda',
        'scheduled_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(MeetingInvitation::class);
    }

    public function minute(): HasOne
    {
        return $this->hasOne(MeetingMinute::class);
    }

    // --- Helpers ---

    public function isPresidencia(): bool
    {
        return $this->type === 'presidencia';
    }

    public function isSumoConsejo(): bool
    {
        return $this->type === 'sumo_consejo';
    }

    public function isPresencial(): bool
    {
        return $this->modality === 'presencial';
    }

    public function attendingCount(): int
    {
        return $this->invitations()->where('response', 'attending')->count();
    }

    public function confirmedAttendees()
    {
        return $this->invitations()
            ->where('response', 'attending')
            ->with('user')
            ->get()
            ->pluck('user');
    }
}
