<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address');
            $table->integer('port')->default(22);
            $table->string('username')->default('root');
            $table->text('ssh_key')->nullable();
            $table->enum('status', ['online', 'offline', 'maintenance', 'error'])->default('offline');
            $table->string('os')->nullable();
            $table->integer('cpu_cores')->nullable();
            $table->integer('memory_gb')->nullable();
            $table->integer('disk_gb')->nullable();
            $table->boolean('docker_installed')->default(false);
            $table->string('docker_version')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
