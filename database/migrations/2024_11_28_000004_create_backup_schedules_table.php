<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->enum('database_type', ['mysql', 'postgresql', 'sqlite'])->default('mysql');
            $table->string('database_name');
            $table->enum('frequency', ['hourly', 'daily', 'weekly', 'monthly'])->default('daily');
            $table->time('time')->default('02:00:00');
            $table->tinyInteger('day_of_week')->nullable()->comment('0-6 for weekly backups');
            $table->tinyInteger('day_of_month')->nullable()->comment('1-31 for monthly backups');
            $table->integer('retention_days')->default(30);
            $table->enum('storage_disk', ['local', 's3'])->default('local');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'is_active']);
            $table->index('next_run_at');
            $table->index('database_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_schedules');
    }
};
