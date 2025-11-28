<?php

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
        Schema::create('scheduled_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('branch')->default('main');
            $table->dateTime('scheduled_at');
            $table->string('timezone')->default('UTC');
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('deployment_id')->nullable()->constrained()->onDelete('set null');
            $table->dateTime('executed_at')->nullable();
            $table->boolean('notify_before')->default(true);
            $table->integer('notify_minutes')->default(15);
            $table->boolean('notified')->default(false);
            $table->timestamps();

            $table->index(['scheduled_at', 'status']);
            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_deployments');
    }
};
