<?php

namespace Database\Factories;

use App\Models\ImportLog;
use App\Models\ImportJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportLogFactory extends Factory
{
    protected $model = ImportLog::class;

    public function definition(): array
    {
        return [
            'import_job_id' => ImportJob::factory(),
            'log_type' => $this->faker->randomElement([
                ImportLog::TYPE_INFO,
                ImportLog::TYPE_WARNING,
                ImportLog::TYPE_ERROR,
                ImportLog::TYPE_DEBUG
            ]),
            'message' => $this->faker->sentence(),
            'details' => [
                'timestamp' => now()->toISOString(),
                'context' => $this->faker->word(),
            ],
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }

    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => ImportLog::TYPE_INFO,
            'message' => $this->faker->randomElement([
                'File uploaded successfully',
                'Import started',
                'Processing batch 1-100',
                'Validation completed',
                'Data commit started',
                'Import completed successfully',
            ]),
        ]);
    }

    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => ImportLog::TYPE_WARNING,
            'message' => $this->faker->randomElement([
                'Duplicate record found and skipped',
                'Invalid phone format, using default',
                'Missing optional field',
                'Currency conversion rate not found',
                'Large file detected, processing may take longer',
            ]),
            'details' => [
                'line_number' => $this->faker->numberBetween(1, 1000),
                'field_name' => $this->faker->randomElement(['email', 'phone', 'vat_number', 'currency']),
            ],
        ]);
    }

    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => ImportLog::TYPE_ERROR,
            'message' => $this->faker->randomElement([
                'Invalid email format',
                'Required field missing',
                'Database constraint violation',
                'File parsing error',
                'Validation failed',
                'Unknown customer reference',
            ]),
            'details' => [
                'line_number' => $this->faker->numberBetween(1, 1000),
                'field_name' => $this->faker->randomElement(['email', 'name', 'invoice_number', 'amount']),
                'error_code' => $this->faker->randomElement(['INVALID_FORMAT', 'MISSING_REQUIRED', 'CONSTRAINT_VIOLATION']),
                'original_value' => $this->faker->word(),
            ],
        ]);
    }

    public function debug(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_type' => ImportLog::TYPE_DEBUG,
            'message' => $this->faker->randomElement([
                'Memory usage: 45MB',
                'Processing time: 2.3 seconds',
                'Database query executed',
                'Cache hit for customer lookup',
                'Transformation applied successfully',
            ]),
            'details' => [
                'memory_usage' => $this->faker->numberBetween(10, 100) . 'MB',
                'execution_time' => $this->faker->randomFloat(2, 0.1, 10.0) . 's',
            ],
        ]);
    }
}