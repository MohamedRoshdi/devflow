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
        if (Schema::hasTable('webhook_deliveries')) {
            return;
        }

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('provider', ['github', 'gitlab', 'bitbucket', 'custom'])->default('github');
            $table->string('event_type');
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'ignored'])->default('pending');
            $table->text('response')->nullable();
            $table->foreignId('deployment_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['project_id', 'status', 'created_at']);
            $table->index(['provider', 'event_type']);
            $table->index('deployment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_deliveries');
    }
};
