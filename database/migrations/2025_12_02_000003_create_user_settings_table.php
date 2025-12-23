<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Project creation defaults
            $table->boolean('default_enable_ssl')->default(true);
            $table->boolean('default_enable_webhooks')->default(true);
            $table->boolean('default_enable_health_checks')->default(true);
            $table->boolean('default_enable_backups')->default(true);
            $table->boolean('default_enable_notifications')->default(true);
            $table->boolean('default_enable_auto_deploy')->default(false);

            // UI Preferences
            $table->string('theme')->default('dark');
            $table->boolean('show_wizard_tips')->default(true);

            // Other settings
            $table->json('additional_settings')->nullable();

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
