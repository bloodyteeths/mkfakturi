<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Item::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'description' => $this->faker->text(),
            'company_id' => User::find(1)->companies()->first()->id,
            'price' => $this->faker->randomDigitNotNull(),
            'unit_id' => Unit::factory(),
            'creator_id' => User::where('role', 'super admin')->first()->company_id,
            'currency_id' => Currency::find(1)->id,
            'tax_per_item' => $this->faker->randomElement([true, false]),
        ];
    }

    /**
     * Indicate that the item should have a barcode.
     *
     * @param  string|null  $barcode  Custom barcode or null for auto-generated EAN-13
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withBarcode(?string $barcode = null): Factory
    {
        return $this->state(function (array $attributes) use ($barcode) {
            return [
                'barcode' => $barcode ?? $this->generateEan13(),
            ];
        });
    }

    /**
     * Indicate that the item should have a SKU.
     *
     * @param  string|null  $sku  Custom SKU or null for auto-generated
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function withSku(?string $sku = null): Factory
    {
        return $this->state(function (array $attributes) use ($sku) {
            return [
                'sku' => $sku ?? strtoupper($this->faker->bothify('SKU-####-????')),
            ];
        });
    }

    /**
     * Generate a valid EAN-13 barcode.
     *
     * @return string
     */
    private function generateEan13(): string
    {
        // Generate 12 random digits
        $code = '';
        for ($i = 0; $i < 12; $i++) {
            $code .= $this->faker->numberBetween(0, 9);
        }

        // Calculate check digit
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int) $code[$i] * (($i % 2 === 0) ? 1 : 3);
        }
        $checkDigit = (10 - ($sum % 10)) % 10;

        return $code . $checkDigit;
    }

    // CLAUDE-CHECKPOINT
}
