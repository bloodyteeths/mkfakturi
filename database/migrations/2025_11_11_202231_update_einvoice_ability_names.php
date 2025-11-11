<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Silber\Bouncer\BouncerFacade;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: Update existing abilities with old names to new names
        $updates = [
            'view-e-invoice' => 'view-einvoice',
            'create-e-invoice' => 'generate-einvoice',
            'submit-e-invoice' => 'submit-einvoice',
            'sign-e-invoice' => 'sign-einvoice',
        ];

        foreach ($updates as $oldName => $newName) {
            DB::table('abilities')
                ->where('name', $oldName)
                ->update(['name' => $newName]);
        }

        // Step 2: Ensure e-invoice abilities exist for all companies
        // This handles cases where companies were created before e-invoice abilities were added
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

        // Get all companies and assign e-invoice abilities to their super admin roles
        $companies = \App\Models\Company::all();

        foreach ($companies as $company) {
            // Set Bouncer scope to this company
            BouncerFacade::scope()->to($company->id);

            // Get super admin role for this company
            $superAdmin = BouncerFacade::role()->where([
                'name' => 'super admin',
                'scope' => $company->id,
            ])->first();

            if ($superAdmin) {
                // Grant all e-invoice abilities to super admin
                foreach ($eInvoiceAbilities as $abilityConfig) {
                    BouncerFacade::allow($superAdmin)->to(
                        $abilityConfig['ability'],
                        $abilityConfig['model']
                    );
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert ability names back to original
        $reverts = [
            'view-einvoice' => 'view-e-invoice',
            'generate-einvoice' => 'create-e-invoice',
            'submit-einvoice' => 'submit-e-invoice',
            'sign-einvoice' => 'sign-e-invoice',
        ];

        foreach ($reverts as $newName => $oldName) {
            DB::table('abilities')
                ->where('name', $newName)
                ->update(['name' => $oldName]);
        }

        // Note: We don't remove the abilities in down() because that could break existing permissions
    }
};
// CLAUDE-CHECKPOINT
