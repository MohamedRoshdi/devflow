<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deployments', function (Blueprint $table): void {
            $table->boolean('is_canary')->default(false);
            $table->foreignId('canary_release_id')->nullable()->constrained('canary_releases')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deployments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('canary_release_id');
            $table->dropColumn('is_canary');
        });
    }
};
