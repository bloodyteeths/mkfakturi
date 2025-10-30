<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportMappingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'mappings' => [
                'required',
                'array',
                'min:1',
            ],
            'mappings.*.source_field' => [
                'required',
                'string',
                'max:255',
            ],
            'mappings.*.target_field' => [
                'required',
                'string',
                'max:255',
            ],
            'mappings.*.transformation_type' => [
                'sometimes',
                Rule::in([
                    'none',
                    'date',
                    'decimal',
                    'currency',
                    'boolean',
                    'email',
                    'phone',
                    'uppercase',
                    'lowercase',
                    'trim',
                ]),
            ],
            'mappings.*.transformation_config' => [
                'sometimes',
                'array',
            ],
            'mappings.*.is_required' => [
                'sometimes',
                'boolean',
            ],
            'mappings.*.default_value' => [
                'sometimes',
                'nullable',
            ],
            'validation_rules' => [
                'sometimes',
                'array',
            ],
            'validation_rules.*.field' => [
                'required_with:validation_rules',
                'string',
                'max:255',
            ],
            'validation_rules.*.rules' => [
                'required_with:validation_rules',
                'array',
            ],
            'validation_rules.*.rules.*' => [
                'string',
            ],
            'skip_duplicates' => [
                'sometimes',
                'boolean',
            ],
            'update_existing' => [
                'sometimes',
                'boolean',
            ],
            'duplicate_handling' => [
                'sometimes',
                Rule::in(['skip', 'update', 'create_new', 'fail']),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'mappings.required' => 'At least one field mapping is required.',
            'mappings.*.source_field.required' => 'Source field name is required for all mappings.',
            'mappings.*.target_field.required' => 'Target field name is required for all mappings.',
            'mappings.*.transformation_type.in' => 'Invalid transformation type selected.',
            'validation_rules.*.field.required_with' => 'Field name is required for validation rules.',
            'validation_rules.*.rules.required_with' => 'Validation rules are required when field is specified.',
            'duplicate_handling.in' => 'Invalid duplicate handling strategy selected.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check for duplicate source fields
            $mappings = $this->input('mappings', []);
            $sourceFields = collect($mappings)->pluck('source_field');
            
            if ($sourceFields->count() !== $sourceFields->unique()->count()) {
                $validator->errors()->add('mappings', 'Duplicate source fields are not allowed.');
            }

            // Check for duplicate target fields
            $targetFields = collect($mappings)->pluck('target_field');
            if ($targetFields->count() !== $targetFields->unique()->count()) {
                $validator->errors()->add('mappings', 'Duplicate target fields are not allowed.');
            }

            // Validate transformation config based on type
            foreach ($mappings as $index => $mapping) {
                $type = $mapping['transformation_type'] ?? 'none';
                $config = $mapping['transformation_config'] ?? [];

                switch ($type) {
                    case 'date':
                        if (!isset($config['format']) || empty($config['format'])) {
                            $validator->errors()->add(
                                "mappings.{$index}.transformation_config.format",
                                'Date format is required for date transformations.'
                            );
                        }
                        break;
                        
                    case 'decimal':
                        if (isset($config['decimal_places']) && 
                            (!is_numeric($config['decimal_places']) || $config['decimal_places'] < 0)) {
                            $validator->errors()->add(
                                "mappings.{$index}.transformation_config.decimal_places",
                                'Decimal places must be a non-negative number.'
                            );
                        }
                        break;
                        
                    case 'currency':
                        if (!isset($config['from_currency']) || !isset($config['to_currency'])) {
                            $validator->errors()->add(
                                "mappings.{$index}.transformation_config",
                                'Both from_currency and to_currency are required for currency transformations.'
                            );
                        }
                        break;
                }
            }
        });
    }

    /**
     * Get the mapping payload.
     */
    public function getMappingPayload(): array
    {
        return [
            'mappings' => $this->mappings,
            'validation_rules' => $this->validation_rules ?? [],
            'skip_duplicates' => $this->boolean('skip_duplicates', false),
            'update_existing' => $this->boolean('update_existing', false),
            'duplicate_handling' => $this->duplicate_handling ?? 'skip',
        ];
    }
}