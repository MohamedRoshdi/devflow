<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('ufw_installed')->default(false)->after('docker_version');
            $table->boolean('ufw_enabled')->default(false)->after('ufw_installed');
            $table->boolean('fail2ban_installed')->default(false)->after('ufw_enabled');
            $table->boolean('fail2ban_enabled')->default(false)->after('fail2ban_installed');
            $table->integer('security_score')->nullable()->after('fail2ban_enabled');
            $table->timestamp('last_security_scan_at')->nullable()->after('security_score');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn([
                'ufw_installed',
                'ufw_enabled',
                'fail2ban_installed',
                'fail2ban_enabled',
                'security_score',
                'last_security_scan_at',
            ]);
        });
    }
};
