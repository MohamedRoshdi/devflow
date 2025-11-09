<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->integer('port')->nullable()->after('node_version');
        });

        // Assign ports to existing projects (8001, 8002, 8003, etc.)
        $projects = DB::table('projects')->get();
        foreach ($projects as $project) {
            DB::table('projects')
                ->where('id', $project->id)
                ->update(['port' => 8000 + $project->id]);
        }
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('port');
        });
    }
};
