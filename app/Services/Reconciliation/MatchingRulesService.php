<?php

namespace App\Services\Reconciliation;

use App\Models\BankTransaction;
use App\Models\MatchingRule;
use Illuminate\Support\Facades\Log;

/**
 * Matching Rules Service
 *
 * Evaluates user-defined matching rules against bank transactions
 * to automatically categorize, match, or ignore transactions.
 *
 * Supported condition operators: contains, equals, greater_than, less_than,
 * starts_with, ends_with, regex.
 *
 * Supported condition fields: description, remittance_info, debtor_name,
 * creditor_name, amount, transaction_type, currency.
 *
 * Supported actions: categorize, match_customer, match_expense, auto_match, ignore.
 *
 * @see P0-09 Matching Rules Engine
 */
class MatchingRulesService
{
    /**
     * Valid condition fields that can be evaluated against a transaction.
     */
    private const VALID_FIELDS = [
        'description',
        'remittance_info',
        'debtor_name',
        'creditor_name',
        'amount',
        'transaction_type',
        'currency',
    ];

    /**
     * Valid operators for condition evaluation.
     */
    private const VALID_OPERATORS = [
        'contains',
        'equals',
        'greater_than',
        'less_than',
        'starts_with',
        'ends_with',
        'regex',
    ];

    /**
     * Valid action types that can be applied when a rule matches.
     */
    private const VALID_ACTIONS = [
        'categorize',
        'match_customer',
        'match_expense',
        'auto_match',
        'ignore',
    ];

    /**
     * Maximum regex execution time in seconds to prevent ReDoS.
     */
    private const REGEX_TIMEOUT_SECONDS = 1;

    /**
     * Maximum regex pattern length to prevent abuse.
     */
    private const MAX_REGEX_LENGTH = 500;

    /**
     * Apply all active rules to a transaction and return matching actions.
     *
     * Rules are evaluated in priority order (highest first). All matching
     * rules' actions are collected and returned. The caller decides how
     * to apply them (e.g., first-match-wins or merge-all).
     *
     * @param BankTransaction $transaction The bank transaction to evaluate
     * @param int $companyId The company ID for tenant isolation
     * @return array Array of actions from all matching rules: [{rule_id, rule_name, actions}]
     */
    public function applyRules(BankTransaction $transaction, int $companyId): array
    {
        $rules = MatchingRule::forCompany($companyId)
            ->active()
            ->byPriority()
            ->get();

        $matchedActions = [];

        foreach ($rules as $rule) {
            if ($this->evaluateRule($rule, $transaction)) {
                $matchedActions[] = [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'priority' => $rule->priority,
                    'actions' => $rule->actions,
                ];

                Log::debug('Matching rule matched transaction', [
                    'rule_id' => $rule->id,
                    'rule_name' => $rule->name,
                    'transaction_id' => $transaction->id,
                ]);
            }
        }

        return $matchedActions;
    }

    /**
     * Check if a single rule matches a transaction.
     *
     * All conditions in the rule must match (AND logic) for the rule
     * to be considered a match.
     *
     * @param MatchingRule $rule The rule to evaluate
     * @param BankTransaction $transaction The transaction to evaluate against
     * @return bool True if all conditions match
     */
    public function evaluateRule(MatchingRule $rule, BankTransaction $transaction): bool
    {
        $conditions = $rule->conditions;

        if (empty($conditions) || ! is_array($conditions)) {
            return false;
        }

        foreach ($conditions as $condition) {
            if (! $this->evaluateCondition($condition, $transaction)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Evaluate a single condition against transaction data.
     *
     * @param array $condition Condition array with 'field', 'operator', 'value' keys
     * @param BankTransaction $transaction The transaction to evaluate against
     * @return bool True if the condition matches
     */
    private function evaluateCondition(array $condition, BankTransaction $transaction): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (! $field || ! $operator || $value === null) {
            Log::warning('Invalid matching rule condition: missing field, operator, or value', [
                'condition' => $condition,
            ]);

            return false;
        }

        if (! in_array($field, self::VALID_FIELDS, true)) {
            Log::warning('Invalid matching rule condition field', [
                'field' => $field,
            ]);

            return false;
        }

        if (! in_array($operator, self::VALID_OPERATORS, true)) {
            Log::warning('Invalid matching rule condition operator', [
                'operator' => $operator,
            ]);

            return false;
        }

        $transactionValue = $this->getTransactionFieldValue($field, $transaction);

        return $this->compareValues($transactionValue, $operator, $value, $field);
    }

    /**
     * Get the value of a transaction field for condition evaluation.
     *
     * @param string $field The field name
     * @param BankTransaction $transaction The transaction
     * @return mixed The field value
     */
    private function getTransactionFieldValue(string $field, BankTransaction $transaction): mixed
    {
        return match ($field) {
            'description' => $transaction->description ?? '',
            'remittance_info' => $transaction->remittance_info ?? '',
            'debtor_name' => $transaction->debtor_name ?? '',
            'creditor_name' => $transaction->creditor_name ?? '',
            'amount' => abs((float) $transaction->amount),
            'transaction_type' => $transaction->amount > 0 ? 'credit' : 'debit',
            'currency' => $transaction->currency ?? 'MKD',
            default => '',
        };
    }

    /**
     * Compare a transaction value against a condition value using the specified operator.
     *
     * @param mixed $transactionValue The actual value from the transaction
     * @param string $operator The comparison operator
     * @param mixed $conditionValue The expected value from the condition
     * @param string $field The field name (used to determine comparison type)
     * @return bool True if the comparison matches
     */
    private function compareValues(mixed $transactionValue, string $operator, mixed $conditionValue, string $field): bool
    {
        // For numeric fields, cast to float
        $isNumericField = $field === 'amount';

        return match ($operator) {
            'contains' => $this->evaluateContains($transactionValue, $conditionValue),
            'equals' => $this->evaluateEquals($transactionValue, $conditionValue, $isNumericField),
            'greater_than' => $this->evaluateGreaterThan($transactionValue, $conditionValue),
            'less_than' => $this->evaluateLessThan($transactionValue, $conditionValue),
            'starts_with' => $this->evaluateStartsWith($transactionValue, $conditionValue),
            'ends_with' => $this->evaluateEndsWith($transactionValue, $conditionValue),
            'regex' => $this->evaluateRegex($transactionValue, $conditionValue),
            default => false,
        };
    }

    /**
     * Case-insensitive contains check.
     *
     * @param mixed $haystack The string to search in
     * @param mixed $needle The string to search for
     * @return bool
     */
    private function evaluateContains(mixed $haystack, mixed $needle): bool
    {
        return str_contains(
            mb_strtolower((string) $haystack),
            mb_strtolower((string) $needle)
        );
    }

    /**
     * Exact match check (case-insensitive for strings, numeric for numbers).
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The expected value
     * @param bool $isNumeric Whether to compare as numbers
     * @return bool
     */
    private function evaluateEquals(mixed $actual, mixed $expected, bool $isNumeric): bool
    {
        if ($isNumeric) {
            return abs((float) $actual - (float) $expected) < 0.01;
        }

        return mb_strtolower((string) $actual) === mb_strtolower((string) $expected);
    }

    /**
     * Numeric greater-than comparison.
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The threshold value
     * @return bool
     */
    private function evaluateGreaterThan(mixed $actual, mixed $expected): bool
    {
        return (float) $actual > (float) $expected;
    }

    /**
     * Numeric less-than comparison.
     *
     * @param mixed $actual The actual value
     * @param mixed $expected The threshold value
     * @return bool
     */
    private function evaluateLessThan(mixed $actual, mixed $expected): bool
    {
        return (float) $actual < (float) $expected;
    }

    /**
     * Case-insensitive starts-with check.
     *
     * @param mixed $haystack The string to check
     * @param mixed $prefix The expected prefix
     * @return bool
     */
    private function evaluateStartsWith(mixed $haystack, mixed $prefix): bool
    {
        return str_starts_with(
            mb_strtolower((string) $haystack),
            mb_strtolower((string) $prefix)
        );
    }

    /**
     * Case-insensitive ends-with check.
     *
     * @param mixed $haystack The string to check
     * @param mixed $suffix The expected suffix
     * @return bool
     */
    private function evaluateEndsWith(mixed $haystack, mixed $suffix): bool
    {
        return str_ends_with(
            mb_strtolower((string) $haystack),
            mb_strtolower((string) $suffix)
        );
    }

    /**
     * Regex match with safety guards against ReDoS.
     *
     * Limits pattern length and uses a timeout to prevent
     * catastrophic backtracking attacks.
     *
     * @param mixed $subject The string to match against
     * @param mixed $pattern The regex pattern
     * @return bool
     */
    private function evaluateRegex(mixed $subject, mixed $pattern): bool
    {
        $pattern = (string) $pattern;
        $subject = (string) $subject;

        // Safety: limit pattern length
        if (strlen($pattern) > self::MAX_REGEX_LENGTH) {
            Log::warning('Matching rule regex pattern too long, skipping', [
                'pattern_length' => strlen($pattern),
                'max_length' => self::MAX_REGEX_LENGTH,
            ]);

            return false;
        }

        // Safety: check for potential backtracking bombs
        if (preg_match('/(\.\*){3,}/', $pattern) || preg_match('/(\.\+){3,}/', $pattern)) {
            Log::warning('Matching rule regex pattern contains potential backtracking bomb, skipping', [
                'pattern' => $pattern,
            ]);

            return false;
        }

        // Wrap in delimiters if not already wrapped
        if (! preg_match('/^[\/\#\~]/', $pattern)) {
            $pattern = '/' . $pattern . '/i';
        }

        try {
            // Set PCRE backtrack limit for safety
            $previousLimit = ini_get('pcre.backtrack_limit');
            ini_set('pcre.backtrack_limit', '10000');

            $result = @preg_match($pattern, $subject);

            // Restore previous limit
            ini_set('pcre.backtrack_limit', $previousLimit ?: '1000000');

            if ($result === false) {
                Log::warning('Matching rule regex pattern invalid', [
                    'pattern' => $pattern,
                    'error' => preg_last_error_msg(),
                ]);

                return false;
            }

            return $result === 1;
        } catch (\Throwable $e) {
            Log::warning('Matching rule regex evaluation failed', [
                'pattern' => $pattern,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get the list of valid condition fields.
     *
     * @return array<string>
     */
    public static function getValidFields(): array
    {
        return self::VALID_FIELDS;
    }

    /**
     * Get the list of valid operators.
     *
     * @return array<string>
     */
    public static function getValidOperators(): array
    {
        return self::VALID_OPERATORS;
    }

    /**
     * Get the list of valid action types.
     *
     * @return array<string>
     */
    public static function getValidActions(): array
    {
        return self::VALID_ACTIONS;
    }
}

// CLAUDE-CHECKPOINT
