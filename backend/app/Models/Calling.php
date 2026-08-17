<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Calling extends Model
{
    protected $fillable = [
        'proposed_by',
        'member_name',
        'calling_name',
        'ward',
        'notes',
        'status',
        'voting_deadline',
    ];

    protected function casts(): array
    {
        return [
            'voting_deadline' => 'datetime',
        ];
    }

    public function proposer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(CallingVote::class);
    }

    // --- Helpers de votación ---

    public function isVotingOpen(): bool
    {
        return $this->status === 'pending' && $this->voting_deadline->isFuture();
    }

    public function isVotingClosed(): bool
    {
        return $this->voting_deadline->isPast() || $this->status !== 'pending';
    }

    public function approvalCount(): int
    {
        return $this->votes()->where('vote', 'approve')->count();
    }

    public function disapprovalCount(): int
    {
        return $this->votes()->where('vote', 'disapprove')->count();
    }

    public function totalVotes(): int
    {
        return $this->votes()->count();
    }

    /**
     * Calcula si tiene quórum de 2/3 para aprobar.
     */
    public function hasQuorum(int $totalEligibleVoters): bool
    {
        $approvals = $this->approvalCount();
        return $approvals >= ceil($totalEligibleVoters * 2 / 3);
    }

    public function hasUserVoted(int $userId): bool
    {
        return $this->votes()->where('user_id', $userId)->exists();
    }
}
