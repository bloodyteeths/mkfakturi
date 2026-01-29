<?php

namespace App\Rules;

use Closure;
use Cron\CronExpression;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCronExpression implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if value is empty
        if (empty($value)) {
            $fail(__('validation.required', ['attribute' => $attribute]));
            return;
        }

        // Basic format check - should have 5 parts
        $parts = preg_split('/\s+/', trim($value));
        if (count($parts) !== 5) {
            $fail(__('Cron изразот мора да има 5 делови: минута час ден месец ден_од_недела'));
            return;
        }

        // Validate using the cron-expression library
        try {
            $cron = new CronExpression($value);
            // Try to get next run date to ensure it's valid
            $cron->getNextRunDate();
        } catch (\Exception $e) {
            $fail(__('Невалиден cron израз. Формат: минута(0-59) час(0-23) ден(1-31) месец(1-12) ден_од_недела(0-6)'));
        }
    }
}
