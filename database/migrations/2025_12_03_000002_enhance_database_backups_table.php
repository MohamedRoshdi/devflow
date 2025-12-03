<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('database_backups', function (Blueprint $table) {
            // Add checksum for integrity verification
            $table->string('checksum', 64)->nullable()->after('file_size')
                ->comment('SHA-256 hash for backup integrity verification');

            // Add backup type
            $table->enum('type', ['manual', 'scheduled', 'pre_deploy'])->default('manual')->after('database_name');

            // Add verification timestamp
            $table->timestamp('verified_at')->nullable()->after('completed_at');

            // Add metadata JSON column for additional info
            $table->json('metadata')->nullable()->after('error_message')
                ->comment('Database metadata: tables count, size, etc.');

            // Add index for checksum lookups
            $table->index('checksum');
        });

        Schema::table('backup_schedules', function (Blueprint $table) {
            // Enhanced retention policy
            $table->integer('retention_daily')->default(7)->after('retention_days')
                ->comment('Keep last N daily backups');
            $table->integer('retention_weekly')->default(4)->after('retention_daily')
                ->comment('Keep last N weekly backups');
            $table->integer('retention_monthly')->default(3)->after('retention_weekly')
                ->comment('Keep last N monthly backups');

            // Encryption option
            $table->boolean('encrypt')->default(false)->after('storage_disk');

            // Notification settings
            $table->boolean('notify_on_failure')->default(true)->after('encrypt');
        });
    }

    public function down(): void
    {
        Schema::table('database_backups', function (Blueprint $table) {
            $table->dropIndex(['checksum']);
            $table->dropColumn(['checksum', 'type', 'verified_at', 'metadata']);
        });

        Schema::table('backup_schedules', function (Blueprint $table) {
            $table->dropColumn([
                'retention_daily',
                'retention_weekly',
                'retention_monthly',
                'encrypt',
                'notify_on_failure'
            ]);
        });
    }
};
