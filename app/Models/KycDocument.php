<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class KycDocument extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'partner_id',
        'document_type',
        'original_filename',
        'file_path',
        'encrypted_data',
        'status',
        'verified_at',
        'verified_by',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'metadata' => 'array',
        'encrypted_data' => 'encrypted', // Laravel's encrypted casting
    ];

    /**
     * Get the partner that owns this KYC document
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }

    /**
     * Get the admin user who verified this document
     */
    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope a query to only include pending documents
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved documents
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected documents
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Approve this KYC document
     *
     * @param int $adminUserId
     * @return bool
     */
    public function approve(int $adminUserId): bool
    {
        $this->status = 'approved';
        $this->verified_at = now();
        $this->verified_by = $adminUserId;
        $this->rejection_reason = null;

        return $this->save();
    }

    /**
     * Reject this KYC document
     *
     * @param int $adminUserId
     * @param string $reason
     * @return bool
     */
    public function reject(int $adminUserId, string $reason): bool
    {
        $this->status = 'rejected';
        $this->verified_at = now();
        $this->verified_by = $adminUserId;
        $this->rejection_reason = $reason;

        return $this->save();
    }

    /**
     * Check if all required documents are approved for a partner
     *
     * @param int $partnerId
     * @return bool
     */
    public static function allRequiredDocumentsApproved(int $partnerId): bool
    {
        $requiredTypes = ['id_card', 'proof_of_address'];

        foreach ($requiredTypes as $type) {
            $hasApproved = self::where('partner_id', $partnerId)
                ->where('document_type', $type)
                ->where('status', 'approved')
                ->exists();

            if (!$hasApproved) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the public URL for the document (if needed)
     * Note: This should be protected and only accessible by authorized users
     *
     * @return string|null
     */
    public function getDocumentUrl(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        // Generate temporary signed URL (expires in 1 hour)
        return Storage::temporaryUrl($this->file_path, now()->addHour());
    }

    /**
     * Delete the physical file when the model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($document) {
            if ($document->file_path && Storage::exists($document->file_path)) {
                Storage::delete($document->file_path);
            }
        });
    }
}

// CLAUDE-CHECKPOINT
