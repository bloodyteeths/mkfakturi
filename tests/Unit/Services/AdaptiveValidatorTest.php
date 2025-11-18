<?php

namespace Tests\Unit\Services;

use App\Models\Company;
use App\Models\MappingRule;
use App\Services\Import\Intelligent\AdaptiveValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Unit tests for AdaptiveValidator
 */
class AdaptiveValidatorTest extends TestCase
{
    use RefreshDatabase;

    private AdaptiveValidator $validator;

    private Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed essential data (currencies needed for Company/User factories)
        Artisan::call('db:seed', ['--class' => 'CurrenciesTableSeeder', '--force' => true]);
        Artisan::call('db:seed', ['--class' => 'CountriesTableSeeder', '--force' => true]);

        $this->company = Company::factory()->create();
        $this->validator = new AdaptiveValidator($this->company->id);
    }

    public function test_validates_required_fields()
    {
        // Create a mapping rule with required validation
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'email',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => [
                'required' => true,
                'type' => 'email',
            ],
            'is_active' => true,
        ]);

        $record = ['csv_email' => ''];
        $fieldMappings = ['csv_email' => 'email'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Required field', $result['errors'][0]);
    }

    public function test_validates_email_format()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'email',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => [
                'type' => 'email',
            ],
            'is_active' => true,
        ]);

        // Test invalid email
        $record = ['csv_email' => 'not-an-email'];
        $fieldMappings = ['csv_email' => 'email'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Invalid email format', $result['errors'][0]);

        // Test valid email
        $record = ['csv_email' => 'test@example.com'];
        $result = $this->validator->validate($record, $fieldMappings, 2);

        $this->assertEmpty($result['errors']);
    }

    public function test_validates_phone_format()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'phone',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => [
                'type' => 'phone',
            ],
            'is_active' => true,
        ]);

        // Test invalid phone
        $record = ['csv_phone' => '123'];
        $fieldMappings = ['csv_phone' => 'phone'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Phone number may be invalid', $result['warnings'][0]);

        // Test valid phone
        $record = ['csv_phone' => '+38970123456'];
        $result = $this->validator->validate($record, $fieldMappings, 2);

        $this->assertEmpty($result['warnings']);
    }

    public function test_validates_date_format()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'invoice_date',
            'entity_type' => MappingRule::ENTITY_INVOICE,
            'validation_rules' => [
                'type' => 'date',
            ],
            'is_active' => true,
        ]);

        // Test invalid date
        $record = ['csv_date' => 'not-a-date'];
        $fieldMappings = ['csv_date' => 'invoice_date'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Invalid date', $result['errors'][0]);

        // Test valid dates in different formats
        $validDates = [
            '2025-01-15',      // ISO
            '15.01.2025',      // European
            '15/01/2025',      // Alternative
        ];

        foreach ($validDates as $date) {
            $record = ['csv_date' => $date];
            $result = $this->validator->validate($record, $fieldMappings, 2);
            $this->assertEmpty($result['errors'], "Failed to validate date: {$date}");
        }
    }

    public function test_validates_numeric_fields()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'total',
            'entity_type' => MappingRule::ENTITY_INVOICE,
            'validation_rules' => [
                'type' => 'decimal',
            ],
            'is_active' => true,
        ]);

        // Test invalid number
        $record = ['csv_total' => 'not-a-number'];
        $fieldMappings = ['csv_total' => 'total'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('Must be a decimal number', $result['errors'][0]);

        // Test valid numbers (including European format)
        $validNumbers = ['100', '100.50', '100,50'];

        foreach ($validNumbers as $number) {
            $record = ['csv_total' => $number];
            $result = $this->validator->validate($record, $fieldMappings, 2);
            $this->assertEmpty($result['errors'], "Failed to validate number: {$number}");
        }
    }

    public function test_validates_business_rule_min_value()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'total',
            'entity_type' => MappingRule::ENTITY_INVOICE,
            'validation_rules' => [
                'type' => 'decimal',
            ],
            'business_rules' => [
                'min_value' => 0,
            ],
            'is_active' => true,
        ]);

        // Test value below minimum
        $record = ['csv_total' => '-100'];
        $fieldMappings = ['csv_total' => 'total'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('must be at least', $result['errors'][0]);

        // Test valid value
        $record = ['csv_total' => '100'];
        $result = $this->validator->validate($record, $fieldMappings, 2);

        $this->assertEmpty($result['errors']);
    }

    public function test_validates_business_rule_max_value()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'total',
            'entity_type' => MappingRule::ENTITY_INVOICE,
            'validation_rules' => [
                'type' => 'decimal',
            ],
            'business_rules' => [
                'max_value' => 999999,
            ],
            'is_active' => true,
        ]);

        // Test value above maximum
        $record = ['csv_total' => '10000000'];
        $fieldMappings = ['csv_total' => 'total'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('must not exceed', $result['errors'][0]);
    }

    public function test_validates_business_rule_regex()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'invoice_number',
            'entity_type' => MappingRule::ENTITY_INVOICE,
            'validation_rules' => [
                'type' => 'string',
            ],
            'business_rules' => [
                'regex' => '^INV-[0-9]{3,}$',
            ],
            'is_active' => true,
        ]);

        // Test invalid pattern
        $record = ['csv_number' => 'ABC-123'];
        $fieldMappings = ['csv_number' => 'invoice_number'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('does not match required pattern', $result['errors'][0]);

        // Test valid pattern
        $record = ['csv_number' => 'INV-12345'];
        $result = $this->validator->validate($record, $fieldMappings, 2);

        $this->assertEmpty($result['errors']);
    }

    public function test_validates_business_rule_enum()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'status',
            'entity_type' => MappingRule::ENTITY_INVOICE,
            'validation_rules' => [
                'type' => 'string',
            ],
            'business_rules' => [
                'enum' => ['draft', 'sent', 'paid'],
            ],
            'is_active' => true,
        ]);

        // Test invalid enum value
        $record = ['csv_status' => 'invalid'];
        $fieldMappings = ['csv_status' => 'status'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('must be one of', $result['errors'][0]);

        // Test valid enum values (case-insensitive)
        $validStatuses = ['draft', 'SENT', 'Paid'];

        foreach ($validStatuses as $status) {
            $record = ['csv_status' => $status];
            $result = $this->validator->validate($record, $fieldMappings, 2);
            $this->assertEmpty($result['errors'], "Failed to validate status: {$status}");
        }
    }

    public function test_validates_business_rule_min_max_length()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'name',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => [
                'type' => 'string',
            ],
            'business_rules' => [
                'min_length' => 3,
                'max_length' => 50,
            ],
            'is_active' => true,
        ]);

        // Test too short
        $record = ['csv_name' => 'AB'];
        $fieldMappings = ['csv_name' => 'name'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('at least 3 characters', $result['errors'][0]);

        // Test too long
        $record = ['csv_name' => str_repeat('A', 51)];
        $result = $this->validator->validate($record, $fieldMappings, 2);

        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('must not exceed 50 characters', $result['errors'][0]);

        // Test valid length
        $record = ['csv_name' => 'Valid Name'];
        $result = $this->validator->validate($record, $fieldMappings, 3);

        $this->assertEmpty($result['errors']);
    }

    public function test_validates_cross_field_subtotal_tax_total()
    {
        // Create mapping rules for financial fields
        foreach (['subtotal', 'tax', 'total'] as $field) {
            MappingRule::factory()->create([
                'company_id' => $this->company->id,
                'target_field' => $field,
                'entity_type' => MappingRule::ENTITY_INVOICE,
                'validation_rules' => ['type' => 'decimal'],
                'is_active' => true,
            ]);
        }

        // Test incorrect calculation
        $record = [
            'csv_subtotal' => '100',
            'csv_tax' => '20',
            'csv_total' => '150', // Should be 120
        ];
        $fieldMappings = [
            'csv_subtotal' => 'subtotal',
            'csv_tax' => 'tax',
            'csv_total' => 'total',
        ];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('does not match subtotal + tax', $result['warnings'][0]);

        // Test correct calculation
        $record = [
            'csv_subtotal' => '100',
            'csv_tax' => '20',
            'csv_total' => '120',
        ];

        $result = $this->validator->validate($record, $fieldMappings, 2);

        // Should not have this specific warning (might have others)
        $hasCalculationWarning = false;
        foreach ($result['warnings'] as $warning) {
            if (strpos($warning, 'does not match subtotal + tax') !== false) {
                $hasCalculationWarning = true;
            }
        }
        $this->assertFalse($hasCalculationWarning);
    }

    public function test_validates_cross_field_dates()
    {
        // Create mapping rules for date fields
        foreach (['invoice_date', 'due_date'] as $field) {
            MappingRule::factory()->create([
                'company_id' => $this->company->id,
                'target_field' => $field,
                'entity_type' => MappingRule::ENTITY_INVOICE,
                'validation_rules' => ['type' => 'date'],
                'is_active' => true,
            ]);
        }

        // Test due date before invoice date
        $record = [
            'csv_invoice_date' => '2025-01-15',
            'csv_due_date' => '2025-01-10',
        ];
        $fieldMappings = [
            'csv_invoice_date' => 'invoice_date',
            'csv_due_date' => 'due_date',
        ];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertNotEmpty($result['warnings']);
        $this->assertStringContainsString('Due date is before invoice date', $result['warnings'][0]);

        // Test correct date order
        $record = [
            'csv_invoice_date' => '2025-01-15',
            'csv_due_date' => '2025-01-30',
        ];

        $result = $this->validator->validate($record, $fieldMappings, 2);

        // Should not have this specific warning
        $hasDateWarning = false;
        foreach ($result['warnings'] as $warning) {
            if (strpos($warning, 'Due date is before invoice date') !== false) {
                $hasDateWarning = true;
            }
        }
        $this->assertFalse($hasDateWarning);
    }

    public function test_supports_macedonian_cyrillic_characters()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'name',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => [
                'type' => 'string',
            ],
            'business_rules' => [
                'min_length' => 3,
            ],
            'is_active' => true,
        ]);

        // Test Cyrillic name
        $record = ['csv_name' => 'Компанија ДООЕЛ'];
        $fieldMappings = ['csv_name' => 'name'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        $this->assertEmpty($result['errors']);
    }

    public function test_validates_boolean_values_with_macedonian_support()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'is_active',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => [
                'type' => 'boolean',
            ],
            'is_active' => true,
        ]);

        $fieldMappings = ['csv_active' => 'is_active'];

        // Test Macedonian "да" (yes)
        $record = ['csv_active' => 'да'];
        $result = $this->validator->validate($record, $fieldMappings, 1);
        $this->assertEmpty($result['warnings']);

        // Test Macedonian "не" (no)
        $record = ['csv_active' => 'не'];
        $result = $this->validator->validate($record, $fieldMappings, 2);
        $this->assertEmpty($result['warnings']);
    }

    public function test_skips_validation_for_inactive_rules()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'email',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => [
                'required' => true,
                'type' => 'email',
            ],
            'is_active' => false, // Inactive rule
        ]);

        $record = ['csv_email' => ''];
        $fieldMappings = ['csv_email' => 'email'];

        $result = $this->validator->validate($record, $fieldMappings, 1);

        // Should not validate because rule is inactive
        $this->assertEmpty($result['errors']);
    }

    public function test_caches_mapping_rules()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'email',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => ['type' => 'email'],
            'is_active' => true,
        ]);

        $record = ['csv_email' => 'test@example.com'];
        $fieldMappings = ['csv_email' => 'email'];

        // First validation - will load from DB
        $result1 = $this->validator->validate($record, $fieldMappings, 1);

        // Second validation - should use cache
        $result2 = $this->validator->validate($record, $fieldMappings, 2);

        // Both should produce same results
        $this->assertEquals($result1, $result2);
    }

    public function test_clears_cache()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'email',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => ['type' => 'email'],
            'is_active' => true,
        ]);

        $record = ['csv_email' => 'test@example.com'];
        $fieldMappings = ['csv_email' => 'email'];

        // First validation
        $this->validator->validate($record, $fieldMappings, 1);

        // Clear cache
        $this->validator->clearCache();

        // This should work without errors
        $result = $this->validator->validate($record, $fieldMappings, 2);
        $this->assertEmpty($result['errors']);
    }

    public function test_handles_url_validation()
    {
        MappingRule::factory()->create([
            'company_id' => $this->company->id,
            'target_field' => 'website',
            'entity_type' => MappingRule::ENTITY_CUSTOMER,
            'validation_rules' => ['type' => 'url'],
            'is_active' => true,
        ]);

        $fieldMappings = ['csv_website' => 'website'];

        // Test invalid URL
        $record = ['csv_website' => 'not a url'];
        $result = $this->validator->validate($record, $fieldMappings, 1);
        $this->assertNotEmpty($result['warnings']);

        // Test valid URLs
        $validUrls = [
            'https://example.com',
            'http://example.com',
            'https://example.com/path',
        ];

        foreach ($validUrls as $url) {
            $record = ['csv_website' => $url];
            $result = $this->validator->validate($record, $fieldMappings, 2);
            $this->assertEmpty($result['warnings'], "Failed to validate URL: {$url}");
        }
    }
}

// CLAUDE-CHECKPOINT
