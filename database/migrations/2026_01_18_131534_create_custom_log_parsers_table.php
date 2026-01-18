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
        Schema::create('custom_log_parsers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('log_type'); // Type of logs this parser handles
            $table->string('format'); // regex, json, csv, custom
            $table->text('pattern'); // Regex pattern or parser definition
            $table->json('field_mappings'); // Map parsed fields to log columns
            $table->json('sample_log')->nullable(); // Example log for testing
            $table->boolean('is_active')->default(true);
            $table->integer('parsed_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('log_type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_log_parsers');
    }
};
