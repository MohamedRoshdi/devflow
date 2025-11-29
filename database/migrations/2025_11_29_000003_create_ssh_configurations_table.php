<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ssh_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->unique()->constrained()->onDelete('cascade');
            $table->integer('port')->default(22);
            $table->boolean('root_login_enabled')->default(true);
            $table->boolean('password_auth_enabled')->default(true);
            $table->boolean('pubkey_auth_enabled')->default(true);
            $table->integer('max_auth_tries')->default(6);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ssh_configurations');
    }
};
