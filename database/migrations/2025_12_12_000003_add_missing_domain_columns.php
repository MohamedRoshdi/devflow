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
     * This migration adds missing columns to the domains table as specified
     * in the CLAUDE.md documentation for DevFlow Pro.
     */
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            // Add subdomain column if it doesn't exist
            if (! Schema::hasColumn('domains', 'subdomain')) {
                $table->string('subdomain', 100)
                    ->nullable()
                    ->after('domain')
                    ->comment('Subdomain prefix for the domain');
            }

            // Add redirect_to column if it doesn't exist
            if (! Schema::hasColumn('domains', 'redirect_to')) {
                $table->string('redirect_to', 255)
                    ->nullable()
                    ->after('is_primary')
                    ->comment('URL to redirect this domain to');
            }

            // Add dns_provider column if it doesn't exist
            if (! Schema::hasColumn('domains', 'dns_provider')) {
                $table->string('dns_provider', 100)
                    ->nullable()
                    ->after('dns_configured')
                    ->comment('DNS provider name (e.g., Cloudflare, Route53)');
            }

            // Add dns_config column if it doesn't exist
            if (! Schema::hasColumn('domains', 'dns_config')) {
                $table->json('dns_config')
                    ->nullable()
                    ->after('dns_provider')
                    ->comment('DNS configuration and credentials');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            // Drop columns in reverse order
            if (Schema::hasColumn('domains', 'dns_config')) {
                $table->dropColumn('dns_config');
            }
            if (Schema::hasColumn('domains', 'dns_provider')) {
                $table->dropColumn('dns_provider');
            }
            if (Schema::hasColumn('domains', 'redirect_to')) {
                $table->dropColumn('redirect_to');
            }
            if (Schema::hasColumn('domains', 'subdomain')) {
                $table->dropColumn('subdomain');
            }
        });
    }
};
