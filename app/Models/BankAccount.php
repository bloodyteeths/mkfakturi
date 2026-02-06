<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bank Account Model
 *
 * Represents a company's bank account, whether added manually or
 * discovered via PSD2 consent. Supports multi-account handling
 * with deduplication by IBAN and external_id.
 *
 * @property int $id
 * @property string $account_name
 * @property string $account_number
 * @property string|null $iban
 * @property string|null $swift_code
 * @property string $bank_name
 * @property string|null $bank_code
 * @property string|null $branch
 * @property string $account_type
 * @property int $currency_id
 * @property string $currency ISO 4217 currency code
 * @property int $company_id
 * @property int|null $bank_consent_id
 * @property string|null $nickname User-friendly display name
 * @property float $opening_balance
 * @property float $current_balance
 * @property bool $is_primary
 * @property bool $is_active
 * @property string $status active|inactive|disconnected
 * @property string|null $external_id PSD2 account resourceId
 * @property \Carbon\Carbon|null $last_synced_at
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read string $masked_iban
 * @property-read string $display_name
 *
 * @see \App\Services\Banking\Psd2AccountSyncService
 */
class BankAccount extends Model
{
    use BelongsToCompany;
    use HasFactory;

    // Status constants
    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_DISCONNECTED = 'disconnected';

    // Account type constants
    public const TYPE_CHECKING = 'checking';

    public const TYPE_SAVINGS = 'savings';

    public const TYPE_BUSINESS = 'business';

    protected $fillable = [
        'account_name',
        'account_number',
        'iban',
        'swift_code',
        'bank_name',
        'bank_code',
        'branch',
        'account_type',
        'currency_id',
        'currency',
        'company_id',
        'bank_consent_id',
        'nickname',
        'opening_balance',
        'current_balance',
        'is_primary',
        'is_active',
        'status',
        'external_id',
        'last_synced_at',
        'notes',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'last_synced_at' => 'datetime',
    ];

    /**
     * Get the company that owns the bank account.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the currency record of the bank account.
     *
     * Note: The `currency_id` FK links to the currencies table.
     * The `currency` string column holds the 3-char ISO code for PSD2.
     */
    public function currencyRelation(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    /**
     * Get the PSD2 consent that discovered this account.
     */
    public function consent(): BelongsTo
    {
        return $this->belongsTo(BankConsent::class, 'bank_consent_id');
    }

    /**
     * Get all transactions for this bank account.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(BankTransaction::class);
    }

    /**
     * Get masked IBAN for display (e.g., "MK07 **** **** 1234").
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function maskedIban(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->iban) {
                    return 'No IBAN';
                }

                $iban = $this->iban;
                $length = strlen($iban);

                if ($length <= 8) {
                    return $iban;
                }

                return substr($iban, 0, 4) . ' **** **** ' . substr($iban, -4);
            },
        );
    }

    /**
     * Get display name (nickname or account_name or masked IBAN).
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    protected function displayName(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->nickname) {
                    return $this->nickname;
                }

                if ($this->account_name) {
                    return $this->account_name;
                }

                return $this->masked_iban;
            },
        );
    }

    /**
     * Scope: Only include active bank accounts.
     *
     * Checks both the is_active boolean and the status enum column.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where('status', self::STATUS_ACTIVE)
                    ->orWhereNull('status');
            });
    }

    /**
     * Scope: Only include primary bank accounts.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope: Filter by status.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Filter accounts linked to a PSD2 consent.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $consentId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForConsent($query, int $consentId)
    {
        return $query->where('bank_consent_id', $consentId);
    }

    /**
     * Scope: Filter accounts that have been synced via PSD2.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePsd2Linked($query)
    {
        return $query->whereNotNull('external_id');
    }

    /**
     * Scope: Accounts needing sync (not synced within given hours).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $hours  Maximum hours since last sync
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNeedsSync($query, int $hours = 24)
    {
        return $query->whereNotNull('external_id')
            ->where('status', self::STATUS_ACTIVE)
            ->where(function ($q) use ($hours) {
                $q->whereNull('last_synced_at')
                    ->orWhere('last_synced_at', '<=', now()->subHours($hours));
            });
    }

    /**
     * Mark account as synced now.
     *
     * @return bool
     */
    public function markAsSynced(): bool
    {
        return $this->update([
            'last_synced_at' => now(),
        ]);
    }

    /**
     * Mark account as disconnected (e.g., consent expired).
     *
     * @return bool
     */
    public function disconnect(): bool
    {
        return $this->update([
            'status' => self::STATUS_DISCONNECTED,
            'is_active' => false,
        ]);
    }

    /**
     * Reactivate a disconnected account (e.g., new consent granted).
     *
     * @param  int|null  $consentId  New consent ID
     * @return bool
     */
    public function reactivate(?int $consentId = null): bool
    {
        $data = [
            'status' => self::STATUS_ACTIVE,
            'is_active' => true,
        ];

        if ($consentId) {
            $data['bank_consent_id'] = $consentId;
        }

        return $this->update($data);
    }

    /**
     * Check if this account is linked to PSD2.
     *
     * @return bool
     */
    public function isPsd2Linked(): bool
    {
        return ! empty($this->external_id);
    }

    /**
     * Check if this account is currently active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->is_active && ($this->status === self::STATUS_ACTIVE || $this->status === null);
    }

    /**
     * Check if this account needs syncing.
     *
     * @param  int  $hours  Maximum hours since last sync
     * @return bool
     */
    public function needsSync(int $hours = 24): bool
    {
        if (! $this->isPsd2Linked()) {
            return false;
        }

        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        if (! $this->last_synced_at) {
            return true;
        }

        return $this->last_synced_at->diffInHours(now()) >= $hours;
    }
}

// CLAUDE-CHECKPOINT
