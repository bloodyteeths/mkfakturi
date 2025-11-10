<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'auditable_type' => $this->auditable_type,
            'auditable_id' => $this->auditable_id,
            'event' => $this->event,
            'old_values' => AuditLog::decryptPii($this->old_values ?? []),
            'new_values' => AuditLog::decryptPii($this->new_values ?? []),
            'changed_fields' => $this->changed_fields,
            'user_id' => $this->user_id,
            'user_name' => $this->user_name,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'url' => $this->url,
            'http_method' => $this->http_method,
            'description' => $this->description,
            'batch_id' => $this->batch_id,
            'tags' => $this->tags,
            'created_at' => $this->created_at,
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ];
            }),
            'auditable' => $this->whenLoaded('auditable', function () {
                // Return basic info about the audited entity
                return [
                    'id' => $this->auditable->id,
                    'type' => $this->auditable_type,
                    'name' => $this->getEntityName(),
                ];
            }),
        ];
    }

    /**
     * Get display name for the audited entity.
     *
     * @return string
     */
    protected function getEntityName(): string
    {
        if (!$this->auditable) {
            return 'N/A';
        }

        // Try common name attributes
        $nameAttributes = ['name', 'title', 'invoice_number', 'estimate_number', 'payment_number', 'number'];

        foreach ($nameAttributes as $attribute) {
            if (isset($this->auditable->{$attribute})) {
                return $this->auditable->{$attribute};
            }
        }

        // For models without a clear name, use the ID
        return class_basename($this->auditable_type) . ' #' . $this->auditable->id;
    }
}

// CLAUDE-CHECKPOINT
