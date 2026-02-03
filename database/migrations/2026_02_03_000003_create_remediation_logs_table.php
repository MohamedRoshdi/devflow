<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remediation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('security_incident_id')->nullable()->constrained('security_incidents')->nullOnDelete();
            $table->string('action', 50);
            $table->string('target');
            $table->text('command_executed')->nullable();
            $table->text('rollback_command')->nullable();
            $table->boolean('success')->default(false);
            $table->text('output')->nullable();
            $table->text('error')->nullable();
            $table->boolean('auto_triggered')->default(false);
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['server_id', 'created_at']);
            $table->index('action');
            $table->index('success');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remediation_logs');
    }
};
