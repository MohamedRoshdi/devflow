<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_tag_pivot', function (Blueprint $table) {
            $table->foreignId('server_id')->constrained()->onDelete('cascade');
            $table->foreignId('tag_id')->references('id')->on('server_tags')->onDelete('cascade');

            $table->primary(['server_id', 'tag_id']);
            $table->index('server_id');
            $table->index('tag_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_tag_pivot');
    }
};
