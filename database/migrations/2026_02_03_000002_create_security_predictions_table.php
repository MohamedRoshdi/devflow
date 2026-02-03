<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_predictions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('prediction_type', 50);
            $table->string('severity', 20);
            $table->string('status', 30)->default('active');
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('evidence')->nullable();
            $table->json('recommended_actions')->nullable();
            $table->float('confidence_score')->default(0);
            $table->timestamp('predicted_impact_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['server_id', 'status']);
            $table->index(['severity', 'status']);
            $table->index('prediction_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_predictions');
    }
};
