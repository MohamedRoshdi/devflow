<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('database');
            $table->string('admin_email');
            $table->string('admin_password');
            $table->string('plan')->default('basic'); // basic, pro, enterprise
            $table->string('status')->default('active'); // active, suspended, terminated
            $table->json('custom_config')->nullable();
            $table->json('features')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('last_deployed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_deployments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('deployment_id')->constrained()->onDelete('cascade');
            $table->string('status'); // pending, success, failed
            $table->text('output')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tenant_deployments');
        Schema::dropIfExists('tenants');
    }
};