<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Fix slug uniqueness conflict with soft deletes.
 *
 * Problem: The `slug` column has a UNIQUE constraint, but soft-deleted records
 * keep their original slug. When a user deletes a project and tries to recreate
 * it with the same slug, the DB constraint fails even though Laravel validation passes.
 *
 * Solution: Append '-deleted-{id}' to slugs of already soft-deleted records.
 * Combined with the model event in Project::booted(), future soft-deletes
 * will automatically modify the slug to free it for reuse.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Fix existing soft-deleted records that hold slugs hostage
        $softDeleted = DB::table('projects')
            ->whereNotNull('deleted_at')
            ->whereRaw("slug NOT LIKE '%-deleted-%'")
            ->get(['id', 'slug']);

        foreach ($softDeleted as $record) {
            DB::table('projects')
                ->where('id', $record->id)
                ->update(['slug' => $record->slug . '-deleted-' . $record->id]);
        }
    }

    public function down(): void
    {
        // Restore original slugs for soft-deleted records
        $softDeleted = DB::table('projects')
            ->whereNotNull('deleted_at')
            ->whereRaw("slug LIKE '%-deleted-%'")
            ->get(['id', 'slug']);

        foreach ($softDeleted as $record) {
            $originalSlug = preg_replace('/-deleted-\d+$/', '', $record->slug);
            DB::table('projects')
                ->where('id', $record->id)
                ->update(['slug' => $originalSlug]);
        }
    }
};
