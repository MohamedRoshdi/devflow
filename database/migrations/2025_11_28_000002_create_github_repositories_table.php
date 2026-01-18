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
        if (Schema::hasTable('github_repositories')) {
            return;
        }

        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('github_connection_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('set null');
            $table->string('repo_id')->unique();
            $table->string('name');
            $table->string('full_name')->unique();
            $table->text('description')->nullable();
            $table->boolean('private')->default(false);
            $table->string('default_branch')->default('main');
            $table->string('clone_url');
            $table->string('ssh_url');
            $table->string('html_url');
            $table->string('language')->nullable();
            $table->integer('stars_count')->default(0);
            $table->integer('forks_count')->default(0);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->index(['github_connection_id', 'synced_at']);
            $table->index('project_id');
            $table->index('repo_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
