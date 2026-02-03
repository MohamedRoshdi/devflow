<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_baselines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->json('running_services');
            $table->json('listening_ports');
            $table->json('system_users');
            $table->json('crontab_entries');
            $table->json('systemd_services');
            $table->json('installed_packages')->nullable();
            $table->float('avg_cpu_usage')->default(0);
            $table->float('avg_memory_usage')->default(0);
            $table->integer('total_processes')->default(0);
            $table->json('network_connections_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_baselines');
    }
};
