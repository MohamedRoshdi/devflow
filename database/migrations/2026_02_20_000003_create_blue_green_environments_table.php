<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blue_green_environments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('environment');
            $table->string('status')->default('inactive');
            $table->integer('port')->nullable();
            $table->string('commit_hash', 40)->nullable();
            $table->string('health_status')->default('unknown');
            $table->timestamp('last_health_check_at')->nullable();
            $table->json('container_ids')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'environment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blue_green_environments');
    }
};
