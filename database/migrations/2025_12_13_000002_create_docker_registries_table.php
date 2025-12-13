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
        Schema::create('docker_registries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('registry_type', [
                'docker_hub',
                'github',
                'gitlab',
                'aws_ecr',
                'google_gcr',
                'azure_acr',
                'custom',
            ])->default('docker_hub');
            $table->string('registry_url');
            $table->string('username');
            $table->text('credentials_encrypted'); // Encrypted JSON containing passwords/tokens
            $table->string('email')->nullable();
            $table->boolean('is_default')->default(false);
            $table->enum('status', ['active', 'inactive', 'failed'])->default('active');
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'is_default']);
            $table->index('registry_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('docker_registries');
    }
};
