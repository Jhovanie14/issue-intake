<?php

namespace App\Providers;

use App\Contracts\SummaryGenerator;
use App\Services\AI\FallbackSummaryGenerator;
use App\Services\AI\LLMSummaryGenerator;
use App\Services\AI\RulesSummaryGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SummaryGenerator::class, function ($app) {
            $rules = new RulesSummaryGenerator();
            $apikey = config('services.openai.key');

            if (empty($apikey)) {
                // No API key configured, use rules-based generator only
                return $rules;
            }

            $llm = new LLMSummaryGenerator(
                apiKey: $apikey,
                model: config('services.openai.model'),
                timeoutSeconds: config('services.openai.timeout'),
            );

            return new FallbackSummaryGenerator(
                primary: $llm,
                fallback: $rules,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
