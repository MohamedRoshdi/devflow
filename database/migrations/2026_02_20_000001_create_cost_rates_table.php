<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cost_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->nullable()->constrained('servers')->nullOnDelete();
            $table->string('resource_type');
            $table->decimal('rate_per_unit', 10, 4);
            $table->string('unit');
            $table->string('currency')->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['server_id', 'resource_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cost_rates');
    }
};
