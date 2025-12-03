<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->timestamp('provisioned_at')->nullable()->after('last_ping_at');
            $table->enum('provision_status', ['pending', 'provisioning', 'completed', 'failed'])->nullable()->after('provisioned_at');
            $table->json('installed_packages')->nullable()->after('provision_status')->comment('Track installed packages: nginx, mysql, php, composer, nodejs, etc.');
            $table->string('ssh_password')->nullable()->after('ssh_key');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn([
                'provisioned_at',
                'provision_status',
                'installed_packages',
                'ssh_password'
            ]);
        });
    }
};
