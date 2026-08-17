<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallingVote extends Model
{
    protected $fillable = [
        'calling_id',
        'user_id',
        'vote',
        'voted_at',
    ];

    protected function casts(): array
    {
        return [
            'voted_at' => 'datetime',
        ];
    }

    public function calling(): BelongsTo
    {
        return $this->belongsTo(Calling::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
