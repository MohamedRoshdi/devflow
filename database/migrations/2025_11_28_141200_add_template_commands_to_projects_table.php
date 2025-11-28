<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->json('install_commands')->nullable()->after('build_command');
            $table->json('build_commands')->nullable()->after('install_commands');
            $table->json('post_deploy_commands')->nullable()->after('build_commands');
            $table->foreignId('template_id')->nullable()->after('user_id')->constrained('project_templates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['template_id']);
            $table->dropColumn(['install_commands', 'build_commands', 'post_deploy_commands', 'template_id']);
        });
    }
};
