<?php

namespace App\Services\Reconciliation;

/**
 * Result object from SmartReconciliationService.
 *
 * Represents the best suggested action for a bank transaction,
 * along with alternatives the user can choose from.
 */
class SmartSuggestion
{
    // Action types
    public const ACTION_LINK_BILL = 'link_bill';

    public const ACTION_LINK_INVOICE = 'link_invoice';

    public const ACTION_LINK_PAYROLL = 'link_payroll';

    public const ACTION_CREATE_EXPENSE = 'create_expense';

    public const ACTION_RECORD_INCOME = 'record_income';

    public const ACTION_MARK_REVIEWED = 'mark_reviewed';

    public function __construct(
        public string $action,
        public float $confidence,
        public string $reason,
        public ?int $targetId = null,
        public ?string $targetLabel = null,
        public ?int $categoryId = null,
        public ?string $categoryName = null,
        public array $alternatives = [],
    ) {}

    public function toArray(): array
    {
        return [
            'action' => $this->action,
            'confidence' => round($this->confidence, 2),
            'reason' => $this->reason,
            'target_id' => $this->targetId,
            'target_label' => $this->targetLabel,
            'category_id' => $this->categoryId,
            'category_name' => $this->categoryName,
            'alternatives' => array_map(fn ($alt) => $alt instanceof self ? $alt->toArray() : $alt, $this->alternatives),
        ];
    }
}
// CLAUDE-CHECKPOINT
