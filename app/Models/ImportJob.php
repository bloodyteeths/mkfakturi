<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportJob extends Model
{
    use HasFactory;

    // Job types
    public const TYPE_CUSTOMERS = 'customers';
    public const TYPE_INVOICES = 'invoices';
    public const TYPE_ITEMS = 'items';
    public const TYPE_PAYMENTS = 'payments';
    public const TYPE_EXPENSES = 'expenses';
     public const TYPE_BILLS = 'bills';
    public const TYPE_COMPLETE = 'complete';

    // Job statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PARSING = 'parsing';
    public const STATUS_MAPPING = 'mapping';
    public const STATUS_VALIDATING = 'validating';
    public const STATUS_COMMITTING = 'committing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    protected $guarded = ['id'];

    protected $appends = [
        'formattedCreatedAt',
        'formattedStartedAt',
        'formattedCompletedAt',
        'progressPercentage',
        'duration',
        'isInProgress',
        'canRetry',
    ];

    protected function casts(): array
    {
        return [
            'file_info' => 'array',
            'mapping_config' => 'array',
            'validation_rules' => 'array',
            'error_details' => 'array',
            'summary' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'total_records' => 'integer',
            'processed_records' => 'integer',
            'successful_records' => 'integer',
            'failed_records' => 'integer',
        ];
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function tempCustomers(): HasMany
    {
        return $this->hasMany(ImportTempCustomer::class);
    }

    public function tempInvoices(): HasMany
    {
        return $this->hasMany(ImportTempInvoice::class);
    }

    public function tempItems(): HasMany
    {
        return $this->hasMany(ImportTempItem::class);
    }

    public function tempPayments(): HasMany
    {
        return $this->hasMany(ImportTempPayment::class);
    }

    public function tempExpenses(): HasMany
    {
        return $this->hasMany(ImportTempExpense::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ImportLog::class);
    }

    // Accessors
    public function getFormattedCreatedAtAttribute()
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function getFormattedStartedAtAttribute()
    {
        if (!$this->started_at) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($this->started_at)->translatedFormat($dateFormat . ' H:i:s');
    }

    public function getFormattedCompletedAtAttribute()
    {
        if (!$this->completed_at) {
            return null;
        }
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
        return Carbon::parse($this->completed_at)->translatedFormat($dateFormat . ' H:i:s');
    }

    public function getProgressPercentageAttribute()
    {
        if ($this->total_records === 0) {
            return 0;
        }
        return round(($this->processed_records / $this->total_records) * 100, 2);
    }

    public function getDurationAttribute()
    {
        if (!$this->started_at) {
            return null;
        }
        
        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffForHumans($endTime, true);
    }

    public function getIsInProgressAttribute()
    {
        return in_array($this->status, [
            self::STATUS_PARSING,
            self::STATUS_MAPPING,
            self::STATUS_VALIDATING,
            self::STATUS_COMMITTING,
        ]);
    }

    public function getCanRetryAttribute()
    {
        return $this->status === self::STATUS_FAILED;
    }

    // Scopes
    public function scopeWhereCompany($query)
    {
        return $query->where('company_id', request()->header('company'));
    }

    public function scopeWhereType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeWhereStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeWhereCreator($query, $creatorId)
    {
        return $query->where('creator_id', $creatorId);
    }

    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PARSING,
            self::STATUS_MAPPING,
            self::STATUS_VALIDATING,
            self::STATUS_COMMITTING,
        ]);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('type')) {
            $query->whereType($filters->get('type'));
        }

        if ($filters->get('status')) {
            $query->whereStatus($filters->get('status'));
        }

        if ($filters->get('creator_id')) {
            $query->whereCreator($filters->get('creator_id'));
        }

        if ($filters->get('source_system')) {
            $query->where('source_system', $filters->get('source_system'));
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->whereBetween('created_at', [$start->startOfDay(), $end->endOfDay()]);
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ?: 'created_at';
            $orderBy = $filters->get('orderBy') ?: 'desc';
            $query->orderBy($field, $orderBy);
        } else {
            $query->orderBy('created_at', 'desc');
        }
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    // Helper methods
    public function updateProgress($processed = null, $successful = null, $failed = null)
    {
        $data = [];
        
        if ($processed !== null) {
            $data['processed_records'] = $processed;
        }
        
        if ($successful !== null) {
            $data['successful_records'] = $successful;
        }
        
        if ($failed !== null) {
            $data['failed_records'] = $failed;
        }

        $this->update($data);
    }

    public function markAsStarted()
    {
        $this->update([
            'status' => self::STATUS_PARSING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted($summary = null)
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'summary' => $summary,
        ]);
    }

    public function markAsFailed($errorMessage, $errorDetails = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
        ]);
    }

    public function getSuccessRate()
    {
        if ($this->processed_records === 0) {
            return 0;
        }
        
        return round(($this->successful_records / $this->processed_records) * 100, 2);
    }

    public function hasErrors()
    {
        return $this->failed_records > 0 || !empty($this->error_message);
    }

    public function canBeRetried()
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function getFileSize()
    {
        $fileInfo = $this->file_info;
        if (!$fileInfo || !isset($fileInfo['size'])) {
            return null;
        }
        
        return $this->formatBytes($fileInfo['size']);
    }

    private function formatBytes($size, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, $precision) . ' ' . $units[$i];
    }

    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PARSING => 'Parsing',
            self::STATUS_MAPPING => 'Mapping',
            self::STATUS_VALIDATING => 'Validating',
            self::STATUS_COMMITTING => 'Committing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public static function getTypeOptions()
    {
        return [
            self::TYPE_CUSTOMERS => 'Customers',
            self::TYPE_INVOICES => 'Invoices',
            self::TYPE_ITEMS => 'Items',
            self::TYPE_PAYMENTS => 'Payments',
            self::TYPE_EXPENSES => 'Expenses',
            self::TYPE_COMPLETE => 'Complete Business',
        ];
    }
}
