<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'format',
        'params',
        'status',
        'error_message',
        'file_path',
        'row_count',
        'expires_at',
    ];

    protected $casts = [
        'params' => 'array',
        'row_count' => 'integer',
        'expires_at' => 'datetime',
    ];

    /**
     * Scope to filter by company
     */
    public function scopeWhereCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to filter by user
     */
    public function scopeWhereUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeWhereStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter expired jobs
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Relationship to company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship to user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get download URL for the exported file
     */
    public function getDownloadUrl(): ?string
    {
        if (! $this->file_path || $this->status !== 'completed') {
            return null;
        }

        // Check if file exists
        if (! Storage::exists($this->file_path)) {
            return null;
        }

        // Check if expired
        if ($this->expires_at && $this->expires_at->isPast()) {
            return null;
        }

        return route('exports.download', ['company' => $this->company_id, 'export' => $this->id]);
    }

    /**
     * Mark export as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'error_message' => null,
        ]);
    }

    /**
     * Mark export as completed
     */
    public function markAsCompleted(string $filePath, int $rowCount): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'row_count' => $rowCount,
            'expires_at' => now()->addDays(7),
            'error_message' => null,
        ]);
    }

    /**
     * Mark export as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Delete the exported file from storage
     */
    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }

        return false;
    }
}
// CLAUDE-CHECKPOINT
