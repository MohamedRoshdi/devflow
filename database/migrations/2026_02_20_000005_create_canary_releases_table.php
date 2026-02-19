<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canary_releases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('stable_version')->nullable();
            $table->string('canary_version')->nullable();
            $table->string('status')->default('pending');
            $table->integer('current_weight')->default(0);
            $table->json('weight_schedule')->nullable();
            $table->integer('current_step')->default(0);
            $table->decimal('error_rate_threshold', 5, 2)->default(5.00);
            $table->integer('response_time_threshold')->default(2000);
            $table->boolean('auto_promote')->default(true);
            $table->boolean('auto_rollback')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('promoted_at')->nullable();
            $table->timestamp('rolled_back_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canary_releases');
    }
};
