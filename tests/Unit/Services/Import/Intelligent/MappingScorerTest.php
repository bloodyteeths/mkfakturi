<?php

namespace Tests\Unit\Services\Import\Intelligent;

use App\Models\Company;
use App\Models\Currency;
use App\Models\MappingRule;
use App\Models\User;
use App\Services\Import\Intelligent\MappingScorer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for MappingScorer Service
 *
 * @package Tests\Unit\Services\Import\Intelligent
 */
class MappingScorerTest extends TestCase
{
    use RefreshDatabase;

    private MappingScorer $scorer;
    private Company $company;
    private Currency $currency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required database records
        $this->currency = Currency::factory()->create([
            'code' => 'MKD',
            'name' => 'Macedonian Denar',
            'symbol' => 'МКД',
            'precision' => 2,
        ]);

        $this->user = User::factory()->create([
            'currency_id' => $this->currency->id,
        ]);

        $this->company = Company::factory()->create([
            'owner_id' => $this->user->id,
        ]);

        $this->scorer = new MappingScorer();
    }

    public function test_excellent_quality_score()
    {
        // Create mapping rules with required fields
        $this->createCustomerMappingRules();

        // All critical fields mapped with high confidence
        $mappings = [
            'Customer Name' => [
                'target_field' => 'name',
                'confidence' => 0.95,
                'source' => 'rule',
            ],
            'Email Address' => [
                'target_field' => 'email',
                'confidence' => 0.92,
                'source' => 'rule',
            ],
            'Phone Number' => [
                'target_field' => 'phone',
                'confidence' => 0.90,
                'source' => 'pattern',
            ],
            'Tax ID' => [
                'target_field' => 'vat_number',
                'confidence' => 0.88,
                'source' => 'rule',
            ],
        ];

        $result = $this->scorer->calculateQuality(
            $mappings,
            MappingRule::ENTITY_CUSTOMER,
            4,
            $this->company->id
        );

        $this->assertGreaterThanOrEqual(90, $result['overall_score']);
        $this->assertEquals('EXCELLENT', $result['grade']);
        $this->assertEquals(100, $result['critical_coverage']);
        $this->assertGreaterThanOrEqual(85, $result['critical_confidence']);
        $this->assertEquals(4, $result['critical_fields_mapped']);
        $this->assertStringContainsString('high confidence', strtolower($result['recommendation']));
    }

    public function test_good_quality_score()
    {
        $this->createCustomerMappingRules();

        // Critical fields mapped but some with medium confidence
        $mappings = [
            'Customer Name' => [
                'target_field' => 'name',
                'confidence' => 0.95,
                'source' => 'rule',
            ],
            'Email Address' => [
                'target_field' => 'email',
                'confidence' => 0.85,
                'source' => 'rule',
            ],
            'Phone' => [
                'target_field' => 'phone',
                'confidence' => 0.65, // Medium confidence
                'source' => 'pattern',
            ],
            'Tax Number' => [
                'target_field' => 'vat_number',
                'confidence' => 0.70, // Medium confidence
                'source' => 'pattern',
            ],
        ];

        $result = $this->scorer->calculateQuality(
            $mappings,
            MappingRule::ENTITY_CUSTOMER,
            5, // One unmapped field
            $this->company->id
        );

        $this->assertGreaterThanOrEqual(75, $result['overall_score']);
        $this->assertLessThan(90, $result['overall_score']);
        $this->assertEquals('GOOD', $result['grade']);
        $this->assertEquals(100, $result['critical_coverage']);
    }

    public function test_fair_quality_score_with_missing_critical_field()
    {
        $this->createCustomerMappingRules();

        // Missing one critical field
        $mappings = [
            'Customer Name' => [
                'target_field' => 'name',
                'confidence' => 0.90,
                'source' => 'rule',
            ],
            'Email Address' => [
                'target_field' => 'email',
                'confidence' => 0.85,
                'source' => 'rule',
            ],
            'Phone' => [
                'target_field' => 'phone',
                'confidence' => 0.75,
                'source' => 'pattern',
            ],
            // Missing vat_number
        ];

        $result = $this->scorer->calculateQuality(
            $mappings,
            MappingRule::ENTITY_CUSTOMER,
            4,
            $this->company->id
        );

        $this->assertGreaterThanOrEqual(60, $result['overall_score']);
        $this->assertLessThan(75, $result['overall_score']);
        $this->assertEquals('FAIR', $result['grade']);
        $this->assertLessThan(100, $result['critical_coverage']);
        $this->assertContains('vat_number', $result['unmapped_critical_fields']);
    }

    public function test_poor_quality_score()
    {
        $this->createCustomerMappingRules();

        // Only half of critical fields mapped with low confidence
        $mappings = [
            'Name' => [
                'target_field' => 'name',
                'confidence' => 0.55, // Low confidence
                'source' => 'pattern',
            ],
            'Email' => [
                'target_field' => 'email',
                'confidence' => 0.45, // Low confidence
                'source' => 'pattern',
            ],
        ];

        $result = $this->scorer->calculateQuality(
            $mappings,
            MappingRule::ENTITY_CUSTOMER,
            5,
            $this->company->id
        );

        $this->assertGreaterThanOrEqual(40, $result['overall_score']);
        $this->assertLessThan(60, $result['overall_score']);
        $this->assertEquals('POOR', $result['grade']);
        $this->assertLessThan(100, $result['critical_coverage']);
        $this->assertStringContainsString('manual', strtolower($result['recommendation']));
    }

    public function test_failed_quality_score()
    {
        $this->createCustomerMappingRules();

        // Very few critical fields mapped
        $mappings = [
            'Name' => [
                'target_field' => 'name',
                'confidence' => 0.30,
                'source' => 'pattern',
            ],
        ];

        $result = $this->scorer->calculateQuality(
            $mappings,
            MappingRule::ENTITY_CUSTOMER,
            6,
            $this->company->id
        );

        $this->assertLessThan(40, $result['overall_score']);
        $this->assertEquals('FAILED', $result['grade']);
        $this->assertStringContainsString('FAILED', $result['recommendation']);
    }

    public function test_confidence_breakdown()
    {
        $this->createCustomerMappingRules();

        $mappings = [
            'Field1' => ['target_field' => 'name', 'confidence' => 0.95, 'source' => 'rule'],      // High
            'Field2' => ['target_field' => 'email', 'confidence' => 0.85, 'source' => 'rule'],     // High
            'Field3' => ['target_field' => 'phone', 'confidence' => 0.70, 'source' => 'pattern'],  // Medium
            'Field4' => ['target_field' => 'address', 'confidence' => 0.65, 'source' => 'pattern'], // Medium
            'Field5' => ['target_field' => 'city', 'confidence' => 0.45, 'source' => 'pattern'],    // Low
        ];

        $result = $this->scorer->calculateQuality(
            $mappings,
            MappingRule::ENTITY_CUSTOMER,
            5,
            $this->company->id
        );

        $this->assertEquals(2, $result['breakdown']['high_confidence_count']);
        $this->assertEquals(2, $result['breakdown']['medium_confidence_count']);
        $this->assertEquals(1, $result['breakdown']['low_confidence_count']);
    }

    public function test_data_quality_calculation()
    {
        $this->createCustomerMappingRules();

        $records = [
            ['Name' => 'John Doe', 'Email' => 'john@example.com', 'Phone' => '123456789'],
            ['Name' => 'Jane Smith', 'Email' => 'jane@example.com', 'Phone' => '987654321'],
            ['Name' => 'Bob Johnson', 'Email' => 'bob@example.com', 'Phone' => '555555555'],
        ];

        $mappings = [
            'Name' => ['target_field' => 'name', 'confidence' => 0.95, 'source' => 'rule'],
            'Email' => ['target_field' => 'email', 'confidence' => 0.90, 'source' => 'rule'],
            'Phone' => ['target_field' => 'phone', 'confidence' => 0.85, 'source' => 'pattern'],
        ];

        $result = $this->scorer->calculateDataQuality(
            $records,
            $mappings,
            MappingRule::ENTITY_CUSTOMER
        );

        $this->assertArrayHasKey('completeness', $result);
        $this->assertArrayHasKey('uniqueness', $result);
        $this->assertArrayHasKey('consistency', $result);
        $this->assertArrayHasKey('overall_quality', $result);
        $this->assertArrayHasKey('issues', $result);

        // With complete data, completeness should be 100%
        $this->assertEquals(100, $result['completeness']);
    }

    public function test_data_quality_with_incomplete_data()
    {
        $this->createCustomerMappingRules();

        $records = [
            ['Name' => 'John Doe', 'Email' => 'john@example.com', 'Phone' => ''],
            ['Name' => 'Jane Smith', 'Email' => '', 'Phone' => '987654321'],
            ['Name' => '', 'Email' => 'bob@example.com', 'Phone' => '555555555'],
        ];

        $mappings = [
            'Name' => ['target_field' => 'name', 'confidence' => 0.95, 'source' => 'rule'],
            'Email' => ['target_field' => 'email', 'confidence' => 0.90, 'source' => 'rule'],
            'Phone' => ['target_field' => 'phone', 'confidence' => 0.85, 'source' => 'pattern'],
        ];

        $result = $this->scorer->calculateDataQuality(
            $records,
            $mappings,
            MappingRule::ENTITY_CUSTOMER
        );

        // Completeness should be around 66% (6 out of 9 values filled)
        $this->assertLessThan(70, $result['completeness']);
        $this->assertGreaterThan(60, $result['completeness']);
        $this->assertNotEmpty($result['issues']);
    }

    public function test_data_quality_with_invalid_emails()
    {
        $this->createCustomerMappingRules();

        $records = [
            ['Name' => 'John Doe', 'Email' => 'invalid-email', 'Phone' => '123456789'],
            ['Name' => 'Jane Smith', 'Email' => 'jane@example.com', 'Phone' => '987654321'],
            ['Name' => 'Bob Johnson', 'Email' => 'not-an-email', 'Phone' => '555555555'],
        ];

        $mappings = [
            'Name' => ['target_field' => 'name', 'confidence' => 0.95, 'source' => 'rule'],
            'Email' => ['target_field' => 'email', 'confidence' => 0.90, 'source' => 'rule'],
            'Phone' => ['target_field' => 'phone', 'confidence' => 0.85, 'source' => 'pattern'],
        ];

        $result = $this->scorer->calculateDataQuality(
            $records,
            $mappings,
            MappingRule::ENTITY_CUSTOMER
        );

        // Should detect invalid email formats
        $hasEmailIssue = false;
        foreach ($result['issues'] as $issue) {
            if (stripos($issue, 'email') !== false && stripos($issue, 'invalid') !== false) {
                $hasEmailIssue = true;
                break;
            }
        }

        $this->assertTrue($hasEmailIssue, 'Should detect invalid email formats');
    }

    public function test_field_quality_metrics()
    {
        $this->createCustomerMappingRules();

        $records = [
            ['Name' => 'John Doe', 'Email' => 'john@example.com', 'Phone' => '123456789'],
            ['Name' => 'Jane Smith', 'Email' => 'jane@example.com', 'Phone' => ''],
            ['Name' => 'Bob Johnson', 'Email' => 'bob@example.com', 'Phone' => '555555555'],
        ];

        $mappings = [
            'Name' => ['target_field' => 'name', 'confidence' => 0.95, 'source' => 'rule'],
            'Email' => ['target_field' => 'email', 'confidence' => 0.90, 'source' => 'rule'],
            'Phone' => ['target_field' => 'phone', 'confidence' => 0.85, 'source' => 'pattern'],
        ];

        $result = $this->scorer->getFieldQualityMetrics($records, $mappings);

        $this->assertArrayHasKey('Name', $result);
        $this->assertArrayHasKey('Email', $result);
        $this->assertArrayHasKey('Phone', $result);

        // Check Name field metrics
        $this->assertEquals('name', $result['Name']['target_field']);
        $this->assertEquals(0.95, $result['Name']['confidence']);
        $this->assertEquals(3, $result['Name']['total_values']);
        $this->assertEquals(3, $result['Name']['non_empty_values']);
        $this->assertEquals(1.0, $result['Name']['completeness']);

        // Check Phone field metrics (one empty value)
        $this->assertEquals(2, $result['Phone']['non_empty_values']);
        $this->assertLessThan(1.0, $result['Phone']['completeness']);
    }

    public function test_no_critical_fields_defined()
    {
        // Test with entity type that has no critical fields
        $mappings = [
            'Field1' => ['target_field' => 'optional_field', 'confidence' => 0.80, 'source' => 'pattern'],
        ];

        $result = $this->scorer->calculateQuality(
            $mappings,
            MappingRule::ENTITY_CUSTOMER,
            1,
            $this->company->id
        );

        // Should still provide valid results
        $this->assertIsArray($result);
        $this->assertArrayHasKey('overall_score', $result);
        $this->assertArrayHasKey('grade', $result);
        $this->assertEquals(0, $result['critical_fields_total']);
        $this->assertEquals(100, $result['critical_coverage']); // 100% when no critical fields
    }

    public function test_empty_mappings()
    {
        $this->createCustomerMappingRules();

        $result = $this->scorer->calculateQuality(
            [],
            MappingRule::ENTITY_CUSTOMER,
            5,
            $this->company->id
        );

        $this->assertEquals(0, $result['overall_score']);
        $this->assertEquals('FAILED', $result['grade']);
        $this->assertEquals(0, $result['critical_coverage']);
        $this->assertEquals(0, $result['mapped_fields']);
    }

    /**
     * Create customer mapping rules with required fields
     */
    private function createCustomerMappingRules(): void
    {
        // Name - required
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'source_field' => 'name',
            'target_field' => 'name',
            'priority' => 1,
            'validation_rules' => ['required' => true],
            'is_active' => true,
        ]);

        // Email - required
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'source_field' => 'email',
            'target_field' => 'email',
            'priority' => 2,
            'validation_rules' => ['required' => true],
            'is_active' => true,
        ]);

        // Phone - required
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'source_field' => 'phone',
            'target_field' => 'phone',
            'priority' => 3,
            'validation_rules' => ['required' => true],
            'is_active' => true,
        ]);

        // VAT Number - required
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'source_field' => 'vat_number',
            'target_field' => 'vat_number',
            'priority' => 4,
            'validation_rules' => ['required' => true],
            'is_active' => true,
        ]);

        // Address - optional
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'source_field' => 'address',
            'target_field' => 'address',
            'priority' => 5,
            'validation_rules' => ['required' => false],
            'is_active' => true,
        ]);
    }
}

// CLAUDE-CHECKPOINT
