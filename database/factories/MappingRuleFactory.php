<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\MappingRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MappingRuleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MappingRule::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $sourceField = $this->faker->word();
        $targetField = $this->faker->word();

        return [
            'name' => ucfirst($sourceField) . ' to ' . ucfirst($targetField), // Human-readable name
            'description' => $this->faker->sentence(),
            'company_id' => function () {
                return Company::factory()->create()->id;
            },
            'creator_id' => function () {
                return User::factory()->create()->id;
            },
            'entity_type' => $this->faker->randomElement([
                MappingRule::ENTITY_CUSTOMER,
                MappingRule::ENTITY_INVOICE,
                MappingRule::ENTITY_ITEM,
                MappingRule::ENTITY_PAYMENT,
                MappingRule::ENTITY_EXPENSE,
            ]),
            'source_system' => $this->faker->randomElement(['csv', 'onivo', 'megasoft', null]),
            'source_field' => $sourceField,
            'target_field' => $targetField,
            'transformation_type' => MappingRule::TRANSFORM_DIRECT,
            'field_variations' => [],
            'transformation_config' => [],
            'validation_rules' => [
                'required' => false,
                'type' => 'string',
            ],
            'business_rules' => [],
            'macedonian_patterns' => [],
            'language_variations' => [],
            'format_patterns' => [],
            'conditions' => [],
            'test_cases' => [],
            'sample_data' => [],
            'confidence_score' => $this->faker->randomFloat(2, 0.5, 1.0),
            'success_rate' => $this->faker->randomFloat(2, 70, 100),
            'usage_count' => $this->faker->numberBetween(0, 100),
            'success_count' => $this->faker->numberBetween(0, 100),
            'priority' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'is_system_rule' => false,
        ];
    }

    /**
     * Indicate that the mapping rule is for email validation
     */
    public function email(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'target_field' => 'email',
                'validation_rules' => [
                    'required' => false,
                    'type' => 'email',
                ],
            ];
        });
    }

    /**
     * Indicate that the mapping rule is for phone validation
     */
    public function phone(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'target_field' => 'phone',
                'validation_rules' => [
                    'required' => false,
                    'type' => 'phone',
                ],
            ];
        });
    }

    /**
     * Indicate that the mapping rule is for date validation
     */
    public function date(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'target_field' => 'invoice_date',
                'validation_rules' => [
                    'required' => false,
                    'type' => 'date',
                ],
            ];
        });
    }

    /**
     * Indicate that the mapping rule is for numeric validation
     */
    public function numeric(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'target_field' => 'total',
                'validation_rules' => [
                    'required' => false,
                    'type' => 'decimal',
                ],
            ];
        });
    }

    /**
     * Indicate that the field is required
     */
    public function required(): self
    {
        return $this->state(function (array $attributes) {
            $validationRules = $attributes['validation_rules'] ?? [];
            $validationRules['required'] = true;

            return [
                'validation_rules' => $validationRules,
            ];
        });
    }

    /**
     * Indicate that the mapping rule is inactive
     */
    public function inactive(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => false,
            ];
        });
    }

    /**
     * Indicate that the mapping rule is a system rule
     */
    public function systemRule(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'is_system_rule' => true,
            ];
        });
    }
}

// CLAUDE-CHECKPOINT
