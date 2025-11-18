<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportJobFactory extends Factory
{
    protected $model = ImportJob::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'creator_id' => User::factory(),
            'type' => $this->faker->randomElement([
                ImportJob::TYPE_CUSTOMERS,
                ImportJob::TYPE_INVOICES,
                ImportJob::TYPE_ITEMS,
                ImportJob::TYPE_PAYMENTS,
                ImportJob::TYPE_EXPENSES,
                ImportJob::TYPE_COMPLETE,
            ]),
            'status' => ImportJob::STATUS_PENDING,
            'source_system' => $this->faker->randomElement([
                'onivo', 'megasoft', 'pantheon', 'syntegra', 'excel', 'csv', 'xml', 'other',
            ]),
            'file_path' => 'imports/'.$this->faker->uuid().'/'.$this->faker->uuid().'.csv',
            'file_info' => [
                'original_name' => $this->faker->word().'.csv',
                'filename' => $this->faker->uuid().'.csv',
                'extension' => 'csv',
                'size' => $this->faker->numberBetween(1024, 1048576), // 1KB to 1MB
                'mime_type' => 'text/csv',
            ],
            'mapping_config' => [],
            'validation_rules' => [],
            'total_records' => 0,
            'processed_records' => 0,
            'successful_records' => 0,
            'failed_records' => 0,
            'error_message' => null,
            'error_details' => null,
            'summary' => null,
            'started_at' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportJob::STATUS_PENDING,
        ]);
    }

    public function parsing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportJob::STATUS_PARSING,
            'started_at' => now()->subMinutes($this->faker->numberBetween(1, 30)),
        ]);
    }

    public function mapping(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportJob::STATUS_MAPPING,
            'started_at' => now()->subMinutes($this->faker->numberBetween(5, 60)),
        ]);
    }

    public function validating(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportJob::STATUS_VALIDATING,
            'started_at' => now()->subMinutes($this->faker->numberBetween(10, 120)),
            'total_records' => $this->faker->numberBetween(100, 10000),
            'processed_records' => $this->faker->numberBetween(50, 8000),
            'successful_records' => $this->faker->numberBetween(40, 7500),
            'failed_records' => $this->faker->numberBetween(0, 500),
        ]);
    }

    public function committing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ImportJob::STATUS_COMMITTING,
            'started_at' => now()->subMinutes($this->faker->numberBetween(30, 180)),
            'total_records' => $this->faker->numberBetween(100, 10000),
            'processed_records' => $this->faker->numberBetween(90, 9500),
            'successful_records' => $this->faker->numberBetween(85, 9000),
            'failed_records' => $this->faker->numberBetween(0, 100),
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $total = $this->faker->numberBetween(100, 10000);
            $failed = $this->faker->numberBetween(0, $total * 0.05); // Max 5% failure rate
            $successful = $total - $failed;

            return [
                'status' => ImportJob::STATUS_COMPLETED,
                'started_at' => now()->subHours($this->faker->numberBetween(1, 24)),
                'completed_at' => now()->subMinutes($this->faker->numberBetween(1, 60)),
                'total_records' => $total,
                'processed_records' => $total,
                'successful_records' => $successful,
                'failed_records' => $failed,
                'summary' => [
                    'duration' => $this->faker->numberBetween(60, 3600), // 1min to 1hour in seconds
                    'success_rate' => round(($successful / $total) * 100, 2),
                ],
            ];
        });
    }

    public function failed(): static
    {
        return $this->state(function (array $attributes) {
            $total = $this->faker->numberBetween(100, 10000);
            $processed = $this->faker->numberBetween(0, $total);
            $failed = $this->faker->numberBetween(1, $processed);
            $successful = $processed - $failed;

            return [
                'status' => ImportJob::STATUS_FAILED,
                'started_at' => now()->subHours($this->faker->numberBetween(1, 48)),
                'completed_at' => now()->subMinutes($this->faker->numberBetween(1, 120)),
                'total_records' => $total,
                'processed_records' => $processed,
                'successful_records' => $successful,
                'failed_records' => $failed,
                'error_message' => $this->faker->sentence(),
                'error_details' => [
                    'error_code' => $this->faker->randomElement(['DB_ERROR', 'TIMEOUT', 'VALIDATION_FAILED', 'FILE_CORRUPT']),
                    'line_number' => $this->faker->numberBetween(1, $processed),
                ],
            ];
        });
    }

    public function withMappings(): static
    {
        return $this->state(fn (array $attributes) => [
            'mapping_config' => [
                [
                    'source_field' => 'name',
                    'target_field' => 'name',
                    'transformation_type' => 'trim',
                    'is_required' => true,
                ],
                [
                    'source_field' => 'email',
                    'target_field' => 'email',
                    'transformation_type' => 'lowercase',
                    'is_required' => true,
                ],
                [
                    'source_field' => 'phone',
                    'target_field' => 'phone',
                    'transformation_type' => 'phone',
                    'is_required' => false,
                ],
            ],
            'validation_rules' => [
                [
                    'field' => 'email',
                    'rules' => ['email', 'unique:customers,email'],
                ],
            ],
        ]);
    }

    public function customers(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ImportJob::TYPE_CUSTOMERS,
        ]);
    }

    public function invoices(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ImportJob::TYPE_INVOICES,
        ]);
    }

    public function items(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ImportJob::TYPE_ITEMS,
        ]);
    }

    public function payments(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ImportJob::TYPE_PAYMENTS,
        ]);
    }

    public function expenses(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ImportJob::TYPE_EXPENSES,
        ]);
    }
}
