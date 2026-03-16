<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Proforma invoices: unique number per company
        if (Schema::hasTable('proforma_invoices') && Schema::hasColumn('proforma_invoices', 'proforma_invoice_number')) {
            try {
                Schema::table('proforma_invoices', function (Blueprint $table) {
                    $table->unique(['proforma_invoice_number', 'company_id'], 'proforma_invoices_number_company_unique');
                });
            } catch (\Exception $e) {
                // Constraint may already exist or duplicates prevent creation
                \Illuminate\Support\Facades\Log::warning('Could not add unique constraint on proforma_invoices: '.$e->getMessage());
            }
        }

        // Estimates: unique number per company
        if (Schema::hasTable('estimates') && Schema::hasColumn('estimates', 'estimate_number')) {
            try {
                Schema::table('estimates', function (Blueprint $table) {
                    $table->unique(['estimate_number', 'company_id'], 'estimates_number_company_unique');
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Could not add unique constraint on estimates: '.$e->getMessage());
            }
        }

        // Projects: unique name per company
        if (Schema::hasTable('projects') && Schema::hasColumn('projects', 'name')) {
            try {
                Schema::table('projects', function (Blueprint $table) {
                    $table->unique(['name', 'company_id'], 'projects_name_company_unique');
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Could not add unique constraint on projects: '.$e->getMessage());
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('proforma_invoices')) {
            Schema::table('proforma_invoices', function (Blueprint $table) {
                $table->dropUnique('proforma_invoices_number_company_unique');
            });
        }

        if (Schema::hasTable('estimates')) {
            Schema::table('estimates', function (Blueprint $table) {
                $table->dropUnique('estimates_number_company_unique');
            });
        }

        if (Schema::hasTable('projects')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropUnique('projects_name_company_unique');
            });
        }
    }
};
