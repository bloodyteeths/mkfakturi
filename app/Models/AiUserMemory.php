<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUserMemory extends Model
{
    protected $table = 'ai_user_memory';

    protected $guarded = ['id'];

    protected $casts = [
        'frequent_topics' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to filter by user and company
     */
    public function scopeForUser($query, int $userId, int $companyId)
    {
        return $query->where('user_id', $userId)->where('company_id', $companyId);
    }

    /**
     * Get or create a memory record for a user+company
     */
    public static function getOrCreate(int $userId, int $companyId): self
    {
        return static::firstOrCreate(
            ['user_id' => $userId, 'company_id' => $companyId],
            [
                'memory_summary' => '',
                'preferred_language' => 'mk',
                'frequent_topics' => [],
                'total_conversations' => 0,
                'total_messages' => 0,
            ]
        );
    }
}
// CLAUDE-CHECKPOINT
