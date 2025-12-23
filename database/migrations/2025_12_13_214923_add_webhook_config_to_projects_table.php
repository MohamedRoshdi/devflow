<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Add webhook provider field
            $table->enum('webhook_provider', ['github', 'gitlab', 'bitbucket', 'custom'])
                ->nullable()
                ->after('webhook_enabled');

            // Add external webhook ID (e.g., GitHub hook ID, GitLab hook ID)
            $table->string('webhook_id')->nullable()->after('webhook_provider');

            // Add webhook URL for reference
            $table->string('webhook_url')->nullable()->after('webhook_id');

            // Add index for webhook lookups
            $table->index('webhook_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['webhook_id']);
            $table->dropColumn(['webhook_provider', 'webhook_id', 'webhook_url']);
        });
    }
};
