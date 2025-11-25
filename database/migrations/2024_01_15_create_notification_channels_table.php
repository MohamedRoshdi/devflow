<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('provider'); // slack, discord, teams, webhook
            $table->string('webhook_url');
            $table->text('webhook_secret')->nullable();
            $table->boolean('enabled')->default(true);
            $table->json('events'); // array of events to notify
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_channel_id')->constrained()->onDelete('cascade');
            $table->string('event_type');
            $table->json('payload');
            $table->string('status'); // sent, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notification_channels');
    }
};