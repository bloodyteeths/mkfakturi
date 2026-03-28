<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cession extends Model
{
    use SoftDeletes;

    protected $table = 'cessions';

    protected $fillable = [
        'company_id',
        'cession_number',
        'cession_date',
        'role',
        'cedent_name',
        'cedent_vat_id',
        'cedent_tax_id',
        'cessionary_name',
        'cessionary_vat_id',
        'cessionary_tax_id',
        'debtor_name',
        'debtor_vat_id',
        'debtor_tax_id',
        'amount',
        'original_document_type',
        'original_document_id',
        'original_document_number',
        'notes',
        'status',
        'confirmed_at',
        'creator_id',
    ];

    protected function casts(): array
    {
        return [
            'cession_date' => 'date',
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

        static::creating(function (Cession $cession) {
            if (empty($cession->cession_number)) {
                $year = date('Y');
                $sequence = static::where('company_id', $cession->company_id)
                    ->whereYear('created_at', $year)
                    ->count() + 1;
                $cession->cession_number = sprintf('CES-%d-%04d', $year, $sequence);
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
        return $query->where('cessions.company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('cessions.status', $status);
    }

    // ---- Accessors ----

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->amount / 100, 2, '.', ',');
    }
}

// CLAUDE-CHECKPOINT
