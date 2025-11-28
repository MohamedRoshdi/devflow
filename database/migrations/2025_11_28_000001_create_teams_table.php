<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('teams')) {
            return;
        }

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_personal')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
