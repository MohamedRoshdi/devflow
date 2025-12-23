<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('setup_status')->default('pending')->after('status'); // pending, in_progress, completed, failed
            $table->json('setup_config')->nullable()->after('setup_status');
            $table->timestamp('setup_completed_at')->nullable()->after('setup_config');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['setup_status', 'setup_config', 'setup_completed_at']);
        });
    }
};
