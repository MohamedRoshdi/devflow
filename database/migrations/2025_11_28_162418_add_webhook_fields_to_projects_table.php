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
            $table->string('webhook_secret', 64)->nullable()->after('auto_deploy');
            $table->boolean('webhook_enabled')->default(false)->after('webhook_secret');

            $table->index('webhook_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['webhook_secret']);
            $table->dropColumn(['webhook_secret', 'webhook_enabled']);
        });
    }
};
