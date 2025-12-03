<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('servers', 'provisioned_at')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->timestamp('provisioned_at')->nullable()->after('last_ping_at');
            });
        }
        if (!Schema::hasColumn('servers', 'provision_status')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->enum('provision_status', ['pending', 'provisioning', 'completed', 'failed'])->nullable()->after('provisioned_at');
            });
        }
        if (!Schema::hasColumn('servers', 'installed_packages')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->json('installed_packages')->nullable()->after('provision_status')->comment('Track installed packages: nginx, mysql, php, composer, nodejs, etc.');
            });
        }
        if (!Schema::hasColumn('servers', 'ssh_password')) {
            Schema::table('servers', function (Blueprint $table) {
                $table->string('ssh_password')->nullable()->after('ssh_key');
            });
        }
    }

    public function down(): void
    {
        $columns = ['provisioned_at', 'provision_status', 'installed_packages', 'ssh_password'];
        foreach ($columns as $column) {
            if (Schema::hasColumn('servers', $column)) {
                Schema::table('servers', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
