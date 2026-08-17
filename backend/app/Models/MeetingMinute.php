<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinute extends Model
{
    protected $fillable = [
        'meeting_id',
        'audio_path',
        'transcription',
        'agile_minute',
        'executive_summary',
        'processing_status',
        'processing_error',
    ];

    protected function casts(): array
    {
        return [
            'transcription' => 'array',
            'agile_minute' => 'array',
        ];
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function isProcessing(): bool
    {
        return $this->processing_status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->processing_status === 'completed';
    }

    public function hasFailed(): bool
    {
        return $this->processing_status === 'failed';
    }
}
