<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add portfolio fields to partners, partner_company_links, and companies tables.
     *
     * Supports the Accountant Portfolio program where partners (accountants)
     * can manage multiple companies. Each paying company "covers" 1 non-paying
     * company for Standard features (1:1 sliding scale).
     */
    public function up(): void
    {
        // Partners: portfolio activation and grace period tracking
        if (Schema::hasTable('partners')) {
            if (! Schema::hasColumn('partners', 'portfolio_enabled')) {
                Schema::table('partners', function (Blueprint $table) {
                    $table->boolean('portfolio_enabled')->default(false)->after('payment_method');
                    $table->timestamp('portfolio_activated_at')->nullable()->after('portfolio_enabled');
                    $table->timestamp('portfolio_grace_ends_at')->nullable()->after('portfolio_activated_at');
                });
            }
        }

        // Partner company links: distinguish portfolio-managed from referral companies
        if (Schema::hasTable('partner_company_links')) {
            if (! Schema::hasColumn('partner_company_links', 'is_portfolio_managed')) {
                Schema::table('partner_company_links', function (Blueprint $table) {
                    $table->boolean('is_portfolio_managed')->default(false)->after('is_active');
                    $table->string('portfolio_tier_override', 20)->nullable()->after('is_portfolio_managed');

                    $table->index(['partner_id', 'is_portfolio_managed'], 'idx_partner_portfolio');
                });
            }
        }

        // Companies: portfolio management flag and managing partner reference
        if (Schema::hasTable('companies')) {
            if (! Schema::hasColumn('companies', 'is_portfolio_managed')) {
                Schema::table('companies', function (Blueprint $table) {
                    $table->boolean('is_portfolio_managed')->default(false)->after('subscription_tier');
                    $table->unsignedBigInteger('managing_partner_id')->nullable()->after('is_portfolio_managed');

                    $table->foreign('managing_partner_id')
                        ->references('id')
                        ->on('partners')
                        ->onDelete('set null');

                    $table->index(['is_portfolio_managed', 'managing_partner_id'], 'idx_portfolio_managed');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('companies')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropForeign(['managing_partner_id']);
                $table->dropIndex('idx_portfolio_managed');
                $table->dropColumn(['is_portfolio_managed', 'managing_partner_id']);
            });
        }

        if (Schema::hasTable('partner_company_links')) {
            Schema::table('partner_company_links', function (Blueprint $table) {
                $table->dropIndex('idx_partner_portfolio');
                $table->dropColumn(['is_portfolio_managed', 'portfolio_tier_override']);
            });
        }

        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn(['portfolio_enabled', 'portfolio_activated_at', 'portfolio_grace_ends_at']);
            });
        }
    }
};
// CLAUDE-CHECKPOINT
