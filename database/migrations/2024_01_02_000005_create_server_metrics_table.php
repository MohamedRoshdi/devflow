<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->decimal('cpu_usage', 5, 2)->default(0);
            $table->decimal('memory_usage', 5, 2)->default(0);
            $table->integer('memory_used_mb')->default(0);
            $table->integer('memory_total_mb')->default(0);
            $table->decimal('disk_usage', 5, 2)->default(0);
            $table->integer('disk_used_gb')->default(0);
            $table->integer('disk_total_gb')->default(0);
            $table->decimal('load_average_1', 5, 2)->default(0);
            $table->decimal('load_average_5', 5, 2)->default(0);
            $table->decimal('load_average_15', 5, 2)->default(0);
            $table->bigInteger('network_in_bytes')->default(0);
            $table->bigInteger('network_out_bytes')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['server_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metrics');
    }
};
