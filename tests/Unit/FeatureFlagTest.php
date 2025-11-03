<?php

namespace Tests\Unit;

use App\Helpers\FeatureHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Run Pennant migrations
        $this->artisan('migrate', ['--path' => 'vendor/laravel/pennant/database/migrations'])->run();
    }

    /**
     * Test that all feature flags default to safe values.
     */
    public function test_feature_flags_default_to_safe_values(): void
    {
        // All features should default to OFF
        $this->assertFalse(Feature::active('accounting-backbone'));
        $this->assertFalse(Feature::active('migration-wizard'));
        $this->assertFalse(Feature::active('psd2-banking'));
        $this->assertFalse(Feature::active('partner-portal'));
        $this->assertFalse(Feature::active('advanced-payments'));
        $this->assertFalse(Feature::active('mcp-ai-tools'));
        $this->assertFalse(Feature::active('monitoring'));

        // Partner mocked data should default to ON (safety)
        $this->assertTrue(Feature::active('partner-mocked-data'));
    }

    /**
     * Test that partner mocked data flag defaults to true for safety.
     */
    public function test_partner_mocked_data_defaults_to_true(): void
    {
        $this->assertTrue(Feature::active('partner-mocked-data'));
        $this->assertTrue(FeatureHelper::partnerMockedData());
    }

    /**
     * Test that FeatureHelper methods return correct boolean values.
     */
    public function test_feature_helper_methods(): void
    {
        $this->assertFalse(FeatureHelper::accountingEnabled());
        $this->assertFalse(FeatureHelper::migrationWizardEnabled());
        $this->assertFalse(FeatureHelper::psd2BankingEnabled());
        $this->assertFalse(FeatureHelper::partnerPortalEnabled());
        $this->assertTrue(FeatureHelper::partnerMockedData());
        $this->assertFalse(FeatureHelper::advancedPaymentsEnabled());
        $this->assertFalse(FeatureHelper::mcpAiToolsEnabled());
        $this->assertFalse(FeatureHelper::monitoringEnabled());
    }

    /**
     * Test that getAllFeatures returns all flags with correct defaults.
     */
    public function test_get_all_features_returns_correct_defaults(): void
    {
        $features = FeatureHelper::getAllFeatures();

        $this->assertIsArray($features);
        $this->assertCount(8, $features);

        $this->assertFalse($features['accounting-backbone']);
        $this->assertFalse($features['migration-wizard']);
        $this->assertFalse($features['psd2-banking']);
        $this->assertFalse($features['partner-portal']);
        $this->assertTrue($features['partner-mocked-data']);
        $this->assertFalse($features['advanced-payments']);
        $this->assertFalse($features['mcp-ai-tools']);
        $this->assertFalse($features['monitoring']);
    }

}

// CLAUDE-CHECKPOINT
