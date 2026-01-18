<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            // Add project_id to allow project-specific channels
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->onDelete('cascade');

            // Rename provider to type for consistency
            $table->renameColumn('provider', 'type');

            // Add config column for encrypted configuration (replaces webhook_url and webhook_secret)
            $table->text('config')->nullable()->after('type');

            // Add index for project filtering
            $table->index('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('notification_channels', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
            $table->renameColumn('type', 'provider');
            $table->dropColumn('config');
        });
    }
};
