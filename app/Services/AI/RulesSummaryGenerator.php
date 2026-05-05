<?php

namespace App\Services\AI;

use App\Contracts\SummaryGenerator;
use App\DTO\SummaryResult;
use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Issue;
use Illuminate\Support\Str;

class RulesSummaryGenerator implements SummaryGenerator
{
    public function generate(Issue $issue): SummaryResult
    {
        return new SummaryResult(
            summary: $this->buildSummary($issue),
            action: $this->buildAction($issue),
            source: 'rules',
        );
    }

    private function BuildSummary(Issue $issue): string
    {
        $firstSentence = Str::of($issue->description)->trim()->before('.')->limit(140)->toString();

        $execerpt = $firstSentence !== '' ? $firstSentence : Str::limit($issue->description, 140);

        return sprintf(
            '[%s | %s] %s',
            ucfirst($issue->priority->value),
            $issue->category,
            $execerpt
        );
    }

    private function BuildAction(Issue $issue): string
    {
        return match (true) {
            $issue->priority === Priority::Critical && $issue->status === Status::Open
            => 'Acknowledge within 1 hour, assign owner, and notify on-call team.',

            $issue->priority === Priority::High && $issue->status === Status::Open
            => 'Acknowledge within 4 hours and assign to the appropriate team.',

            $issue->priority === Priority::High && $issue->status === Status::InProgress
            => 'Provide a status update to the reporter within 24 hours.',

            $issue->status === Status::InProgress
            => 'Continue working and post a progress note before end of day.',

            $issue->status === Status::Resolved
            => 'Confirm fix with reporter and close if no further response in 48 hours.',

            $issue->status === Status::Closed
            => 'No action needed.',

            default
            => 'Triage within standard SLA and assign category owner.',
        };
    }
}
