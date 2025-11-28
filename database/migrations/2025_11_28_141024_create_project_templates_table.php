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
        Schema::create('project_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('framework'); // laravel, nodejs, nextjs, static, custom
            $table->string('icon')->nullable();
            $table->string('color')->default('blue');
            $table->boolean('is_system')->default(false); // System templates cannot be deleted
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Custom templates

            // Default configuration
            $table->string('default_branch')->default('main');
            $table->string('php_version')->nullable();
            $table->string('node_version')->nullable();

            // Deployment commands
            $table->json('install_commands')->nullable(); // e.g., ["composer install", "npm install"]
            $table->json('build_commands')->nullable(); // e.g., ["npm run build"]
            $table->json('post_deploy_commands')->nullable(); // e.g., ["php artisan migrate"]

            // Environment variables template
            $table->json('env_template')->nullable();

            // Docker configuration
            $table->text('docker_compose_template')->nullable();
            $table->text('dockerfile_template')->nullable();

            // Health check
            $table->string('health_check_path')->nullable();

            $table->timestamps();

            $table->index('framework');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_templates');
    }
};
