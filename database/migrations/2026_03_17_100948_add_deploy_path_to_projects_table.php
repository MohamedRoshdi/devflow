<?php

declare(strict_types=1);

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
            $table->string('deploy_path')->nullable()->after('root_directory')
                ->comment('Custom deployment base path. Defaults to /var/www/{slug} when null.');
            $table->boolean('use_octane')->default(false)->after('deploy_path')
                ->comment('Whether the project uses Laravel Octane. Triggers octane:reload instead of php-fpm reload.');
            $table->string('octane_server')->default('frankenphp')->after('use_octane')
                ->comment('Octane server type: frankenphp, swoole, or roadrunner.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['deploy_path', 'use_octane', 'octane_server']);
        });
    }
};
