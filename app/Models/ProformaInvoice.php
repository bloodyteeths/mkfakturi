<?php

namespace App\Models;

use App\Services\SerialNumberFormatter;
use App\Traits\GeneratesPdfTrait;
use App\Traits\HasCustomFieldsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Vinkla\Hashids\Facades\Hashids;

class ProformaInvoice extends Model
{
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;
    use HasFactory;
    use SoftDeletes;

    // Status constants
    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_SENT = 'SENT';

    public const STATUS_VIEWED = 'VIEWED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_CONVERTED = 'CONVERTED';

    public const STATUS_REJECTED = 'REJECTED';

    protected $fillable = [
        'proforma_invoice_date',
        'expiry_date',
        'proforma_invoice_number',
        'proforma_invoice_prefix',
        'reference_number',
        'customer_po_number',
        'status',
        'notes',
        'terms',
        'private_notes',
        'discount_type',
        'discount',
        'discount_val',
        'sub_total',
        'total',
        'tax',
        'unique_hash',
        'template_name',
        'currency_id',
        'exchange_rate',
        'base_discount_val',
        'base_sub_total',
        'base_total',
        'base_tax',
        'customer_id',
        'company_id',
        'created_by',
        'converted_invoice_id',
        'sequence_number',
        'customer_sequence_number',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'proforma_invoice_date',
        'expiry_date',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'formattedProformaInvoiceDate',
        'formattedExpiryDate',
        'isExpired',
        'allow_edit',
    ];

    protected $with = [
        'customer:id,name,email',
        'currency:id,name,code,symbol',
        'company:id,name',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'integer',
            'tax' => 'integer',
            'sub_total' => 'integer',
            'discount' => 'float',
            'discount_val' => 'integer',
            'exchange_rate' => 'float',
            'proforma_invoice_date' => 'date',
            'expiry_date' => 'date',
        ];
    }

    // Relationships

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProformaInvoiceItem::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function fields(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'custom_field_valuable');
    }

    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_invoice_id');
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany(EmailLog::class, 'mailable');
    }

    // Accessors

    public function getAllowEditAttribute(): bool
    {
        return $this->status !== self::STATUS_CONVERTED;
    }

    public function getIsExpiredAttribute(): bool
    {
        if (! $this->expiry_date) {
            return false;
        }

        return $this->expiry_date->isPast();
    }

    public function getFormattedCreatedAtAttribute(): string
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    public function getFormattedProformaInvoiceDateAttribute(): string
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->proforma_invoice_date)->translatedFormat($dateFormat);
    }

    public function getFormattedExpiryDateAttribute(): string
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->expiry_date)->translatedFormat($dateFormat);
    }

    // Scopes

    public function scopeWhereStatus($query, string $status)
    {
        return $query->where('proforma_invoices.status', $status);
    }

    public function scopeWhereProformaInvoiceNumber($query, string $proformaInvoiceNumber)
    {
        return $query->where('proforma_invoices.proforma_invoice_number', 'LIKE', '%'.$proformaInvoiceNumber.'%');
    }

    public function scopeWhereExpiryDate($query, $start, $end)
    {
        return $query->whereBetween(
            'proforma_invoices.expiry_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
    }

    public function scopeWhereCustomer($query, int $customerId)
    {
        return $query->where('proforma_invoices.customer_id', $customerId);
    }

    public function scopeExpired($query)
    {
        return $query->where('proforma_invoices.expiry_date', '<', now());
    }

    public function scopeConvertible($query)
    {
        return $query->whereIn('proforma_invoices.status', [self::STATUS_SENT, self::STATUS_VIEWED])
            ->where('proforma_invoices.expiry_date', '>=', now());
    }

    public function scopeWhereCompany($query)
    {
        return $query->where('proforma_invoices.company_id', request()->header('company'));
    }

    public function scopeWhereCompanyId($query, $company)
    {
        return $query->where('proforma_invoices.company_id', $company);
    }

    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->whereHas('customer', function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('contact_name', 'LIKE', '%'.$term.'%')
                    ->orWhere('company_name', 'LIKE', '%'.$term.'%');
            });
        }
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters)->filter()->all();

        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereSearch($search);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->whereStatus($status);
        })->when($filters['proforma_invoice_number'] ?? null, function ($query, $proformaInvoiceNumber) {
            $query->whereProformaInvoiceNumber($proformaInvoiceNumber);
        })->when(($filters['from_date'] ?? null) && ($filters['to_date'] ?? null), function ($query) use ($filters) {
            $start = Carbon::parse($filters['from_date']);
            $end = Carbon::parse($filters['to_date']);
            $query->whereExpiryDate($start, $end);
        })->when($filters['customer_id'] ?? null, function ($query, $customerId) {
            $query->whereCustomer($customerId);
        })->when($filters['orderByField'] ?? null, function ($query, $orderByField) use ($filters) {
            $orderBy = $filters['orderBy'] ?? 'desc';
            $query->orderBy($orderByField, $orderBy);
        }, function ($query) {
            $query->orderBy('sequence_number', 'desc');
        });
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    // Methods

    /**
     * Mark proforma invoice as sent
     */
    public function markAsSent(): void
    {
        $this->status = self::STATUS_SENT;
        $this->save();
    }

    /**
     * Mark proforma invoice as viewed
     */
    public function markAsViewed(): void
    {
        if ($this->status === self::STATUS_DRAFT || $this->status === self::STATUS_SENT) {
            $this->status = self::STATUS_VIEWED;
            $this->save();
        }
    }

    /**
     * Mark proforma invoice as expired
     */
    public function markAsExpired(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->save();
    }

    /**
     * Mark proforma invoice as rejected
     */
    public function markAsRejected(): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->save();
    }

    /**
     * Convert proforma invoice to regular invoice
     */
    public function convertToInvoice(): Invoice
    {
        // Prepare invoice data from proforma
        $invoiceData = [
            'invoice_date' => now(),
            'due_date' => now()->addDays(30), // Default 30 days, can be customized
            'customer_id' => $this->customer_id,
            'company_id' => $this->company_id,
            'currency_id' => $this->currency_id,
            'exchange_rate' => $this->exchange_rate,
            'status' => Invoice::STATUS_DRAFT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'tax_per_item' => 'YES', // Default
            'discount_per_item' => 'NO', // Default
            'notes' => $this->notes,
            'discount_type' => $this->discount_type,
            'discount' => $this->discount,
            'discount_val' => $this->discount_val,
            'sub_total' => $this->sub_total,
            'total' => $this->total,
            'tax' => $this->tax,
            'due_amount' => $this->total,
            'base_discount_val' => $this->base_discount_val,
            'base_sub_total' => $this->base_sub_total,
            'base_total' => $this->base_total,
            'base_tax' => $this->base_tax,
            'base_due_amount' => $this->base_total,
            'template_name' => $this->template_name,
            'reference_number' => $this->reference_number,
            'creator_id' => auth()->id(),
        ];

        // Create invoice
        $invoice = Invoice::create($invoiceData);

        // Generate invoice number
        $serial = (new SerialNumberFormatter)
            ->setModel($invoice)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $invoice->sequence_number = $serial->nextSequenceNumber;
        $invoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
        $invoice->save();

        // Copy items
        foreach ($this->items as $proformaItem) {
            $invoiceItem = $invoice->items()->create([
                'name' => $proformaItem->name,
                'description' => $proformaItem->description,
                'discount_type' => $proformaItem->discount_type,
                'price' => $proformaItem->price,
                'quantity' => $proformaItem->quantity,
                'discount' => $proformaItem->discount,
                'discount_val' => $proformaItem->discount_val,
                'tax' => $proformaItem->tax,
                'total' => $proformaItem->total,
                'unit_name' => $proformaItem->unit_name,
                'exchange_rate' => $proformaItem->exchange_rate,
                'base_price' => $proformaItem->base_price,
                'base_discount_val' => $proformaItem->base_discount_val,
                'base_tax' => $proformaItem->base_tax,
                'base_total' => $proformaItem->base_total,
                'item_id' => $proformaItem->item_id,
                'company_id' => $proformaItem->company_id,
            ]);

            // Copy item taxes
            foreach ($proformaItem->taxes as $tax) {
                $invoiceItem->taxes()->create([
                    'tax_type_id' => $tax->tax_type_id,
                    'name' => $tax->name,
                    'percent' => $tax->percent,
                    'amount' => $tax->amount,
                    'compound_tax' => $tax->compound_tax,
                    'company_id' => $tax->company_id,
                    'exchange_rate' => $tax->exchange_rate,
                    'base_amount' => $tax->base_amount,
                    'currency_id' => $tax->currency_id,
                ]);
            }

            // Copy custom fields if any
            if ($proformaItem->fields()->exists()) {
                foreach ($proformaItem->fields as $field) {
                    $invoiceItem->fields()->create([
                        'custom_field_id' => $field->custom_field_id,
                        'value' => $field->value,
                        'company_id' => $field->company_id,
                    ]);
                }
            }
        }

        // Copy proforma-level taxes
        foreach ($this->taxes as $tax) {
            $invoice->taxes()->create([
                'tax_type_id' => $tax->tax_type_id,
                'name' => $tax->name,
                'percent' => $tax->percent,
                'amount' => $tax->amount,
                'compound_tax' => $tax->compound_tax,
                'company_id' => $tax->company_id,
                'exchange_rate' => $tax->exchange_rate,
                'base_amount' => $tax->base_amount,
                'currency_id' => $tax->currency_id,
            ]);
        }

        // Copy custom fields if any
        if ($this->fields()->exists()) {
            foreach ($this->fields as $field) {
                $invoice->fields()->create([
                    'custom_field_id' => $field->custom_field_id,
                    'value' => $field->value,
                    'company_id' => $field->company_id,
                ]);
            }
        }

        // Mark proforma as converted
        $this->converted_invoice_id = $invoice->id;
        $this->status = self::STATUS_CONVERTED;
        $this->save();

        return $invoice;
    }

    /**
     * Create proforma invoice from request
     *
     * @param  object  $request
     */
    public static function createProformaInvoice($request): ProformaInvoice
    {
        $data = $request->getProformaInvoicePayload();

        if ($request->has('proformaInvoiceSend')) {
            $data['status'] = self::STATUS_SENT;
        }

        $proformaInvoice = self::create($data);

        // Generate proforma invoice number
        $serial = (new SerialNumberFormatter)
            ->setModel($proformaInvoice)
            ->setCompany($proformaInvoice->company_id)
            ->setCustomer($proformaInvoice->customer_id)
            ->setNextNumbers();

        $proformaInvoice->sequence_number = $serial->nextSequenceNumber;
        $proformaInvoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $proformaInvoice->unique_hash = Hashids::connection(self::class)->encode($proformaInvoice->id);
        $proformaInvoice->save();

        self::createItems($proformaInvoice, $request->items);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($proformaInvoice);
        }

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($proformaInvoice, $request->taxes);
        }

        if ($request->customFields) {
            $proformaInvoice->addCustomFields($request->customFields);
        }

        return $proformaInvoice->load([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes',
        ]);
    }

    /**
     * Update proforma invoice from request
     *
     * @param  object  $request
     */
    public function updateProformaInvoice($request): ProformaInvoice
    {
        $serial = (new SerialNumberFormatter)
            ->setModel($this)
            ->setCompany($this->company_id)
            ->setCustomer($request->customer_id)
            ->setModelObject($this->id)
            ->setNextNumbers();

        $data = $request->getProformaInvoicePayload();
        $data['customer_sequence_number'] = $serial->nextCustomerSequenceNumber;

        $this->update($data);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($this);
        }

        // Delete old items and custom fields
        $this->items->map(function ($item) {
            $fields = $item->fields()->get();
            $fields->map(function ($field) {
                $field->delete();
            });
        });

        $this->items()->delete();
        $this->taxes()->delete();

        self::createItems($this, $request->items);

        if ($request->has('taxes') && (! empty($request->taxes))) {
            self::createTaxes($this, $request->taxes);
        }

        if ($request->customFields) {
            $this->updateCustomFields($request->customFields);
        }

        return $this->load([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes',
        ]);
    }

    /**
     * Create items for proforma invoice
     *
     * @param  ProformaInvoice  $proformaInvoice
     * @param  array  $items
     */
    public static function createItems($proformaInvoice, $items): void
    {
        $exchange_rate = $proformaInvoice->exchange_rate;

        foreach ($items as $item) {
            $item['company_id'] = $proformaInvoice->company_id;
            $item['exchange_rate'] = $exchange_rate;
            $item['base_price'] = $item['price'] * $exchange_rate;
            $item['base_discount_val'] = $item['discount_val'] * $exchange_rate;
            $item['base_tax'] = $item['tax'] * $exchange_rate;
            $item['base_total'] = $item['total'] * $exchange_rate;

            $proformaItem = $proformaInvoice->items()->create($item);

            if (array_key_exists('taxes', $item) && $item['taxes']) {
                foreach ($item['taxes'] as $tax) {
                    $tax['company_id'] = $proformaInvoice->company_id;
                    $tax['exchange_rate'] = $proformaInvoice->exchange_rate;
                    $tax['base_amount'] = $tax['amount'] * $exchange_rate;
                    $tax['currency_id'] = $proformaInvoice->currency_id;

                    if (gettype($tax['amount']) !== 'NULL') {
                        $proformaItem->taxes()->create($tax);
                    }
                }
            }

            if (array_key_exists('custom_fields', $item) && $item['custom_fields']) {
                $proformaItem->addCustomFields($item['custom_fields']);
            }
        }
    }

    /**
     * Create taxes for proforma invoice
     *
     * @param  ProformaInvoice  $proformaInvoice
     * @param  array  $taxes
     */
    public static function createTaxes($proformaInvoice, $taxes): void
    {
        $exchange_rate = $proformaInvoice->exchange_rate;

        foreach ($taxes as $tax) {
            $tax['company_id'] = $proformaInvoice->company_id;
            $tax['exchange_rate'] = $proformaInvoice->exchange_rate;
            $tax['base_amount'] = $tax['amount'] * $exchange_rate;
            $tax['currency_id'] = $proformaInvoice->currency_id;

            if (gettype($tax['amount']) !== 'NULL') {
                $proformaInvoice->taxes()->create($tax);
            }
        }
    }

    /**
     * Delete multiple proforma invoices
     */
    public static function deleteProformaInvoices(array $ids): bool
    {
        foreach ($ids as $id) {
            $proformaInvoice = self::find($id);
            $proformaInvoice->delete();
        }

        return true;
    }
}

// CLAUDE-CHECKPOINT
