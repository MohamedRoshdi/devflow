<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Only add environment field (other fields already exist)
            if (!Schema::hasColumn('projects', 'environment')) {
                $table->string('environment')->default('production')->after('framework');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'environment')) {
                $table->dropColumn('environment');
            }
        });
    }
};
