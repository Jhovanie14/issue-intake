<?php

namespace App\Services\AI;

use App\Contracts\SummaryGenerator;
use App\DTO\SummaryResult;
use App\Models\Issue;
use App\Services\AI\Exceptions\SummaryGenerationException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class LLMSummaryGenerator implements SummaryGenerator
{
    public function __construct(
        private string $apiKey,
        private string $model = 'gpt-4o-mini',
        private int $timeoutSeconds = 8,
    ) {}

    public function generate(Issue $issue): SummaryResult
    {
        try {
            $response = Http::withToken($this->apiKey)
                ->timeout($this->timeoutSeconds)
                ->retry(1, 200)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $this->userPrompt($issue)],
                    ],
                ])
                ->throw();

            $content = $response->json('choices.0.message.content');
            $parsed = json_decode($content, associative: true, flags: JSON_THROW_ON_ERROR);

            if (!isset($parsed['summary'], $parsed['action'])) {
                throw new SummaryGenerationException('LLM response missing required keys.');
            }

            return new SummaryResult(
                summary: (string) $parsed['summary'],
                action:  (string) $parsed['action'],
                source:  'llm',
            );
        } catch (Throwable $e) {
            Log::warning('LLM summary generation failed', [
                'issue_id' => $issue->id,
                'error'    => $e->getMessage(),
            ]);
            throw new SummaryGenerationException(
                'LLM summary generation failed: ' . $e->getMessage(),
                previous: $e,
            );
        }
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
        You are a support-ticket assistant. Given an issue, return a JSON object with two fields:
        - "summary": a 1-2 sentence summary of the issue (max 200 chars)
        - "action": a single specific next action for the support team (max 200 chars)

        Be concrete and operational. No filler. Respond with JSON only.
        PROMPT;
    }

    private function userPrompt(Issue $issue): string
    {
        return sprintf(
            "Title: %s\nPriority: %s\nCategory: %s\nStatus: %s\nDescription: %s",
            $issue->title,
            $issue->priority->value,
            $issue->category,
            $issue->status->value,
            $issue->description,
        );
    }
}