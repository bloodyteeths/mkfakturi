<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Account Model
 *
 * Represents an account in the Chart of Accounts.
 * Accounts are organized hierarchically and typed for classification.
 *
 * Types:
 * - asset: Assets (cash, receivables, inventory, etc.)
 * - liability: Liabilities (payables, loans, etc.)
 * - equity: Equity (capital, retained earnings, etc.)
 * - revenue: Revenue accounts (sales, services, etc.)
 * - expense: Expense accounts (cost of goods, operating expenses, etc.)
 *
 * @property int $id
 * @property int $company_id
 * @property string $code
 * @property string $name
 * @property int|null $parent_id
 * @property string $type
 * @property bool $is_active
 * @property bool $system_defined
 * @property array|null $meta
 * @property string|null $description
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Account extends Model
{
    use HasFactory;

    protected $table = 'accounts';

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'parent_id',
        'type',
        'is_active',
        'system_defined',
        'meta',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'system_defined' => 'boolean',
        'meta' => 'array',
    ];

    // Account types
    public const TYPE_ASSET = 'asset';

    public const TYPE_LIABILITY = 'liability';

    public const TYPE_EQUITY = 'equity';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_EXPENSE = 'expense';

    /**
     * Get the company that owns the account.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the parent account.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    /**
     * Get the child accounts.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    /**
     * Get all descendants recursively.
     */
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    /**
     * Scope to filter by company.
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('company_id', request()->header('company'));
    }

    /**
     * Scope to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter active accounts.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter root accounts (no parent).
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Get the full path of the account (parent codes > current code).
     */
    public function getFullPathAttribute(): string
    {
        $path = [$this->code];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->code);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Get formatted display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return "{$this->code} - {$this->name}";
    }

    /**
     * Check if account can be deleted.
     */
    public function canDelete(): bool
    {
        if ($this->system_defined) {
            return false;
        }

        // Check if has children
        if ($this->children()->exists()) {
            return false;
        }

        // Check if has mappings
        if (AccountMapping::where('debit_account_id', $this->id)
            ->orWhere('credit_account_id', $this->id)
            ->exists()) {
            return false;
        }

        return true;
    }

    /**
     * Get all available account types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_ASSET,
            self::TYPE_LIABILITY,
            self::TYPE_EQUITY,
            self::TYPE_REVENUE,
            self::TYPE_EXPENSE,
        ];
    }
}
// CLAUDE-CHECKPOINT
