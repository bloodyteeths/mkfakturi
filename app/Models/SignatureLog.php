<?php

namespace App\Models;

use App\Traits\HasAuditing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * SignatureLog Model
 *
 * Audit trail for all certificate and signature operations.
 * Tracks who signed what, when, and with which certificate.
 *
 * @property int $id
 * @property int|null $certificate_id
 * @property int $company_id
 * @property string $action
 * @property string|null $signable_type Polymorphic type (e.g., EInvoice)
 * @property int|null $signable_id Polymorphic ID
 * @property int|null $user_id
 * @property bool $success
 * @property string|null $error_message
 * @property array|null $metadata Additional context data
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property-read Certificate|null $certificate
 * @property-read Model|null $signable
 * @property-read User|null $user
 * @property-read Company $company
 */
class SignatureLog extends Model
{
    use HasAuditing;
    use HasFactory;

    /**
     * Action constants
     */
    public const ACTION_SIGN = 'SIGN';

    public const ACTION_VERIFY = 'VERIFY';

    public const ACTION_UPLOAD = 'UPLOAD';

    public const ACTION_DELETE = 'DELETE';

    public const ACTION_ROTATE = 'ROTATE';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'signature_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'certificate_id',
        'company_id',
        'action',
        'signable_type',
        'signable_id',
        'user_id',
        'success',
        'error_message',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'metadata' => 'array',
        ];
    }

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'certificate:id,name,fingerprint,company_id',
        'user:id,name,email',
    ];

    /**
     * Get the certificate used in this operation.
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Get the signable model (polymorphic).
     */
    public function signable(): MorphTo
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
     * Works through certificate relationship.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCompany($query, ?int $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('company_id', $companyId);
    }

    /**
     * Scope: filter by action.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: filter by certificate.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereCertificate($query, int $certificateId)
    {
        return $query->where('certificate_id', $certificateId);
    }

    /**
     * Scope: filter by user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: get successful operations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope: get failed operations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope: get sign operations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSignOperations($query)
    {
        return $query->where('action', self::ACTION_SIGN);
    }

    /**
     * Scope: get verify operations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVerifyOperations($query)
    {
        return $query->where('action', self::ACTION_VERIFY);
    }

    /**
     * Scope: get recent operations (last N days).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Log a signature operation.
     */
    public static function logOperation(
        ?Certificate $certificate,
        string $action,
        ?Model $signable = null,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): SignatureLog {
        return static::create([
            'certificate_id' => $certificate?->id,
            'company_id' => $certificate?->company_id ?? request()->header('company'),
            'action' => $action,
            'signable_type' => $signable ? get_class($signable) : null,
            'signable_id' => $signable?->id,
            'user_id' => auth()->id(),
            'success' => $success,
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a signature.
     */
    public static function logSign(
        Certificate $certificate,
        Model $signable,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): SignatureLog {
        return static::logOperation($certificate, self::ACTION_SIGN, $signable, $success, $errorMessage, $metadata);
    }

    /**
     * Log a verification.
     */
    public static function logVerify(
        ?Certificate $certificate,
        Model $signable,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): SignatureLog {
        return static::logOperation($certificate, self::ACTION_VERIFY, $signable, $success, $errorMessage, $metadata);
    }

    /**
     * Log a certificate upload.
     */
    public static function logUpload(
        Certificate $certificate,
        bool $success = true,
        ?string $errorMessage = null,
        ?array $metadata = null
    ): SignatureLog {
        return static::logOperation($certificate, self::ACTION_UPLOAD, null, $success, $errorMessage, $metadata);
    }

    /**
     * Log a certificate deletion.
     */
    public static function logDelete(Certificate $certificate, ?array $metadata = null): SignatureLog
    {
        return static::logOperation($certificate, self::ACTION_DELETE, null, true, null, $metadata);
    }

    /**
     * Log a certificate rotation.
     */
    public static function logRotate(
        Certificate $oldCertificate,
        Certificate $newCertificate,
        ?array $metadata = null
    ): SignatureLog {
        return static::logOperation(
            $newCertificate,
            self::ACTION_ROTATE,
            null,
            true,
            null,
            array_merge($metadata ?? [], [
                'old_certificate_id' => $oldCertificate->id,
                'old_fingerprint' => $oldCertificate->fingerprint,
                'new_certificate_id' => $newCertificate->id,
                'new_fingerprint' => $newCertificate->fingerprint,
            ])
        );
    }
}

// CLAUDE-CHECKPOINT
