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
        Schema::create('log_forwarding_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('destination_type'); // slack, email, webhook, syslog, splunk
            $table->json('destination_config'); // URL, email, credentials
            $table->json('filters')->nullable(); // log_type, log_level, pattern
            $table->boolean('is_active')->default(true);
            $table->integer('batch_size')->default(1); // Number of logs to batch before sending
            $table->integer('batch_timeout')->default(60); // Seconds to wait before sending partial batch
            $table->timestamp('last_forwarded_at')->nullable();
            $table->integer('forwarded_count')->default(0);
            $table->integer('failed_count')->default(0);
            $table->timestamps();

            $table->index('server_id');
            $table->index('destination_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_forwarding_rules');
    }
};
