<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('environment')->default('production')->after('framework');
            $table->integer('port')->nullable()->after('node_version');
            $table->string('current_commit_hash', 40)->nullable()->after('status');
            $table->string('current_commit_message')->nullable()->after('current_commit_hash');
            $table->timestamp('last_commit_at')->nullable()->after('current_commit_message');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['environment', 'port', 'current_commit_hash', 'current_commit_message', 'last_commit_at']);
        });
    }
};
