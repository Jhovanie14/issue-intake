<?php

namespace App\Console\Commands;

use App\Models\Issue;
use App\Services\EscalationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('issues:escalate')]
#[Description('Scan all open issues and apply escalation rules')]
class EscalateIssues extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(EscalationService $escalation): int
    {
        $issues = Issue::where('escalated', false)->get();
        $escalatedCount = 0;

        foreach ($issues as $issue) {
            $beforeState = $issue->escalated;
            $escalation->apply($issue);

            if ($issue->fresh()->escalated && !$beforeState) {
                $escalatedCount++;
                $this->line("  Escalated: #{$issue->id} - {$issue->title}");
            }
        }

        $this->info("Escalation scan complete. {$escalatedCount} issue(s) newly escalated.");

        return Command::SUCCESS;
    }
}
