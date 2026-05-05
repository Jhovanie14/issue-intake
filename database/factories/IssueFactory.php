<?php

namespace Database\Factories;

use App\Enums\Priority;
use App\Enums\Status;
use App\Models\Issue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'billing',
            'authentication',
            'performance',
            'bug',
            'feature_request',
            'data_issue',
            'integration',
            'ui',
        ];

        $titles = [
            'Login page returns 500 error',
            'Customer cannot reset password',
            'Invoice PDF missing line items',
            'Dashboard loads slowly during peak hours',
            'Webhook from payment provider failing',
            'Search returns stale results',
            'Bulk export times out for large accounts',
            'Email notifications not delivered',
            'Two-factor code expires too quickly',
            'Profile picture upload fails on mobile',
            'Reports show duplicated rows',
            'API rate limit unclear in docs',
        ];

        return [
            'title'       => $this->faker->randomElement($titles),
            'description' => $this->faker->paragraph(4),
            'priority'    => $this->faker->randomElement(Priority::cases()),
            'category'    => $this->faker->randomElement($categories),
            'status'      => $this->faker->randomElement(Status::cases()),
            'due_at'      => $this->faker->optional(0.7)->dateTimeBetween('-5 days', '+10 days'),
            'escalated'   => false,
            'escalated_at' => null,
        ];
    }
    public function overdueHighPriority(): static
    {
        return $this->state(fn() => [
            'priority' => Priority::High,
            'status' => Status::Open,
            'due_at' => $this->faker->dateTimeBetween('-10 days', '-1 day'),
        ]);
    }
    public function freshCritical(): static
    {
        return $this->state(fn() => [
            'priority' => Priority::Critical,
            'status' => Status::Open,
            'due_at' => null,
            'created_at' => now(),
        ]);
    }
    public function closed(): static
    {
        return $this->state(fn() => [
            'status' => Status::Closed,
            'due_at' => now()->subDays(10),
        ]);
    }
}
