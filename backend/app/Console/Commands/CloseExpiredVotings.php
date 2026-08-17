<?php

namespace App\Console\Commands;

use App\Services\VotingService;
use Illuminate\Console\Command;

class CloseExpiredVotings extends Command
{
    protected $signature = 'votings:close-expired';
    protected $description = 'Cierra votaciones de llamamientos cuyo deadline ya pasó';

    public function handle(VotingService $votingService): int
    {
        $closed = $votingService->closeExpiredVotings();

        $this->info("Se cerraron {$closed} votaciones expiradas.");

        return Command::SUCCESS;
    }
}
