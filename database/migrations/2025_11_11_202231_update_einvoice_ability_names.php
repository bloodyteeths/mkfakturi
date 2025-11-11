<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update e-invoice ability names to match policy expectations
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
    }
};
// CLAUDE-CHECKPOINT
