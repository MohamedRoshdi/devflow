<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('domain')->unique();
            $table->boolean('is_primary')->default(false);
            $table->boolean('ssl_enabled')->default(false);
            $table->string('ssl_provider')->nullable();
            $table->text('ssl_certificate')->nullable();
            $table->text('ssl_private_key')->nullable();
            $table->timestamp('ssl_issued_at')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->boolean('auto_renew_ssl')->default(true);
            $table->boolean('dns_configured')->default(false);
            $table->enum('status', ['active', 'inactive', 'pending', 'error'])->default('pending');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'is_primary']);
            $table->index(['ssl_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
