<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CRITICAL: This migration ensures e-invoice abilities exist for all companies
     * and assigns them to super admin roles. This fixes 403 authorization errors.
     */
    public function up(): void
    {
        echo '=== Ensuring E-Invoice Abilities Exist ==='.PHP_EOL;

        // Step 1: Update any existing abilities with old naming
        $updates = [
            'view-e-invoice' => 'view-einvoice',
            'create-e-invoice' => 'generate-einvoice',
            'submit-e-invoice' => 'submit-einvoice',
            'sign-e-invoice' => 'sign-einvoice',
        ];

        foreach ($updates as $oldName => $newName) {
            $updated = DB::table('abilities')
                ->where('name', $oldName)
                ->update(['name' => $newName]);

            if ($updated > 0) {
                echo "  ✅ Renamed: {$oldName} → {$newName}".PHP_EOL;
            }
        }

        // Step 2: Define all required e-invoice abilities
        $eInvoiceAbilities = [
            [
                'name' => 'view e-invoice',
                'ability' => 'view-einvoice',
                'model' => \App\Models\EInvoice::class,
            ],
            [
                'name' => 'generate e-invoice',
                'ability' => 'generate-einvoice',
                'model' => \App\Models\EInvoice::class,
            ],
            [
                'name' => 'submit e-invoice',
                'ability' => 'submit-einvoice',
                'model' => \App\Models\EInvoice::class,
            ],
            [
                'name' => 'sign e-invoice',
                'ability' => 'sign-einvoice',
                'model' => \App\Models\EInvoice::class,
            ],
        ];

        // Step 3: Get all companies and ensure they have e-invoice abilities
        $companies = \App\Models\Company::all();

        if ($companies->count() === 0) {
            echo '  ⚠️ No companies found - skipping ability assignment'.PHP_EOL;

            return;
        }

        echo "  Found {$companies->count()} companies".PHP_EOL;

        foreach ($companies as $company) {
            echo PHP_EOL."  Processing: {$company->name} (ID: {$company->id})".PHP_EOL;

            // Set Bouncer scope to this company
            BouncerFacade::scope()->to($company->id);

            // Find super admin role for this company
            $superAdmin = BouncerFacade::role()->where([
                'name' => 'super admin',
                'scope' => $company->id,
            ])->first();

            if (! $superAdmin) {
                echo '    ⚠️ No super admin role found'.PHP_EOL;

                continue;
            }

            echo "    Found super admin role (ID: {$superAdmin->id})".PHP_EOL;

            // Grant all e-invoice abilities to super admin
            foreach ($eInvoiceAbilities as $abilityConfig) {
                try {
                    BouncerFacade::allow($superAdmin)->to(
                        $abilityConfig['ability'],
                        $abilityConfig['model']
                    );
                    echo "    ✅ Granted: {$abilityConfig['ability']}".PHP_EOL;
                } catch (\Exception $e) {
                    echo "    ❌ Failed to grant {$abilityConfig['ability']}: {$e->getMessage()}".PHP_EOL;
                }
            }
        }

        echo PHP_EOL.'=== E-Invoice Abilities Migration Complete ==='.PHP_EOL;

        // Verification
        $totalAbilities = DB::table('abilities')
            ->where('name', 'like', '%einvoice%')
            ->count();
        echo "  Total e-invoice abilities in database: {$totalAbilities}".PHP_EOL;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't remove abilities in down() to avoid breaking permissions
        echo '  Note: E-invoice abilities are NOT removed to preserve permissions'.PHP_EOL;
    }
};
// CLAUDE-CHECKPOINT
