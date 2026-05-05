<?php

namespace App\DTO;

class SummaryResult
{
    public function __construct(
        public readonly string $summary,
        public readonly string $action,
        public readonly string $source,  // 'llm' or 'rules'
    ) {}
}