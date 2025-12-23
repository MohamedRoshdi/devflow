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
        Schema::create('pipeline_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->json('auto_deploy_branches')->nullable(); // array of branch names
            $table->json('skip_patterns')->nullable(); // commit message patterns to skip
            $table->json('deploy_patterns')->nullable(); // commit message patterns to force deploy
            $table->string('webhook_secret', 100)->nullable();
            $table->timestamps();

            $table->index('project_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pipeline_configs');
    }
};
