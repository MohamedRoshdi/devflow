<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->foreignId('rollback_deployment_id')->nullable()->after('triggered_by')
                ->constrained('deployments')->onDelete('set null');
            $table->json('environment_snapshot')->nullable()->after('rollback_deployment_id');
            $table->text('error_message')->nullable()->after('error_log');
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table) {
            $table->dropForeign(['rollback_deployment_id']);
            $table->dropColumn(['rollback_deployment_id', 'environment_snapshot', 'error_message']);
        });
    }
};
