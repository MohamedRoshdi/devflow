<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dns_routing_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('routing_type')->default('latency');
            $table->integer('weight')->default(100);
            $table->integer('priority')->default(0);
            $table->json('geo_restrictions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'region_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dns_routing_rules');
    }
};
