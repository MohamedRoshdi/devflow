<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration adds missing columns to the servers table as specified
     * in the CLAUDE.md documentation for DevFlow Pro.
     */
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // Add nginx_proxy_manager_installed column if it doesn't exist
            if (! Schema::hasColumn('servers', 'nginx_proxy_manager_installed')) {
                $table->boolean('nginx_proxy_manager_installed')
                    ->default(false)
                    ->after('docker_version')
                    ->comment('Whether Nginx Proxy Manager is installed');
            }

            // Add portainer_installed column if it doesn't exist
            if (! Schema::hasColumn('servers', 'portainer_installed')) {
                $table->boolean('portainer_installed')
                    ->default(false)
                    ->after('nginx_proxy_manager_installed')
                    ->comment('Whether Portainer is installed for Docker management');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // Drop columns in reverse order
            if (Schema::hasColumn('servers', 'portainer_installed')) {
                $table->dropColumn('portainer_installed');
            }
            if (Schema::hasColumn('servers', 'nginx_proxy_manager_installed')) {
                $table->dropColumn('nginx_proxy_manager_installed');
            }
        });
    }
};
