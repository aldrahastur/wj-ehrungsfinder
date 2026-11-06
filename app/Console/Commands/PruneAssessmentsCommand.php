<?php

namespace App\Console\Commands;

use App\Models\Assessment;
use Illuminate\Console\Command;

class PruneAssessmentsCommand extends Command
{
    protected $signature = 'ehrungsfinder:prune';

    protected $description = 'Löscht anonyme Vorprüfungen nach Ablauf der Aufbewahrungsfrist';

    public function handle(): int
    {
        $days = (int) config('ehrungsfinder.assessment_retention_days');
        $deleted = Assessment::query()->where('created_at', '<', now()->subDays($days))->delete();

        $this->info("{$deleted} Vorprüfungen gelöscht (älter als {$days} Tage).");

        return self::SUCCESS;
    }
}
