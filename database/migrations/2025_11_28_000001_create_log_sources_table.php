<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('log_sources')) {
            return;
        }

        Schema::create('log_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['file', 'journald', 'docker'])->default('file');
            $table->string('path', 500);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->bigInteger('last_position')->default(0)->comment('File position or log offset');
            $table->timestamps();

            $table->index('server_id');
            $table->index('project_id');
            $table->index(['server_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('log_sources');
    }
};
