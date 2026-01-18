<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_incidents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('incident_type', 50);
            $table->string('severity', 20);
            $table->string('status', 30)->default('detected');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('findings')->nullable();
            $table->json('affected_items')->nullable();
            $table->json('remediation_actions')->nullable();
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->boolean('auto_remediated')->default(false);
            $table->timestamps();

            $table->index(['server_id', 'status']);
            $table->index(['severity', 'status']);
            $table->index('detected_at');
        });

        // Add threat scanning fields to servers table
        Schema::table('servers', function (Blueprint $table): void {
            $table->timestamp('last_threat_scan_at')->nullable()->after('last_security_scan_at');
            $table->unsignedInteger('active_incidents_count')->default(0)->after('security_score');
            $table->boolean('lockdown_mode')->default(false)->after('active_incidents_count');
            $table->boolean('auto_remediation_enabled')->default(false)->after('lockdown_mode');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->dropColumn([
                'last_threat_scan_at',
                'active_incidents_count',
                'lockdown_mode',
                'auto_remediation_enabled',
            ]);
        });

        Schema::dropIfExists('security_incidents');
    }
};
