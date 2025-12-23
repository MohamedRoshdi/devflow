<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('storage_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('driver', ['local', 's3', 'gcs', 'ftp', 'sftp'])->default('local');
            $table->boolean('is_default')->default(false);
            $table->text('credentials'); // Encrypted JSON with driver-specific credentials
            $table->string('bucket')->nullable();
            $table->string('region')->nullable();
            $table->string('endpoint')->nullable(); // Custom S3 endpoint for DO Spaces, MinIO
            $table->string('path_prefix')->nullable();
            $table->string('encryption_key')->nullable(); // For at-rest encryption
            $table->enum('status', ['active', 'testing', 'disabled'])->default('active');
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('project_id');
            $table->index('driver');
            $table->index('status');
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_configurations');
    }
};
