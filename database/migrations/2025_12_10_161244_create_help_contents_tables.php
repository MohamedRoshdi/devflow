<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Main help contents table
        Schema::create('help_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->comment('Unique identifier for help content (e.g., deploy-button)');
            $table->string('category', 50)->index()->comment('Category: deployments, domains, servers, etc.');
            $table->string('ui_element_type', 50)->comment('button, toggle, checkbox, input, etc.');
            $table->string('icon', 50)->nullable()->comment('Icon class or emoji');
            $table->string('title')->comment('Display title');
            $table->text('brief')->comment('Short one-line explanation');
            $table->json('details')->nullable()->comment('Array of detailed bullet points');
            $table->string('docs_url')->nullable()->comment('Link to full documentation');
            $table->string('video_url')->nullable()->comment('Link to video tutorial');
            $table->unsignedInteger('view_count')->default(0)->comment('How many times viewed');
            $table->unsignedInteger('helpful_count')->default(0)->comment('Thumbs up count');
            $table->unsignedInteger('not_helpful_count')->default(0)->comment('Thumbs down count');
            $table->boolean('is_active')->default(true)->comment('Show/hide this help content');
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });

        // Translations table for multi-language support
        Schema::create('help_content_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_content_id')->constrained()->onDelete('cascade');
            $table->string('locale', 5)->comment('Language code: en, ar, etc.');
            $table->text('brief');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->unique(['help_content_id', 'locale']);
            $table->index('locale');
        });

        // User interactions tracking
        Schema::create('help_interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_content_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('interaction_type', ['view', 'helpful', 'not_helpful'])->comment('Type of interaction');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['help_content_id', 'interaction_type']);
            $table->index('created_at');
        });

        // Related help content (for "See also" links)
        Schema::create('help_content_related', function (Blueprint $table) {
            $table->id();
            $table->foreignId('help_content_id')->constrained()->onDelete('cascade');
            $table->foreignId('related_help_content_id')->constrained('help_contents')->onDelete('cascade');
            $table->unsignedTinyInteger('relevance_score')->default(50)->comment('Relevance score 1-100');
            $table->timestamps();

            $table->unique(['help_content_id', 'related_help_content_id'], 'help_related_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_content_related');
        Schema::dropIfExists('help_interactions');
        Schema::dropIfExists('help_content_translations');
        Schema::dropIfExists('help_contents');
    }
};
