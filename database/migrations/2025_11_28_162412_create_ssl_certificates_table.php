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
        if (Schema::hasTable('ssl_certificates')) {
            return;
        }

        Schema::create('ssl_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('domain_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('domain_name');
            $table->enum('provider', ['letsencrypt', 'custom', 'none'])->default('letsencrypt');
            $table->enum('status', ['pending', 'issued', 'expired', 'failed', 'revoked'])->default('pending');
            $table->string('certificate_path', 500)->nullable();
            $table->string('private_key_path', 500)->nullable();
            $table->string('chain_path', 500)->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('last_renewal_attempt')->nullable();
            $table->text('renewal_error')->nullable();
            $table->timestamps();

            $table->index(['server_id', 'domain_name']);
            $table->index(['status', 'expires_at']);
            $table->index('auto_renew');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssl_certificates');
    }
};
