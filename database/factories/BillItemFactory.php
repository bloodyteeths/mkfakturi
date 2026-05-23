<?php

namespace Database\Factories;

use App\Models\BillItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class BillItemFactory extends Factory
{
    protected $model = BillItem::class;

    public function definition(): array
    {
        $price = $this->faker->numberBetween(1000, 50000);
        $quantity = $this->faker->numberBetween(1, 10);
        $tax = (int) round($price * $quantity * 0.18);
        $total = $price * $quantity + $tax;

        return [
            'name' => $this->faker->word(),
            'description' => $this->faker->sentence(),
            'quantity' => $quantity,
            'price' => $price,
            'discount' => 0,
            'discount_val' => 0,
            'tax' => $tax,
            'total' => $total,
            'exchange_rate' => 1,
            'base_price' => $price,
            'base_discount_val' => 0,
            'base_tax' => $tax,
            'base_total' => $total,
        ];
    }

    public function withItem(\App\Models\Item $item): self
    {
        return $this->state(['item_id' => $item->id]);
    }

    public function noTax(): self
    {
        return $this->state(function (array $attributes) {
            $net = $attributes['price'] * $attributes['quantity'];

            return [
                'tax' => 0,
                'base_tax' => 0,
                'total' => $net,
                'base_total' => $net,
            ];
        });
    }
}
