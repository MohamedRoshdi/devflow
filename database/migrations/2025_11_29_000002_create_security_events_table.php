<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->string('event_type', 50);
            $table->string('source_ip', 45)->nullable();
            $table->text('details')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            $table->index(['server_id', 'event_type', 'created_at']);
            $table->index(['source_ip']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
