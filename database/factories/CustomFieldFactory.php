<?php

namespace Database\Factories;

use App\Models\CustomField;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomFieldFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CustomField::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $company = \App\Models\Company::first() ?? \App\Models\Company::factory()->create();

        return [
            'name' => $this->faker->name(),
            'label' => $this->faker->name(),
            'order' => $this->faker->randomDigitNotNull(),
            'is_required' => $this->faker->randomElement([true, false]),
            'model_type' => $this->faker->randomElement(['Customer', 'Invoice', 'Estimate', 'Expense', 'Payment']),
            'slug' => function (array $item) {
                return clean_slug($item['model_type'], $item['label']);
            },
            'type' => $this->faker->randomElement(['Text', 'Textarea', 'Phone', 'URL', 'Number', 'Dropdown', 'Switch', 'Date', 'DateTime', 'Time']),
            'company_id' => $company->id,
        ];
    }
}
