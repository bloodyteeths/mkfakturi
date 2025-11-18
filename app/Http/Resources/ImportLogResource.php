<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ImportLogResource extends JsonResource
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
            'import_job_id' => $this->import_job_id,
            'log_type' => $this->log_type,
            'message' => $this->message,
            'details' => $this->details,
            'batch_id' => $this->batch_id,
            'record_id' => $this->record_id,
            'field_name' => $this->field_name,
            'old_value' => $this->old_value,
            'new_value' => $this->new_value,
            'execution_time' => $this->execution_time,
            'memory_usage' => $this->memory_usage,
            'row_number' => $this->row_number,
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->created_at->diffForHumans(),
            'formatted_execution_time' => $this->execution_time ? number_format($this->execution_time, 2).'ms' : null,
            'formatted_memory_usage' => $this->memory_usage ? $this->formatBytes($this->memory_usage) : null,
        ];
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes($size, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }

        return round($size, $precision).' '.$units[$i];
    }
}
