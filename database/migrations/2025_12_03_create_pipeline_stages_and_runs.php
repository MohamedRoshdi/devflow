<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create pipeline_stages table for configurable deployment stages
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['pre_deploy', 'deploy', 'post_deploy'])->default('deploy');
            $table->integer('order')->default(0);
            $table->text('commands'); // JSON array of commands to execute
            $table->boolean('enabled')->default(true);
            $table->boolean('continue_on_failure')->default(false);
            $table->integer('timeout_seconds')->default(600); // 10 minutes default
            $table->timestamps();

            $table->index(['project_id', 'type', 'order']);
        });

        // Update pipeline_runs table
        if (! Schema::hasColumn('pipeline_runs', 'deployment_id')) {
            Schema::table('pipeline_runs', function (Blueprint $table) {
                $table->foreignId('deployment_id')->nullable()->after('pipeline_id')->constrained()->onDelete('set null');
                $table->foreignId('project_id')->nullable()->after('pipeline_id')->constrained()->onDelete('cascade');
                $table->json('trigger_data')->nullable()->after('triggered_by');
            });
        }

        // Create pipeline_stage_runs table for tracking individual stage executions
        Schema::create('pipeline_stage_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_run_id')->constrained()->onDelete('cascade');
            $table->foreignId('pipeline_stage_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'skipped'])->default('pending');
            $table->longText('output')->nullable(); // Command output
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->timestamps();

            $table->index(['pipeline_run_id', 'status']);
            $table->index(['pipeline_stage_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stage_runs');

        if (Schema::hasColumn('pipeline_runs', 'deployment_id')) {
            // SQLite doesn't support dropping columns with foreign keys, so skip for SQLite
            if (config('database.default') !== 'sqlite') {
                Schema::table('pipeline_runs', function (Blueprint $table) {
                    $table->dropForeign(['deployment_id']);
                    $table->dropColumn(['deployment_id', 'project_id', 'trigger_data']);
                });
            }
        }

        Schema::dropIfExists('pipeline_stages');
    }
};
