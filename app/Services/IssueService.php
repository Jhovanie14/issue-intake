<?php

namespace App\Services;

use App\Contracts\SummaryGenerator;
use App\Models\Issue;

class IssueService
{
    public function __construct(
        private SummaryGenerator $summarizer,
        private EscalationService $escalation,
    ) {}


    public function create(array $data): Issue
    {
        $issue = Issue::create($data);

        $this->generateSummary($issue);
        $this->escalation->apply($issue);

        return $issue->fresh();
    }

    public function update(Issue $issue, array $data): Issue
    {
        $descriptionChanged = isset($data['description'])
            && $data['description'] !== $issue->description;

        $issue->update($data);

        if ($descriptionChanged) {
            $this->generateSummary($issue);
        }

        $this->escalation->apply($issue);

        return $issue->fresh();
    }


    private function generateSummary(Issue $issue): void
    {
        $result = $this->summarizer->generate($issue);

        $issue->update([
            'summary'          => $result->summary,
            'suggested_action' => $result->action,
            'summary_source'   => $result->source,
        ]);
    }
}
