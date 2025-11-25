<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('deployment_scripts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('language'); // bash, python, php, node, ruby
            $table->text('script');
            $table->json('variables')->nullable();
            $table->string('run_as')->default('www-data');
            $table->integer('timeout')->default(300); // seconds
            $table->boolean('is_template')->default(false);
            $table->json('tags')->nullable();
            $table->timestamps();
        });

        Schema::create('deployment_script_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('deployment_script_id')->constrained()->onDelete('cascade');
            $table->foreignId('deployment_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('status'); // running, success, failed
            $table->text('output')->nullable();
            $table->text('error_output')->nullable();
            $table->integer('exit_code')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('deployment_script_runs');
        Schema::dropIfExists('deployment_scripts');
    }
};