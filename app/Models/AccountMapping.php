<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Account Mapping Model
 *
 * Maps entities (customers, suppliers, expense categories, etc.) to debit/credit accounts.
 * Used for generating journal entries when processing transactions.
 *
 * Entity Types:
 * - customer: Customer-specific account mappings
 * - supplier: Supplier-specific account mappings
 * - expense_category: Expense category account mappings
 * - tax_type: Tax type account mappings
 * - payment_method: Payment method account mappings
 * - default: Default mappings when no specific mapping exists
 *
 * @property int $id
 * @property int $company_id
 * @property string $entity_type
 * @property int|null $entity_id
 * @property int|null $debit_account_id
 * @property int|null $credit_account_id
 * @property string|null $transaction_type
 * @property array|null $meta
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class AccountMapping extends Model
{
    use HasFactory;

    protected $table = 'account_mappings';

    protected $fillable = [
        'company_id',
        'entity_type',
        'entity_id',
        'debit_account_id',
        'credit_account_id',
        'transaction_type',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    // Entity types
    public const ENTITY_CUSTOMER = 'customer';

    public const ENTITY_SUPPLIER = 'supplier';

    public const ENTITY_EXPENSE_CATEGORY = 'expense_category';

    public const ENTITY_TAX_TYPE = 'tax_type';

    public const ENTITY_PAYMENT_METHOD = 'payment_method';

    public const ENTITY_DEFAULT = 'default';

    // Transaction types
    public const TRANSACTION_INVOICE = 'invoice';

    public const TRANSACTION_PAYMENT = 'payment';

    public const TRANSACTION_EXPENSE = 'expense';

    public const TRANSACTION_ADJUSTMENT = 'adjustment';

    /**
     * Get the company that owns the mapping.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the debit account.
     */
    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account_id');
    }

    /**
     * Get the credit account.
     */
    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account_id');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('company_id', request()->header('company'));
    }

    /**
     * Scope to filter by entity type.
     */
    public function scopeForEntityType($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope to filter by entity.
     */
    public function scopeForEntity($query, string $entityType, ?int $entityId)
    {
        return $query->where('entity_type', $entityType)
            ->where('entity_id', $entityId);
    }

    /**
     * Find the mapping for a specific entity, falling back to default.
     */
    public static function findForEntity(int $companyId, string $entityType, ?int $entityId = null, ?string $transactionType = null): ?self
    {
        // First try to find specific mapping
        $query = self::where('company_id', $companyId)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId);

        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }

        $mapping = $query->first();

        if ($mapping) {
            return $mapping;
        }

        // Fall back to default mapping for this entity type
        $defaultQuery = self::where('company_id', $companyId)
            ->where('entity_type', $entityType)
            ->whereNull('entity_id');

        if ($transactionType) {
            $defaultQuery->where('transaction_type', $transactionType);
        }

        return $defaultQuery->first();
    }

    /**
     * Get all available entity types.
     */
    public static function getEntityTypes(): array
    {
        return [
            self::ENTITY_CUSTOMER,
            self::ENTITY_SUPPLIER,
            self::ENTITY_EXPENSE_CATEGORY,
            self::ENTITY_TAX_TYPE,
            self::ENTITY_PAYMENT_METHOD,
            self::ENTITY_DEFAULT,
        ];
    }

    /**
     * Get all available transaction types.
     */
    public static function getTransactionTypes(): array
    {
        return [
            self::TRANSACTION_INVOICE,
            self::TRANSACTION_PAYMENT,
            self::TRANSACTION_EXPENSE,
            self::TRANSACTION_ADJUSTMENT,
        ];
    }
}
// CLAUDE-CHECKPOINT
