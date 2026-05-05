<?php

namespace Database\Seeders;

use App\Models\Issue;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IssueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Issue::factory()->count(10)->create();

        Issue::factory()->count(3)->overdueHighPriority()->create();

        Issue::factory()->count(2)->freshCritical()->create();

        Issue::factory()->count(2)->closed()->create();
    }
}
