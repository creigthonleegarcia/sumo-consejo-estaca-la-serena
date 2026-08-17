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

class ProcessAudioJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        public Meeting $meeting,
        public string $audioPath,
    ) {}

    public function handle(AiAudioService $aiService): void
    {
        Log::info("Processing audio for meeting {$this->meeting->id}");

        // Step 1: Transcribe
        $transcription = $aiService->transcribe($this->audioPath);

        if (!$transcription['success']) {
            Log::error("Transcription failed: {$transcription['error']}");
            $this->createMinute('error', null, "Error en transcripción: {$transcription['error']}");
            return;
        }

        // Step 2: Generate minute
        GenerateMinuteJob::dispatch($this->meeting, $transcription['text']);
    }

    private function createMinute(string $status, ?string $content, ?string $error = null): void
    {
        MeetingMinute::updateOrCreate(
            ['meeting_id' => $this->meeting->id],
            [
                'transcription' => $error,
                'generated_minute' => $content,
                'status' => $status,
            ]
        );
    }
}
