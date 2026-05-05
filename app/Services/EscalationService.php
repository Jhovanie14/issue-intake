<?php

namespace App\Services;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Issue;

class EscalationService
{
    public function shouldEscalate(Issue $issue): bool
    {
        return in_array($issue->priority, [Priority::High, Priority::Critical], true) && $issue->due_at?->isPast() && !in_array($issue->status, [Status::Closed, Status::Resolved], true);
    }

    public function apply(Issue $issue): void
    {
        if (!$issue->escalated && $this->shouldEscalate($issue)) {
            $issue->update([
                'escalated' => true,
                'escalated_at' => now()
            ]);
        }
    }
}
