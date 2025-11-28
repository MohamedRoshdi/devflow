<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['full', 'incremental', 'snapshot'])->default('full');
            $table->enum('frequency', ['daily', 'weekly', 'monthly'])->default('daily');
            $table->string('time')->default('02:00'); // HH:MM format
            $table->integer('day_of_week')->nullable(); // 0-6 for weekly
            $table->integer('day_of_month')->nullable(); // 1-31 for monthly
            $table->integer('retention_days')->default(30);
            $table->enum('storage_driver', ['local', 's3'])->default('local');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'is_active']);
            $table->index('frequency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_backup_schedules');
    }
};
