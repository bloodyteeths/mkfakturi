<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Assign super admin users to the first available company if they don't have one
        $firstCompany = DB::table('companies')->orderBy('id')->first();

        if ($firstCompany) {
            DB::table('users')
                ->where('role', 'super admin')
                ->whereNull('company_id')
                ->update(['company_id' => $firstCompany->id]);

            \Log::info('[Migration] Assigned super admin users to company', [
                'company_id' => $firstCompany->id,
                'company_name' => $firstCompany->name,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't reverse this - it's a data fix
    }
};
