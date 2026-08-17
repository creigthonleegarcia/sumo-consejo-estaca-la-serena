<?php

namespace App\Jobs;

use App\Models\Meeting;
use App\Models\MeetingMinute;
use App\Services\AiAudioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateMinuteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public Meeting $meeting,
        public string $transcription,
    ) {}

    public function handle(AiAudioService $aiService): void
    {
        Log::info("Generating minute for meeting {$this->meeting->id}");

        $result = $aiService->generateMinute($this->transcription, $this->meeting->name);

        MeetingMinute::updateOrCreate(
            ['meeting_id' => $this->meeting->id],
            [
                'transcription' => $this->transcription,
                'generated_minute' => $result['success'] ? $result['text'] : null,
                'status' => $result['success'] ? 'completed' : 'error',
            ]
        );

        if (!$result['success']) {
            Log::error("Minute generation failed: {$result['error']}");
        }
    }
}
