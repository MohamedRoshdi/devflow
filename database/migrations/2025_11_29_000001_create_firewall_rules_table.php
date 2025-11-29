<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('firewall_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->enum('action', ['allow', 'deny', 'limit', 'reject'])->default('allow');
            $table->enum('direction', ['in', 'out'])->default('in');
            $table->enum('protocol', ['tcp', 'udp', 'any'])->default('tcp');
            $table->string('port', 20)->nullable();
            $table->string('from_ip', 45)->nullable();
            $table->string('to_ip', 45)->nullable();
            $table->string('description', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['server_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('firewall_rules');
    }
};
