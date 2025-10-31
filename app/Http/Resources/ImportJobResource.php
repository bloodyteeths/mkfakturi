<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportJobResource extends JsonResource
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
            'type' => $this->type,
            'status' => $this->status,
            'source_system' => $this->source_system,
            'description' => $this->description,
            
            // File information
            'file_path' => $this->file_path,
            'file_info' => [
                'original_name' => $this->file_info['original_name'] ?? null,
                'filename' => $this->file_info['filename'] ?? null,
                'extension' => $this->file_info['extension'] ?? null,
                'size' => $this->file_info['size'] ?? null,
                'formatted_size' => $this->getFileSize(),
                'mime_type' => $this->file_info['mime_type'] ?? null,
            ],
            
            // Progress tracking
            'total_records' => $this->total_records,
            'processed_records' => $this->processed_records,
            'successful_records' => $this->successful_records,
            'failed_records' => $this->failed_records,
            'progress_percentage' => $this->progressPercentage,
            'success_rate' => $this->getSuccessRate(),
            
            // Status information
            'is_in_progress' => $this->isInProgress,
            'can_retry' => $this->canRetry,
            'has_errors' => $this->hasErrors(),
            'duration' => $this->duration,
            
            // Configuration
            'mapping_config' => $this->mapping_config,
            'validation_rules' => $this->validation_rules,
            
            // Error information
            'error_message' => $this->error_message,
            'error_details' => $this->error_details,
            
            // Summary
            'summary' => $this->summary,
            
            // Timestamps
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->formattedCreatedAt,
            'started_at' => $this->started_at,
            'formatted_started_at' => $this->formattedStartedAt,
            'completed_at' => $this->completed_at,
            'formatted_completed_at' => $this->formattedCompletedAt,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'company_id' => $this->company_id,
            'creator_id' => $this->creator_id,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            
            // Temp data counts (when loaded)
            'temp_data_counts' => $this->when($this->relationLoaded('tempCustomers'), function () {
                return [
                    'customers' => $this->tempCustomers->count(),
                    'invoices' => $this->tempInvoices->count(),
                    'items' => $this->tempItems->count(),
                    'payments' => $this->tempPayments->count(),
                    'expenses' => $this->tempExpenses->count(),
                ];
            }),
            
            // Recent logs (when loaded)
            'recent_logs' => $this->when($this->relationLoaded('logs'), function () {
                return $this->logs->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'log_type' => $log->log_type,
                        'message' => $log->message,
                        'details' => $log->details,
                        'created_at' => $log->created_at,
                        'formatted_created_at' => $log->created_at->diffForHumans(),
                    ];
                });
            }),
            
            // Error summary
            'error_summary' => $this->when($this->relationLoaded('logs'), function () {
                $errorLogs = $this->logs->where('log_type', 'error');
                return [
                    'total_errors' => $errorLogs->count(),
                    'error_types' => $errorLogs->groupBy('message')->map->count(),
                ];
            }),
        ];
    }
}