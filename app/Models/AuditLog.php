<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Crypt;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_name',
        'event',
        'description',
        'old_values',
        'new_values',
        'changed_fields',
        'ip_address',
        'user_agent',
        'url',
        'http_method',
        'batch_id',
        'tags',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'tags' => 'array',
    ];

    /**
     * PII fields that should be encrypted in old_values/new_values.
     */
    protected static array $piiFields = [
        'vat_id',
        'vat_number',
        'tax_id',
        'iban',
        'bank_account',
        'email',
        'phone',
        'ssn',
    ];

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope: filter by company.
     */
    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: filter by auditable type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('auditable_type', $modelType);
    }

    /**
     * Scope: filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: filter by date range.
     */
    public function scopeDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: filter by event type.
     */
    public function scopeEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Encrypt PII fields in values array.
     */
    public static function encryptPii(array $values): array
    {
        foreach (self::$piiFields as $field) {
            if (isset($values[$field]) && ! empty($values[$field])) {
                try {
                    $values[$field] = Crypt::encryptString($values[$field]);
                } catch (\Exception $e) {
                    // If encryption fails, mask instead
                    $values[$field] = self::maskValue($values[$field]);
                }
            }
        }

        return $values;
    }

    /**
     * Decrypt PII fields in values array.
     */
    public static function decryptPii(array $values): array
    {
        foreach (self::$piiFields as $field) {
            if (isset($values[$field]) && ! empty($values[$field])) {
                try {
                    $values[$field] = Crypt::decryptString($values[$field]);
                } catch (\Exception $e) {
                    // If decryption fails, leave as-is (might be masked or already decrypted)
                }
            }
        }

        return $values;
    }

    /**
     * Mask sensitive value for logging.
     */
    protected static function maskValue(string $value): string
    {
        if (strlen($value) <= 4) {
            return '****';
        }

        return substr($value, 0, 2) . str_repeat('*', strlen($value) - 4) . substr($value, -2);
    }

    /**
     * Get old values with PII decrypted.
     */
    public function getDecryptedOldValues(): ?array
    {
        return $this->old_values ? self::decryptPii($this->old_values) : null;
    }

    /**
     * Get new values with PII decrypted.
     */
    public function getDecryptedNewValues(): ?array
    {
        return $this->new_values ? self::decryptPii($this->new_values) : null;
    }
}
