<?php

namespace App\Models;

use App\Traits\HasAuditing;
use App\Traits\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * EInvoice Model
 *
 * Represents an electronic invoice in the e-Faktura system.
 * Links an Invoice to its UBL XML, signed XML, and submission history.
 *
 * @property int $id
 * @property int $invoice_id
 * @property int $company_id
 * @property string|null $ubl_xml
 * @property string|null $ubl_xml_signed
 * @property string|null $ubl_file_path
 * @property string|null $signed_file_path
 * @property string $status
 * @property array|null $subject Subject details from certificate
 * @property array|null $issuer Issuer details from certificate
 * @property int|null $certificate_id
 * @property \Carbon\Carbon|null $signed_at
 * @property \Carbon\Carbon|null $submitted_at
 * @property \Carbon\Carbon|null $accepted_at
 * @property \Carbon\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read Invoice $invoice
 * @property-read Company $company
 * @property-read Certificate|null $certificate
 * @property-read \Illuminate\Database\Eloquent\Collection|EInvoiceSubmission[] $submissions
 */
class EInvoice extends Model
{
    use HasAuditing;
    use HasFactory;
    use TenantScope;

    /**
     * Status constants
     */
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SIGNED = 'SIGNED';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_ACCEPTED = 'ACCEPTED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_FAILED = 'FAILED';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'e_invoices';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'invoice_id',
        'company_id',
        'ubl_xml',
        'ubl_xml_signed',
        'ubl_file_path',
        'signed_file_path',
        'status',
        'subject',
        'issuer',
        'certificate_id',
        'signed_at',
        'submitted_at',
        'accepted_at',
        'rejected_at',
        'rejection_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subject' => 'array',
            'issuer' => 'array',
            'signed_at' => 'datetime',
            'submitted_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array<string>
     */
    protected $hidden = [
        'ubl_xml',
        'ubl_xml_signed',
    ];

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'invoice:id,invoice_number,invoice_date,total,company_id,customer_id',
        'company:id,name',
    ];

    /**
     * Get the invoice that this e-invoice belongs to.
     *
     * @return BelongsTo
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the company that owns this e-invoice.
     *
     * @return BelongsTo
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the certificate used to sign this e-invoice.
     *
     * @return BelongsTo
     */
    public function certificate(): BelongsTo
    {
        return $this->belongsTo(Certificate::class);
    }

    /**
     * Get all submission attempts for this e-invoice.
     *
     * @return HasMany
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(EInvoiceSubmission::class)->orderBy('created_at', 'desc');
    }

    /**
     * Get the latest submission.
     *
     * @return EInvoiceSubmission|null
     */
    public function getLatestSubmissionAttribute(): ?EInvoiceSubmission
    {
        return $this->submissions()->first();
    }

    /**
     * Sign the e-invoice with a certificate.
     * Updates status to SIGNED and stores signature metadata.
     *
     * @param Certificate $certificate
     * @param string $signedXml
     * @param array|null $subject
     * @param array|null $issuer
     * @return bool
     */
    public function sign(Certificate $certificate, string $signedXml, ?array $subject = null, ?array $issuer = null): bool
    {
        $this->ubl_xml_signed = $signedXml;
        $this->certificate_id = $certificate->id;
        $this->subject = $subject;
        $this->issuer = $issuer;
        $this->status = self::STATUS_SIGNED;
        $this->signed_at = now();

        return $this->save();
    }

    /**
     * Submit the e-invoice to the tax authority.
     * Updates status to SUBMITTED.
     *
     * @return bool
     */
    public function submit(): bool
    {
        if ($this->status !== self::STATUS_SIGNED) {
            return false;
        }

        $this->status = self::STATUS_SUBMITTED;
        $this->submitted_at = now();

        return $this->save();
    }

    /**
     * Mark the e-invoice as accepted by the tax authority.
     *
     * @return bool
     */
    public function markAsAccepted(): bool
    {
        $this->status = self::STATUS_ACCEPTED;
        $this->accepted_at = now();
        $this->rejection_reason = null;

        return $this->save();
    }

    /**
     * Mark the e-invoice as rejected by the tax authority.
     *
     * @param string|null $reason
     * @return bool
     */
    public function markAsRejected(?string $reason = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->rejected_at = now();
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Mark the e-invoice as failed (error during processing).
     *
     * @param string|null $reason
     * @return bool
     */
    public function markAsFailed(?string $reason = null): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Check if the e-invoice is signed.
     *
     * @return bool
     */
    public function isSigned(): bool
    {
        return in_array($this->status, [
            self::STATUS_SIGNED,
            self::STATUS_SUBMITTED,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * Check if the e-invoice is submitted.
     *
     * @return bool
     */
    public function isSubmitted(): bool
    {
        return in_array($this->status, [
            self::STATUS_SUBMITTED,
            self::STATUS_ACCEPTED,
            self::STATUS_REJECTED,
        ]);
    }

    /**
     * Check if the e-invoice is accepted.
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the e-invoice is rejected.
     *
     * @return bool
     */
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    /**
     * Scope: filter by status.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: filter by invoice ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $invoiceId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    /**
     * Scope: get pending submissions (signed but not yet submitted).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePendingSubmission($query)
    {
        return $query->where('status', self::STATUS_SIGNED);
    }

    /**
     * Scope: get accepted e-invoices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope: get rejected e-invoices.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
}

// CLAUDE-CHECKPOINT
