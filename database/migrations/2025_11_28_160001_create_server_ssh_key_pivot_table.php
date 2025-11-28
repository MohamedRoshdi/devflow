<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('server_ssh_key')) {
            return;
        }

        Schema::create('server_ssh_key', function (Blueprint $table) {
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('ssh_key_id')->constrained()->onDelete('cascade');
            $table->timestamp('deployed_at')->nullable();

            $table->primary(['server_id', 'ssh_key_id']);
            $table->index('deployed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_ssh_key');
    }
};
