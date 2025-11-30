<?php

namespace App\Models;

use App\Traits\HasCustomFieldsTrait;
use App\Traits\RequiresApproval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Expense extends Model implements HasMedia
{
    use HasCustomFieldsTrait;
    use HasFactory;
    use InteractsWithMedia;
    use RequiresApproval;

    protected $dates = [
        'expense_date',
    ];

    protected $guarded = ['id'];

    protected $fillable = [
        'expense_date',
        'attachment_receipt',
        'amount',
        'notes',
        'expense_category_id',
        'company_id',
        'customer_id',
        'supplier_id',
        'invoice_number',
        'project_id',
        'payment_method_id',
        'currency_id',
        'creator_id',
        'ifrs_transaction_id',
    ];

    protected $appends = [
        'formattedExpenseDate',
        'formattedCreatedAt',
        'receipt',
        'receiptMeta',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'string',
            'exchange_rate' => 'float',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }

    /**
     * Get the project this expense belongs to.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the supplier this expense is from.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Check for potential duplicate expenses.
     * Duplicates are identified by: same company + same supplier + same invoice_number
     *
     * @param  int  $companyId
     * @param  int|null  $supplierId
     * @param  string|null  $invoiceNumber
     * @param  int|null  $excludeId  Expense ID to exclude (for updates)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function findPotentialDuplicates(
        int $companyId,
        ?int $supplierId,
        ?string $invoiceNumber,
        ?int $excludeId = null
    ) {
        // If no supplier or invoice number, no duplicate check needed
        if (empty($supplierId) || empty($invoiceNumber)) {
            return collect();
        }

        $query = self::where('company_id', $companyId)
            ->where('supplier_id', $supplierId)
            ->where('invoice_number', $invoiceNumber);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->with(['supplier', 'category'])->get();
    }

    /**
     * Check if this expense would be a duplicate.
     *
     * @return bool
     */
    public function hasDuplicates(): bool
    {
        return self::findPotentialDuplicates(
            $this->company_id,
            $this->supplier_id,
            $this->invoice_number,
            $this->id
        )->isNotEmpty();
    }

    public function getFormattedExpenseDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->expense_date)->translatedFormat($dateFormat);
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function getReceiptUrlAttribute($value)
    {
        $media = $this->getFirstMedia('receipts');

        if ($media) {
            return [
                'url' => $media->getFullUrl(),
                'type' => $media->type,
            ];
        }

        return null;
    }

    public function getReceiptAttribute($value)
    {
        $media = $this->getFirstMedia('receipts');

        if ($media) {
            return $media->getPath();
        }

        return null;
    }

    public function getReceiptMetaAttribute($value)
    {
        $media = $this->getFirstMedia('receipts');

        if ($media) {
            return $media;
        }

        return null;
    }

    public function scopeExpensesBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'expenses.expense_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    public function scopeWhereCategoryName($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->whereHas('category', function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%');
            });
        }
    }

    public function scopeWhereNotes($query, $search)
    {
        $query->where('notes', 'LIKE', '%'.$search.'%');
    }

    public function scopeWhereCategory($query, $categoryId)
    {
        return $query->where('expenses.expense_category_id', $categoryId);
    }

    public function scopeWhereUser($query, $customer_id)
    {
        return $query->where('expenses.customer_id', $customer_id);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('expense_category_id')) {
            $query->whereCategory($filters->get('expense_category_id'));
        }

        if ($filters->get('customer_id')) {
            $query->whereUser($filters->get('customer_id'));
        }

        if ($filters->get('expense_id')) {
            $query->whereExpense($filters->get('expense_id'));
        }

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->expensesBetween($start, $end);
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'expense_date';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }
    }

    public function scopeWhereExpense($query, $expense_id)
    {
        $query->orWhere('id', $expense_id);
    }

    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->whereHas('category', function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%');
            })
                ->orWhere('notes', 'LIKE', '%'.$term.'%');
        }
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('expenses.company_id', request()->header('company'));
    }

    public function scopeWhereCompanyId($query, $company)
    {
        $query->where('expenses.company_id', $company);
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public function scopeExpensesAttributes($query)
    {
        $query->select(
            DB::raw('
                count(*) as expenses_count,
                sum(base_amount) as total_amount,
                expense_category_id')
        )
            ->groupBy('expense_category_id');
    }

    public static function createExpense($request)
    {
        $expense = self::create($request->getExpensePayload());

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $expense['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($expense);
        }

        if ($request->hasFile('attachment_receipt')) {
            $expense->addMediaFromRequest('attachment_receipt')->toMediaCollection('receipts');
        }

        if ($request->customFields) {
            $expense->addCustomFields(json_decode($request->customFields));
        }

        return $expense;
    }

    public function updateExpense($request)
    {
        $data = $request->getExpensePayload();

        $this->update($data);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($this);
        }

        if (isset($request->is_attachment_receipt_removed) && (bool) $request->is_attachment_receipt_removed) {
            $this->clearMediaCollection('receipts');
        }
        if ($request->hasFile('attachment_receipt')) {
            $this->clearMediaCollection('receipts');
            $this->addMediaFromRequest('attachment_receipt')->toMediaCollection('receipts');
        }

        if ($request->customFields) {
            $this->updateCustomFields(json_decode($request->customFields));
        }

        return true;
    }
}
