<?php

namespace App\Contracts;

use App\DTO\SummaryResult;
use App\Models\Issue;

interface SummaryGenerator
{
    public function generate(Issue $issue): SummaryResult;
}