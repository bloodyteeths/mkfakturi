<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Fix the failed_jobs uuid column.
     *
     * The uuid column was created as NOT NULL with a unique constraint,
     * but existing rows have empty string '', preventing new failed jobs
     * from being inserted (duplicate entry '' for key 'failed_jobs_uuid_unique').
     */
    public function up(): void
    {
        if (! Schema::hasTable('failed_jobs')) {
            return;
        }

        // Fill empty uuids with actual UUIDs
        $emptyRows = DB::table('failed_jobs')->where('uuid', '')->get();
        foreach ($emptyRows as $row) {
            DB::table('failed_jobs')
                ->where('id', $row->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // Also fill any NULL uuids
        $nullRows = DB::table('failed_jobs')->whereNull('uuid')->get();
        foreach ($nullRows as $row) {
            DB::table('failed_jobs')
                ->where('id', $row->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }
    }

    public function down(): void
    {
        // No rollback needed
    }
};
