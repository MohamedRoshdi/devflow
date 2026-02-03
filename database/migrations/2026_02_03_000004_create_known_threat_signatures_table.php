<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('known_threat_signatures', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category', 50);
            $table->string('signature_type', 50);
            $table->text('pattern');
            $table->string('severity', 20)->default('high');
            $table->text('description')->nullable();
            $table->text('remediation_hint')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('category');
            $table->index('signature_type');
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('known_threat_signatures');
    }
};
