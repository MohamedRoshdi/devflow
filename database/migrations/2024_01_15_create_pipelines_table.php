<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('provider'); // github_actions, gitlab_ci, bitbucket, jenkins
            $table->json('configuration');
            $table->json('triggers')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pipeline_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pipeline_id')->constrained()->onDelete('cascade');
            $table->string('run_number');
            $table->string('status'); // pending, running, success, failed, cancelled
            $table->string('triggered_by'); // manual, push, pr, schedule
            $table->string('branch')->nullable();
            $table->string('commit_sha')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->json('logs')->nullable();
            $table->json('artifacts')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pipeline_runs');
        Schema::dropIfExists('pipelines');
    }
};