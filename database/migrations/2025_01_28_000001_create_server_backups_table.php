<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['full', 'incremental', 'snapshot'])->default('full');
            $table->enum('status', ['pending', 'running', 'completed', 'failed'])->default('pending');
            $table->bigInteger('size_bytes')->nullable();
            $table->string('storage_path')->nullable();
            $table->enum('storage_driver', ['local', 's3'])->default('local');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'status']);
            $table->index(['server_id', 'type']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_backups');
    }
};
