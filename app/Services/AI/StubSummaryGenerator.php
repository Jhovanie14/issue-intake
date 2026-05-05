<?php

namespace App\Services\AI;

use App\Contracts\SummaryGenerator;
use App\DTO\SummaryResult;
use App\Models\Issue;

class StubSummaryGenerator implements SummaryGenerator
{
    public function generate(Issue $issue): SummaryResult
    {
        return new SummaryResult(
            summary: 'Summary pending — AI layer not yet implemented.',
            action: 'Review manually.',
            source: 'stub',
        );
    }
}
