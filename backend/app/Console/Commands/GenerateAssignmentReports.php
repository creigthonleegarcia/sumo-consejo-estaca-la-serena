<?php

namespace App\Console\Commands;

use App\Models\Assignment;
use App\Models\StewardshipReport;
use Illuminate\Console\Command;

class GenerateAssignmentReports extends Command
{
    protected $signature = 'reports:generate';
    protected $description = 'Genera informes de mayordomía en blanco para asignaciones activas (scheduler quincenal)';

    public function handle(): int
    {
        $assignments = Assignment::where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->get();

        $periodStart = now()->subDays(14)->startOfDay();
        $periodEnd = now()->endOfDay();

        $created = 0;

        foreach ($assignments as $assignment) {
            // Verificar que no exista ya un reporte para este período
            $exists = StewardshipReport::where('assignment_id', $assignment->id)
                ->where('period_start', '>=', $periodStart)
                ->exists();

            if (!$exists) {
                StewardshipReport::create([
                    'user_id' => $assignment->assigned_to,
                    'assignment_id' => $assignment->id,
                    'content' => '',
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'submitted_at' => null,
                ]);
                $created++;
            }
        }

        $this->info("Se generaron {$created} informes de mayordomía.");

        return Command::SUCCESS;
    }
}
