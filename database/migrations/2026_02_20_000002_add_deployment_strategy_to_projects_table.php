<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('deployment_strategy')->default('standard');
            $table->string('active_environment')->nullable();
            $table->json('blue_green_config')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn([
                'deployment_strategy',
                'active_environment',
                'blue_green_config',
            ]);
        });
    }
};
