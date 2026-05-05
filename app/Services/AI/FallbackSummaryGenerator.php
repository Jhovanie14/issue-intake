<?php

namespace App\Services\AI;

use App\Contracts\SummaryGenerator;
use App\DTO\SummaryResult;
use App\Models\Issue;
use App\Services\AI\Exceptions\SummaryGenerationException;

class FallbackSummaryGenerator implements SummaryGenerator
{
    public function __construct(
        private SummaryGenerator $primary,
        private SummaryGenerator $fallback,
    ) {}

    public function generate(Issue $issue): SummaryResult
    {
        try {
            return $this->primary->generate($issue);
        } catch (SummaryGenerationException) {
            return $this->fallback->generate($issue);
        }
    }
}