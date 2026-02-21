<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    protected $table = 'ai_conversations';

    protected $guarded = ['id'];

    protected $casts = [
        'messages' => 'array',
        'is_active' => 'boolean',
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
     * Scope to filter active conversations only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get recent conversations
     */
    public function scopeRecent($query, int $limit = 5)
    {
        return $query->orderByDesc('updated_at')->limit($limit);
    }

    /**
     * Get parsed messages array
     *
     * @return array<int, array{role: string, content: string, timestamp: string}>
     */
    public function getMessages(): array
    {
        return $this->messages ?? [];
    }

    /**
     * Append a message to the conversation
     */
    public function addMessage(string $role, string $content): void
    {
        $messages = $this->getMessages();
        $messages[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => now()->toDateTimeString(),
        ];

        $this->update([
            'messages' => $messages,
            'message_count' => count($messages),
        ]);
    }
}
// CLAUDE-CHECKPOINT
