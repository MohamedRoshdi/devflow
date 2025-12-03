<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('server_metrics', function (Blueprint $table) {
            // Add columns if they don't exist
            if (!Schema::hasColumn('server_metrics', 'memory_used_mb')) {
                $table->integer('memory_used_mb')->default(0)->after('memory_usage');
            }
            if (!Schema::hasColumn('server_metrics', 'memory_total_mb')) {
                $table->integer('memory_total_mb')->default(0)->after('memory_used_mb');
            }
            if (!Schema::hasColumn('server_metrics', 'disk_used_gb')) {
                $table->integer('disk_used_gb')->default(0)->after('disk_usage');
            }
            if (!Schema::hasColumn('server_metrics', 'disk_total_gb')) {
                $table->integer('disk_total_gb')->default(0)->after('disk_used_gb');
            }
            if (!Schema::hasColumn('server_metrics', 'load_average_1')) {
                $table->decimal('load_average_1', 5, 2)->default(0)->after('disk_total_gb');
            }
            if (!Schema::hasColumn('server_metrics', 'load_average_5')) {
                $table->decimal('load_average_5', 5, 2)->default(0)->after('load_average_1');
            }
            if (!Schema::hasColumn('server_metrics', 'load_average_15')) {
                $table->decimal('load_average_15', 5, 2)->default(0)->after('load_average_5');
            }
            if (!Schema::hasColumn('server_metrics', 'network_in_bytes')) {
                $table->bigInteger('network_in_bytes')->default(0)->after('load_average_15');
            }
            if (!Schema::hasColumn('server_metrics', 'network_out_bytes')) {
                $table->bigInteger('network_out_bytes')->default(0)->after('network_in_bytes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('server_metrics', function (Blueprint $table) {
            $table->dropColumn([
                'memory_used_mb',
                'memory_total_mb',
                'disk_used_gb',
                'disk_total_gb',
                'load_average_1',
                'load_average_5',
                'load_average_15',
                'network_in_bytes',
                'network_out_bytes',
            ]);
        });
    }
};
