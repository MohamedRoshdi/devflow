<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('canary_metrics', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('canary_release_id')->constrained('canary_releases')->cascadeOnDelete();
            $table->string('version_type');
            $table->decimal('error_rate', 8, 4)->default(0);
            $table->integer('avg_response_time_ms')->default(0);
            $table->integer('p95_response_time_ms')->default(0);
            $table->integer('p99_response_time_ms')->default(0);
            $table->integer('request_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['canary_release_id', 'version_type', 'recorded_at'], 'canary_metrics_release_version_recorded_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canary_metrics');
    }
};
