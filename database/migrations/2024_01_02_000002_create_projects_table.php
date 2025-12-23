<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('server_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('repository_url')->nullable();
            $table->string('branch')->default('main');
            $table->string('framework')->nullable();
            $table->string('php_version')->nullable();
            $table->string('node_version')->nullable();
            $table->string('root_directory')->default('/');
            $table->text('build_command')->nullable();
            $table->text('start_command')->nullable();
            $table->json('env_variables')->nullable();
            $table->enum('status', ['running', 'stopped', 'building', 'error', 'deploying'])->default('stopped');
            $table->string('health_check_url')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->bigInteger('storage_used_mb')->default(0);
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->boolean('auto_deploy')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['server_id', 'status']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
