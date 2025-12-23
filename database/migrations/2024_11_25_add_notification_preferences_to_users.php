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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notification_sound')->default(true)->after('remember_token');
            $table->boolean('desktop_notifications')->default(false)->after('notification_sound');
            $table->json('notification_preferences')->nullable()->after('desktop_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notification_sound', 'desktop_notifications', 'notification_preferences']);
        });
    }
};
