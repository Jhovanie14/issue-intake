<?php

use App\Enums\Priority;
use App\Enums\Status;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->string('priority')->default(Priority::Low->value);
            $table->string('category');
            $table->string('status')->default(Status::Open->value);
            $table->text('summary')->nullable();
            $table->text('suggested_action')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('escalated')->default(false);
            $table->timestamp('escalated_at')->nullable();
            $table->string('summary_source')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
