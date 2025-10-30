<?php

namespace Database\Factories;

use App\Models\ImportTempCustomer;
use App\Models\ImportJob;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportTempCustomerFactory extends Factory
{
    protected $model = ImportTempCustomer::class;

    public function definition(): array
    {
        return [
            'import_job_id' => ImportJob::factory(),
            'row_number' => $this->faker->numberBetween(1, 10000),
            'name' => $this->faker->company(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'website' => $this->faker->optional()->url(),
            'prefix' => $this->faker->optional()->randomElement(['Mr.', 'Ms.', 'Dr.', 'Prof.']),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'contact_name' => $this->faker->name(),
            'vat_number' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{10,13}'),
            'currency_code' => $this->faker->randomElement(['MKD', 'EUR', 'USD', 'GBP']),
            'address' => [
                'name' => $this->faker->optional()->name(),
                'address_street_1' => $this->faker->streetAddress(),
                'address_street_2' => $this->faker->optional()->secondaryAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->optional()->state(),
                'zip' => $this->faker->postcode(),
                'country_id' => $this->faker->numberBetween(1, 250),
                'phone' => $this->faker->optional()->phoneNumber(),
                'fax' => $this->faker->optional()->phoneNumber(),
            ],
            'billing_address' => [
                'name' => $this->faker->optional()->name(),
                'address_street_1' => $this->faker->streetAddress(),
                'address_street_2' => $this->faker->optional()->secondaryAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->optional()->state(),
                'zip' => $this->faker->postcode(),
                'country_id' => $this->faker->numberBetween(1, 250),
                'phone' => $this->faker->optional()->phoneNumber(),
                'fax' => $this->faker->optional()->phoneNumber(),
            ],
            'shipping_address' => [
                'name' => $this->faker->optional()->name(),
                'address_street_1' => $this->faker->streetAddress(),
                'address_street_2' => $this->faker->optional()->secondaryAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->optional()->state(),
                'zip' => $this->faker->postcode(),
                'country_id' => $this->faker->numberBetween(1, 250),
                'phone' => $this->faker->optional()->phoneNumber(),
                'fax' => $this->faker->optional()->phoneNumber(),
            ],
            'validation_status' => $this->faker->randomElement(['pending', 'valid', 'invalid']),
            'validation_errors' => [],
            'mapped_data' => [],
            'original_data' => [
                'raw_name' => $this->faker->company(),
                'raw_email' => $this->faker->safeEmail(),
                'raw_phone' => $this->faker->phoneNumber(),
            ],
        ];
    }

    public function macedonian(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->randomElement([
                'Компанија Македонија ООД',
                'Балкан Трговија ДООЕЛ',
                'Евро Сервис ООД',
                'Македонски Телеком АД',
                'Скопски Саем АД'
            ]),
            'email' => $this->faker->unique()->userName() . '@example.mk',
            'phone' => '+3897' . $this->faker->numerify('#######'),
            'vat_number' => 'MK' . $this->faker->numerify('40########'),
            'currency_code' => 'MKD',
            'address' => [
                'address_street_1' => 'ул. ' . $this->faker->randomElement([
                    'Партизанска', 'Македонија', 'Јане Сандански', 'Маршал Тито', 'Кеј 13 Ноември'
                ]) . ' ' . $this->faker->numberBetween(1, 100),
                'city' => $this->faker->randomElement(['Скопје', 'Битола', 'Куманово', 'Прилеп', 'Тетово', 'Велес', 'Штип', 'Охрид', 'Гостивар', 'Струмица']),
                'zip' => $this->faker->numerify('####'),
                'country_id' => 142, // Macedonia country ID
            ],
        ]);
    }

    public function valid(): static
    {
        return $this->state(fn (array $attributes) => [
            'validation_status' => 'valid',
            'validation_errors' => [],
        ]);
    }

    public function invalid(): static
    {
        return $this->state(fn (array $attributes) => [
            'validation_status' => 'invalid',
            'validation_errors' => [
                'email' => ['Invalid email format'],
                'vat_number' => ['Invalid VAT number format'],
            ],
        ]);
    }

    public function withMappedData(): static
    {
        return $this->state(fn (array $attributes) => [
            'mapped_data' => [
                'name' => $attributes['name'] ?? $this->faker->company(),
                'email' => strtolower($attributes['email'] ?? $this->faker->safeEmail()),
                'phone' => $attributes['phone'] ?? $this->faker->phoneNumber(),
                'vat_number' => strtoupper($attributes['vat_number'] ?? ''),
                'currency_code' => $attributes['currency_code'] ?? 'MKD',
            ],
        ]);
    }
}