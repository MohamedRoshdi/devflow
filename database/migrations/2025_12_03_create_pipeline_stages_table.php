<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Build", "Test", "Deploy"
            $table->enum('type', ['pre_deploy', 'deploy', 'post_deploy'])->default('pre_deploy');
            $table->integer('order')->default(0); // for sorting within type
            $table->json('commands'); // array of shell commands
            $table->boolean('enabled')->default(true);
            $table->boolean('continue_on_failure')->default(false);
            $table->integer('timeout_seconds')->default(300);
            $table->json('environment_variables')->nullable();
            $table->timestamps();

            // Index for efficient querying
            $table->index(['project_id', 'type', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};
