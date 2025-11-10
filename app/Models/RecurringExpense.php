<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'expense_category_id',
        'vendor_id',
        'currency_id',
        'amount',
        'notes',
        'frequency',
        'next_occurrence_at',
        'ends_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'next_occurrence_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Scope to filter by company
     */
    public function scopeWhereCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope to get active recurring expenses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            });
    }

    /**
     * Scope to get expenses due for processing
     */
    public function scopeDueForProcessing($query)
    {
        return $query->active()
            ->where('next_occurrence_at', '<=', now());
    }

    /**
     * Relationship to company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship to expense category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * Relationship to vendor (stored in customers table)
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'vendor_id');
    }

    /**
     * Relationship to currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship to creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Generate expense from this recurring expense
     */
    public function generateExpense(): Expense
    {
        $expense = Expense::create([
            'company_id' => $this->company_id,
            'expense_category_id' => $this->expense_category_id,
            'customer_id' => $this->vendor_id, // Vendors are stored in customers table
            'currency_id' => $this->currency_id,
            'amount' => $this->amount,
            'expense_date' => now(),
            'notes' => $this->notes . ' (Auto-generated from recurring expense)',
            'creator_id' => $this->created_by,
        ]);

        // Update next occurrence
        $this->updateNextOccurrence();

        return $expense;
    }

    /**
     * Update next occurrence date based on frequency
     */
    public function updateNextOccurrence(): void
    {
        $nextOccurrence = Carbon::parse($this->next_occurrence_at);

        $nextOccurrence = match ($this->frequency) {
            'daily' => $nextOccurrence->addDay(),
            'weekly' => $nextOccurrence->addWeek(),
            'monthly' => $nextOccurrence->addMonth(),
            'quarterly' => $nextOccurrence->addMonths(3),
            'yearly' => $nextOccurrence->addYear(),
            default => throw new \Exception("Unknown frequency: {$this->frequency}"),
        };

        // Check if next occurrence is past end date
        if ($this->ends_at && $nextOccurrence->greaterThan($this->ends_at)) {
            $this->update([
                'is_active' => false,
            ]);
        } else {
            $this->update([
                'next_occurrence_at' => $nextOccurrence,
            ]);
        }
    }

    /**
     * Deactivate this recurring expense
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate this recurring expense
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }
}
// CLAUDE-CHECKPOINT
