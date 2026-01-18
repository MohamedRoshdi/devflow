<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->nullable()->constrained()->onDelete('set null');
            $table->string('commit_hash')->nullable();
            $table->text('commit_message')->nullable();
            $table->string('branch')->default('main');
            $table->enum('status', ['pending', 'running', 'success', 'failed', 'cancelled'])->default('pending');
            $table->longText('output_log')->nullable();
            $table->longText('error_log')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('triggered_by')->default('manual');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status', 'created_at']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deployments');
    }
};
