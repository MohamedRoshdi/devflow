<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('kubernetes_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('api_server_url');
            $table->text('kubeconfig')->nullable();
            $table->string('namespace')->default('default');
            $table->string('context')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('kubernetes_clusters');
    }
};
