<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignation extends Model
{
    use SoftDeletes;

    protected $table = 'assignations';

    protected $fillable = [
        'company_id',
        'assignation_number',
        'assignation_date',
        'role',
        'assignor_name',
        'assignor_vat_id',
        'assignor_tax_id',
        'assignee_name',
        'assignee_vat_id',
        'assignee_tax_id',
        'debtor_name',
        'debtor_vat_id',
        'debtor_tax_id',
        'amount',
        'assignor_to_assignee_doc',
        'assignor_to_debtor_doc',
        'notes',
        'status',
        'confirmed_at',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'assignation_date' => 'date',
            'confirmed_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    protected $appends = [
        'formattedAmount',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Assignation $assignation) {
            if (empty($assignation->assignation_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $assignation->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $assignation->assignation_number = sprintf('ASG-%d-%04d', $year, $sequence);
            }
        });
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('assignations.company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('assignations.status', $status);
    }

    // ---- Accessors ----

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
