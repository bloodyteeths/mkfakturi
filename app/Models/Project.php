<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Project Model
 *
 * Represents a project for tracking invoices, expenses, and payments.
 * Used for construction projects, client projects, or any work that needs
 * separate financial tracking.
 *
 * Part of Phase 1.1 - Project Dimension feature for accountants.
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string|null $code
 * @property string|null $description
 * @property int|null $customer_id
 * @property string $status
 * @property int|null $budget_amount
 * @property int|null $currency_id
 * @property \Carbon\Carbon|null $start_date
 * @property \Carbon\Carbon|null $end_date
 * @property int|null $creator_id
 * @property string|null $notes
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 */
class Project extends Model
{
    use HasFactory;
    use SoftDeletes;

    // Status constants
    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'customer_id',
        'status',
        'budget_amount',
        'currency_id',
        'start_date',
        'end_date',
        'creator_id',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget_amount' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * The attributes that should be appended to the model.
     * Note: Financial totals (totalInvoiced, totalExpenses, etc.) are NOT auto-appended
     * to avoid N+1 query issues. Use getSummary() for financial data.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formattedCreatedAt',
        'formattedStartDate',
        'formattedEndDate',
    ];

    /**
     * Default eager loaded relationships.
     * Note: Relationships are loaded explicitly in controllers to avoid issues.
     *
     * @var array<int, string>
     */
    protected $with = [];

    // ============================================
    // RELATIONSHIPS
    // ============================================

    /**
     * Get the company that owns the project.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the customer associated with the project.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the currency for the project budget.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who created the project.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    /**
     * Get all invoices for the project.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get all expenses for the project.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Get all payments for the project.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get all bills for the project.
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Get all estimates for the project.
     */
    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    /**
     * Get all proforma invoices for the project.
     */
    public function proformaInvoices(): HasMany
    {
        return $this->hasMany(ProformaInvoice::class);
    }

    // ============================================
    // ACCESSORS
    // ============================================

    /**
     * Get formatted created at date.
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    /**
     * Get formatted start date.
     */
    public function getFormattedStartDateAttribute(): ?string
    {
        if (! $this->start_date) {
            return null;
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->start_date)->translatedFormat($dateFormat);
    }

    /**
     * Get formatted end date.
     */
    public function getFormattedEndDateAttribute(): ?string
    {
        if (! $this->end_date) {
            return null;
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->end_date)->translatedFormat($dateFormat);
    }

    /**
     * Get total invoiced amount for the project.
     */
    public function getTotalInvoicedAttribute(): int
    {
        return $this->invoices()->sum('base_total') ?? 0;
    }

    /**
     * Get total expenses for the project.
     */
    public function getTotalExpensesAttribute(): int
    {
        return $this->expenses()->sum('base_amount') ?? 0;
    }

    /**
     * Get total payments received for the project.
     */
    public function getTotalPaymentsAttribute(): int
    {
        return $this->payments()->sum('base_amount') ?? 0;
    }

    /**
     * Get net result (income - expenses) for the project.
     */
    public function getNetResultAttribute(): int
    {
        return $this->totalInvoiced - $this->totalExpenses;
    }

    // ============================================
    // SCOPES
    // ============================================

    /**
     * Scope to filter by company (multi-tenant).
     */
    public function scopeWhereCompany($query)
    {
        return $query->where('projects.company_id', request()->header('company'));
    }

    /**
     * Scope to filter by company ID.
     */
    public function scopeWhereCompanyId($query, $companyId)
    {
        return $query->where('projects.company_id', $companyId);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('projects.status', $status);
    }

    /**
     * Scope to filter by customer.
     */
    public function scopeWhereCustomer($query, int $customerId)
    {
        return $query->where('projects.customer_id', $customerId);
    }

    /**
     * Scope for open projects.
     */
    public function scopeOpen($query)
    {
        return $query->where('projects.status', self::STATUS_OPEN);
    }

    /**
     * Scope for in-progress projects.
     */
    public function scopeInProgress($query)
    {
        return $query->where('projects.status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope for completed projects.
     */
    public function scopeCompleted($query)
    {
        return $query->where('projects.status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for on-hold projects.
     */
    public function scopeOnHold($query)
    {
        return $query->where('projects.status', self::STATUS_ON_HOLD);
    }

    /**
     * Scope for cancelled projects.
     */
    public function scopeCancelled($query)
    {
        return $query->where('projects.status', self::STATUS_CANCELLED);
    }

    /**
     * Scope for active projects (not completed or cancelled).
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('projects.status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    // ============================================
    // STATUS LOGIC
    // ============================================

    /**
     * Check if project allows adding new documents (invoices, expenses, payments).
     * Only open and in_progress projects can receive new documents.
     */
    public function allowsNewDocuments(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    /**
     * Check if project is editable.
     * Completed and cancelled projects cannot be edited.
     */
    public function isEditable(): bool
    {
        return ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if project can be completed.
     */
    public function canBeCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_IN_PROGRESS, self::STATUS_ON_HOLD]);
    }

    /**
     * Check if project can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return $this->status !== self::STATUS_CANCELLED;
    }

    /**
     * Check if project can be reopened.
     */
    public function canBeReopened(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_ON_HOLD, self::STATUS_CANCELLED]);
    }

    /**
     * Mark project as completed.
     */
    public function markAsCompleted(): bool
    {
        if (! $this->canBeCompleted()) {
            return false;
        }
        $this->status = self::STATUS_COMPLETED;

        return $this->save();
    }

    /**
     * Mark project as cancelled.
     */
    public function markAsCancelled(): bool
    {
        if (! $this->canBeCancelled()) {
            return false;
        }
        $this->status = self::STATUS_CANCELLED;

        return $this->save();
    }

    /**
     * Reopen a completed/cancelled/on-hold project.
     */
    public function reopen(): bool
    {
        if (! $this->canBeReopened()) {
            return false;
        }
        $this->status = self::STATUS_OPEN;

        return $this->save();
    }

    /**
     * Put project on hold.
     */
    public function putOnHold(): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }
        $this->status = self::STATUS_ON_HOLD;

        return $this->save();
    }

    /**
     * Start working on project (move to in_progress).
     */
    public function startProgress(): bool
    {
        if (! in_array($this->status, [self::STATUS_OPEN, self::STATUS_ON_HOLD])) {
            return false;
        }
        $this->status = self::STATUS_IN_PROGRESS;

        return $this->save();
    }

    /**
     * Scope to search by name or code.
     */
    public function scopeWhereSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('projects.name', 'LIKE', '%'.$search.'%')
                ->orWhere('projects.code', 'LIKE', '%'.$search.'%')
                ->orWhere('projects.description', 'LIKE', '%'.$search.'%');
        });
    }

    /**
     * Apply filters from request.
     */
    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters)->filter()->all();

        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereSearch($search);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->whereStatus($status);
        })->when($filters['customer_id'] ?? null, function ($query, $customerId) {
            $query->whereCustomer($customerId);
        })->when(($filters['from_date'] ?? null) && ($filters['to_date'] ?? null), function ($query) use ($filters) {
            $start = Carbon::parse($filters['from_date']);
            $end = Carbon::parse($filters['to_date']);
            $query->whereBetween('projects.start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')]);
        })->when($filters['orderByField'] ?? null, function ($query, $orderByField) use ($filters) {
            $orderBy = $filters['orderBy'] ?? 'desc';
            $query->orderBy($orderByField, $orderBy);
        }, function ($query) {
            $query->orderBy('created_at', 'desc');
        });
    }

    /**
     * Scope for pagination.
     */
    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    // ============================================
    // STATIC METHODS
    // ============================================

    /**
     * Create a new project from request data.
     *
     * @param  object  $request
     */
    public static function createProject($request): Project
    {
        $data = $request->validated();
        $data['company_id'] = $request->header('company');
        $data['creator_id'] = auth()->id();

        return self::create($data);
    }

    /**
     * Update project from request data.
     *
     * @param  object  $request
     */
    public function updateProject($request): Project
    {
        $data = $request->validated();

        $this->update($data);

        return $this->fresh();
    }

    /**
     * Delete multiple projects.
     */
    public static function deleteProjects(array $ids): bool
    {
        foreach ($ids as $id) {
            $project = self::find($id);

            if ($project) {
                // Check if project has any associated documents
                if ($project->invoices()->exists() ||
                    $project->expenses()->exists() ||
                    $project->payments()->exists()) {
                    // Soft delete to preserve data integrity
                    $project->delete();
                } else {
                    // Force delete if no associated documents
                    $project->forceDelete();
                }
            }
        }

        return true;
    }

    /**
     * Get project summary statistics.
     *
     * @param  string|null  $fromDate  Optional start date filter (Y-m-d)
     * @param  string|null  $toDate  Optional end date filter (Y-m-d)
     */
    public function getSummary(?string $fromDate = null, ?string $toDate = null): array
    {
        // Build invoice query with optional date filter
        $invoiceQuery = $this->invoices();
        if ($fromDate && $toDate) {
            $invoiceQuery->whereBetween('invoice_date', [$fromDate, $toDate]);
        }
        $totalInvoiced = $invoiceQuery->sum('base_total') ?? 0;
        $invoiceCount = $invoiceQuery->count();

        // Build expense query with optional date filter
        $expenseQuery = $this->expenses();
        if ($fromDate && $toDate) {
            $expenseQuery->whereBetween('expense_date', [$fromDate, $toDate]);
        }
        // Use COALESCE to handle NULL base_amount - fall back to amount * exchange_rate
        $totalExpenses = (clone $expenseQuery)->selectRaw('COALESCE(SUM(COALESCE(base_amount, amount * COALESCE(exchange_rate, 1))), 0) as total')->value('total') ?? 0;
        $expenseCount = $expenseQuery->count();

        // Build payment query with optional date filter
        $paymentQuery = $this->payments();
        if ($fromDate && $toDate) {
            $paymentQuery->whereBetween('payment_date', [$fromDate, $toDate]);
        }
        $totalPayments = $paymentQuery->sum('base_amount') ?? 0;
        $paymentCount = $paymentQuery->count();

        // Build bill query with optional date filter
        $billQuery = $this->bills();
        if ($fromDate && $toDate) {
            $billQuery->whereBetween('bill_date', [$fromDate, $toDate]);
        }
        // Bills use 'total' not 'base_total', and may need exchange rate conversion
        $totalBills = (clone $billQuery)->selectRaw('COALESCE(SUM(total * COALESCE(exchange_rate, 1)), 0) as total')->value('total') ?? 0;
        $billCount = $billQuery->count();

        // Net result: invoices received minus expenses and bills paid
        $netResult = $totalInvoiced - $totalExpenses - $totalBills;

        return [
            'total_invoiced' => $totalInvoiced,
            'total_expenses' => $totalExpenses,
            'total_payments' => $totalPayments,
            'total_bills' => $totalBills,
            'net_result' => $netResult,
            'invoice_count' => $invoiceCount,
            'expense_count' => $expenseCount,
            'payment_count' => $paymentCount,
            'bill_count' => $billCount,
            'budget_amount' => $this->budget_amount,
            'budget_remaining' => $this->budget_amount ? $this->budget_amount - $totalExpenses : null,
            'budget_used_percentage' => $this->budget_amount && $this->budget_amount > 0
                ? round(($totalExpenses / $this->budget_amount) * 100, 2)
                : null,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }
}

// CLAUDE-CHECKPOINT
