<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->boolean('guardian_enabled')->default(false)->after('auto_remediation_enabled');
            $table->timestamp('last_guardian_scan_at')->nullable()->after('guardian_enabled');
            $table->timestamp('last_baseline_at')->nullable()->after('last_guardian_scan_at');
            $table->timestamp('last_hardening_at')->nullable()->after('last_baseline_at');
            $table->string('hardening_level', 20)->nullable()->after('last_hardening_at');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table): void {
            $table->dropColumn([
                'guardian_enabled',
                'last_guardian_scan_at',
                'last_baseline_at',
                'last_hardening_at',
                'hardening_level',
            ]);
        });
    }
};
