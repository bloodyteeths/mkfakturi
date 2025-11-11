<?php

namespace App\Models;

use App\Traits\HasAuditing;
use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

/**
 * Certificate Model
 *
 * Manages QES (Qualified Electronic Signature) certificates for e-invoice signing.
 * Stores encrypted private keys and tracks certificate lifecycle.
 *
 * IMPORTANT: Private keys are ALWAYS encrypted at rest.
 * NEVER store decrypted keys in the database.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $serial_number
 * @property string $fingerprint
 * @property \Carbon\Carbon $valid_from
 * @property \Carbon\Carbon $valid_to
 * @property string|null $encrypted_key_blob Encrypted P12/PFX blob
 * @property string|null $certificate_path Path to cert file in storage
 * @property bool $is_active
 * @property array|null $subject Certificate subject details
 * @property array|null $issuer Certificate issuer details
 * @property string|null $algorithm Signature algorithm (e.g., RSA-SHA256)
 * @property int|null $key_size Key size in bits (e.g., 2048, 4096)
 * @property \Carbon\Carbon|null $last_used_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Company $company
 * @property-read \Illuminate\Database\Eloquent\Collection|SignatureLog[] $signatureLogs
 */
class Certificate extends Model
{
    use HasAuditing;
    use HasFactory;
    use TenantScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'certificates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'serial_number',
        'fingerprint',
        'valid_from',
        'valid_to',
        'encrypted_key_blob',
        'certificate_path',
        'is_active',
        'subject',
        'issuer',
        'algorithm',
        'key_size',
        'last_used_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_to' => 'datetime',
            'is_active' => 'boolean',
            'subject' => 'array',
            'issuer' => 'array',
            'key_size' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<string>
     */
    protected $hidden = [
        'encrypted_key_blob',
    ];

    /**
     * Appended attributes.
     *
     * @var array<string>
     */
    protected $appends = [
        'is_expired',
        'is_valid',
        'days_until_expiry',
    ];

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'company:id,name',
    ];

    /**
     * Get the company that owns this certificate.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all signature logs for this certificate.
     *
     * @return HasMany
     */
    public function signatureLogs(): HasMany
    {
        return $this->hasMany(SignatureLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * Decrypt the private key blob.
     * IMPORTANT: Only use this in memory, NEVER store the result.
     *
     * @param string|null $password Optional password for P12/PFX
     * @return string|null Decrypted key blob
     * @throws \Illuminate\Contracts\Encryption\DecryptException
     */
    public function decrypt(?string $password = null): ?string
    {
        if (!$this->encrypted_key_blob) {
            return null;
        }

        try {
            return Crypt::decryptString($this->encrypted_key_blob);
        } catch (\Exception $e) {
            \Log::error('Failed to decrypt certificate key', [
                'certificate_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if the certificate is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->valid_to->isPast();
    }

    /**
     * Check if the certificate is valid (not expired and active).
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return $this->is_active
            && !$this->isExpired()
            && $this->valid_from->isPast();
    }

    /**
     * Get days until expiry.
     *
     * @return int Negative if expired
     */
    public function daysUntilExpiry(): int
    {
        return now()->diffInDays($this->valid_to, false);
    }

    /**
     * Accessor: is_expired attribute.
     *
     * @return bool
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->isExpired();
    }

    /**
     * Accessor: is_valid attribute.
     *
     * @return bool
     */
    public function getIsValidAttribute(): bool
    {
        return $this->isValid();
    }

    /**
     * Accessor: days_until_expiry attribute.
     *
     * @return int
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        return $this->daysUntilExpiry();
    }

    /**
     * Update last used timestamp.
     * Called after successful signature operations.
     *
     * @return bool
     */
    public function markAsUsed(): bool
    {
        $this->last_used_at = now();
        return $this->save();
    }

    /**
     * Deactivate this certificate.
     * Useful when rotating certificates.
     *
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Activate this certificate.
     * Deactivates other certificates for the same company.
     *
     * @return bool
     */
    public function activate(): bool
    {
        // Deactivate other certificates for this company
        static::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_active' => false]);

        $this->is_active = true;
        return $this->save();
    }

    /**
     * Delete the certificate and its files.
     * Also logs the deletion.
     *
     * @return bool|null
     */
    public function delete(): ?bool
    {
        // Delete certificate file from storage
        if ($this->certificate_path && Storage::exists($this->certificate_path)) {
            Storage::delete($this->certificate_path);
        }

        // Log deletion (certificate_id is null since we're deleting it)
        // The metadata contains fingerprint and serial_number for audit trail
        SignatureLog::create([
            'certificate_id' => null, // Set to null to avoid foreign key constraint
            'company_id' => $this->company_id,
            'action' => SignatureLog::ACTION_DELETE,
            'user_id' => auth()->id(),
            'success' => true,
            'metadata' => [
                'certificate_id' => $this->id, // Store in metadata for reference
                'fingerprint' => $this->fingerprint,
                'serial_number' => $this->serial_number,
                'name' => $this->name,
            ],
        ]);

        return parent::delete();
    }

    /**
     * Scope: get active certificates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: get non-expired certificates.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeNotExpired($query)
    {
        return $query->where('valid_to', '>', now());
    }

    /**
     * Scope: get certificates expiring within N days.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExpiringWithin($query, int $days)
    {
        return $query->where('valid_to', '>', now())
            ->where('valid_to', '<=', now()->addDays($days));
    }

    /**
     * Scope: get valid certificates (active and not expired).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where('valid_from', '<=', now())
            ->where('valid_to', '>', now());
    }

    /**
     * Get the active certificate for a company.
     *
     * @param int $companyId
     * @return Certificate|null
     */
    public static function getActiveCertificate(int $companyId): ?Certificate
    {
        return static::where('company_id', $companyId)
            ->active()
            ->notExpired()
            ->first();
    }

    /**
     * Boot the model.
     * Auto-encrypt the key blob on save.
     */
    protected static function boot()
    {
        parent::boot();

        // Ensure only one active certificate per company
        static::saving(function ($certificate) {
            if ($certificate->is_active && $certificate->isDirty('is_active')) {
                static::where('company_id', $certificate->company_id)
                    ->where('id', '!=', $certificate->id)
                    ->update(['is_active' => false]);
            }
        });
    }
}

// CLAUDE-CHECKPOINT
