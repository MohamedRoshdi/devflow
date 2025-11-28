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
        Schema::create('health_check_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_check_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['success', 'failure', 'timeout'])->default('failure');
            $table->integer('response_time_ms')->nullable();
            $table->integer('status_code')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('checked_at')->useCurrent();

            $table->index(['health_check_id', 'checked_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_check_results');
    }
};
