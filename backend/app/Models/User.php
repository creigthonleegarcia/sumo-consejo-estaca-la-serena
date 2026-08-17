<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'phone', 'role', 'avatar', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // --- Helpers de rol ---

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPresidencia(): bool
    {
        return $this->role === 'presidencia';
    }

    public function isSecretario(): bool
    {
        return $this->role === 'secretario';
    }

    public function isSumoConsejo(): bool
    {
        return $this->role === 'sumo_consejo';
    }

    // --- Relaciones ---

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function createdAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'created_by');
    }

    public function receivedAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assigned_to');
    }

    public function stewardshipReports(): HasMany
    {
        return $this->hasMany(StewardshipReport::class);
    }

    public function callingVotes(): HasMany
    {
        return $this->hasMany(CallingVote::class);
    }

    public function meetingInvitations(): HasMany
    {
        return $this->hasMany(MeetingInvitation::class);
    }
}
