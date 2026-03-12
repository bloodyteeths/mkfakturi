<?php

use App\Models\Company;
use App\Models\CompanyInboundAlias;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('company_inbound_aliases')) {
            return;
        }

        $companyIds = Company::pluck('id');
        $existingCompanyIds = CompanyInboundAlias::pluck('company_id');
        $missingIds = $companyIds->diff($existingCompanyIds);

        foreach ($missingIds as $companyId) {
            $alias = 'bills-'.Str::lower(Str::random(8));

            while (CompanyInboundAlias::where('alias', $alias)->exists()) {
                $alias = 'bills-'.Str::lower(Str::random(8));
            }

            CompanyInboundAlias::create([
                'company_id' => $companyId,
                'alias' => $alias,
            ]);
        }
    }

    public function down(): void
    {
        // Aliases are harmless to keep; no rollback needed
    }
};
