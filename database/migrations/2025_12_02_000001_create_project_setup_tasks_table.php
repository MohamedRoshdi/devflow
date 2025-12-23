<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_setup_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('task_type'); // ssl, webhook, health_check, backup, notifications, deployment
            $table->string('status')->default('pending'); // pending, running, completed, failed, skipped
            $table->text('message')->nullable();
            $table->json('result_data')->nullable();
            $table->integer('progress')->default(0); // 0-100
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'task_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_setup_tasks');
    }
};
