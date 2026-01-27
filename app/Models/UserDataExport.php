<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class UserDataExport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'error_message',
        'file_path',
        'file_size',
        'expires_at',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'expires_at' => 'datetime',
    ];

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
     * Scope to filter expired exports
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to filter stuck exports (processing for too long)
     * Considers an export stuck if it's been processing for more than 15 minutes
     */
    public function scopeStuck($query, int $minutes = 15)
    {
        return $query->whereIn('status', ['pending', 'processing'])
            ->where('updated_at', '<=', now()->subMinutes($minutes));
    }

    /**
     * Check if this export is stuck
     */
    public function isStuck(int $minutes = 15): bool
    {
        return in_array($this->status, ['pending', 'processing'])
            && $this->updated_at->diffInMinutes(now()) >= $minutes;
    }

    /**
     * Reset a stuck export so it can be retried
     */
    public function resetIfStuck(int $minutes = 15): bool
    {
        if ($this->isStuck($minutes)) {
            $this->update([
                'status' => 'failed',
                'error_message' => 'Export timed out after '.$minutes.' minutes. Please try again.',
            ]);

            return true;
        }

        return false;
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

        return route('user-data-export.download', ['export' => $this->id]);
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
    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
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

    /**
     * Get human-readable file size
     */
    public function getFormattedFileSizeAttribute(): string
    {
        if (! $this->file_size) {
            return 'N/A';
        }

        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
// CLAUDE-CHECKPOINT
