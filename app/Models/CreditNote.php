<?php

namespace App\Models;

use App;
use App\Facades\PDF;
use App\Mail\SendCreditNoteMail;
use App\Services\SerialNumberFormatter;
use App\Space\PdfTemplateUtils;
use App\Traits\CacheableTrait;
use App\Traits\GeneratesPdfTrait;
use App\Traits\HasCustomFieldsTrait;
use App\Traits\RequiresApproval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Vinkla\Hashids\Facades\Hashids;

/**
 * CreditNote Model
 *
 * Represents a credit note issued to reduce or reverse an invoice.
 * Once posted to IFRS (ifrs_transaction_id set), the credit note becomes immutable.
 *
 * @package App\Models
 */
class CreditNote extends Model implements HasMedia
{
    use CacheableTrait;
    use GeneratesPdfTrait;
    use HasCustomFieldsTrait;
    use HasFactory;
    use InteractsWithMedia;
    use RequiresApproval;

    /**
     * Status Constants
     */
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_SENT = 'SENT';
    public const STATUS_VIEWED = 'VIEWED';
    public const STATUS_COMPLETED = 'COMPLETED';

    /**
     * Credit Note Number Prefix
     */
    public const NUMBER_PREFIX = 'CN';

    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
        'credit_note_date',
    ];

    protected $guarded = [
        'id',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'formattedCreditNoteDate',
        'creditNotePdfUrl',
    ];

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'customer:id,name,email',
        'currency:id,name,code,symbol',
        'company:id,name'
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
        ];
    }

    /**
     * Relationships
     */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function emailLogs(): MorphMany
    {
        return $this->morphMany('App\Models\EmailLog', 'mailable');
    }

    /**
     * Scopes
     */

    public function scopeWhereCompany($query)
    {
        $query->where('credit_notes.company_id', request()->header('company'));
    }

    public function scopeWhereCustomer($query, $customerId)
    {
        $query->where('credit_notes.customer_id', $customerId);
    }

    public function scopeWhereStatus($query, $status)
    {
        return $query->where('credit_notes.status', $status);
    }

    public function scopeWhereCreditNoteNumber($query, $creditNoteNumber)
    {
        return $query->where('credit_notes.credit_note_number', 'LIKE', '%'.$creditNoteNumber.'%');
    }

    public function scopeCreditNotesBetween($query, $start, $end)
    {
        return $query->whereBetween(
            'credit_notes.credit_note_date',
            [$start->format('Y-m-d'), $end->format('Y-m-d')]
        );
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

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters)->filter()->all();

        return $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereSearch($search);
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->whereStatus($status);
        })->when($filters['credit_note_number'] ?? null, function ($query, $creditNoteNumber) {
            $query->whereCreditNoteNumber($creditNoteNumber);
        })->when(($filters['from_date'] ?? null) && ($filters['to_date'] ?? null), function ($query) use ($filters) {
            $start = Carbon::parse($filters['from_date']);
            $end = Carbon::parse($filters['to_date']);
            $query->creditNotesBetween($start, $end);
        })->when($filters['customer_id'] ?? null, function ($query, $customerId) {
            $query->where('customer_id', $customerId);
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

    /**
     * Attribute Accessors
     */

    public function getCreditNotePdfUrlAttribute()
    {
        return url('/credit-notes/pdf/'.$this->unique_hash);
    }

    public function getAllowEditAttribute()
    {
        // Once posted to IFRS, credit note becomes immutable
        if ($this->ifrs_transaction_id) {
            return false;
        }

        $retrospective_edit = CompanySetting::getSetting('retrospective_edits', $this->company_id);

        $allowed = true;

        if ($retrospective_edit == 'disable_on_invoice_sent' && $this->status !== self::STATUS_DRAFT) {
            $allowed = false;
        }

        return $allowed;
    }

    public function getFormattedNotesAttribute($value)
    {
        return $this->getNotes();
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->created_at)->format($dateFormat);
    }

    public function getFormattedCreditNoteDateAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

        return Carbon::parse($this->credit_note_date)->translatedFormat($dateFormat);
    }

    /**
     * Create Credit Note with number series
     *
     * @param \Illuminate\Http\Request $request
     * @return CreditNote
     */
    public static function createCreditNote($request)
    {
        $data = $request->getCreditNotePayload();

        if ($request->has('creditNoteSend')) {
            $data['status'] = CreditNote::STATUS_SENT;
        }

        $creditNote = CreditNote::create($data);

        $serial = (new SerialNumberFormatter)
            ->setModel($creditNote)
            ->setCompany($creditNote->company_id)
            ->setCustomer($creditNote->customer_id)
            ->setNextNumbers();

        // Generate credit note number: CN-{YEAR}-{SEQUENCE}
        $year = Carbon::parse($creditNote->credit_note_date)->format('Y');
        $creditNote->credit_note_number = self::NUMBER_PREFIX.'-'.$year.'-'.str_pad($serial->nextSequenceNumber, 5, '0', STR_PAD_LEFT);
        $creditNote->sequence_number = $serial->nextSequenceNumber;
        $creditNote->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $creditNote->unique_hash = Hashids::connection(CreditNote::class)->encode($creditNote->id);
        $creditNote->save();

        self::createItems($creditNote, $request->items);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($creditNote);
        }

        if ($request->has('taxes') && (!empty($request->taxes))) {
            self::createTaxes($creditNote, $request->taxes);
        }

        if ($request->customFields) {
            $creditNote->addCustomFields($request->customFields);
        }

        $creditNote = CreditNote::with([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes',
        ])
            ->find($creditNote->id);

        return $creditNote;
    }

    /**
     * Update Credit Note
     *
     * @param \Illuminate\Http\Request $request
     * @return CreditNote|string
     */
    public function updateCreditNote($request)
    {
        // Prevent updates if already posted to IFRS
        if ($this->ifrs_transaction_id) {
            return 'credit_note_cannot_be_changed_after_posting';
        }

        $serial = (new SerialNumberFormatter)
            ->setModel($this)
            ->setCompany($this->company_id)
            ->setCustomer($request->customer_id)
            ->setModelObject($this->id)
            ->setNextNumbers();

        $data = $request->getCreditNotePayload();

        if ($this->customer_id !== $request->customer_id) {
            return 'customer_cannot_be_changed_after_creation';
        }

        $data['customer_sequence_number'] = $serial->nextCustomerSequenceNumber;

        $this->update($data);

        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));

        if ((string) $data['currency_id'] !== $company_currency) {
            ExchangeRateLog::addExchangeRateLog($this);
        }

        $this->items->map(function ($item) {
            $fields = $item->fields()->get();

            $fields->map(function ($field) {
                $field->delete();
            });
        });

        $this->items()->delete();
        $this->taxes()->delete();

        self::createItems($this, $request->items);

        if ($request->has('taxes') && (!empty($request->taxes))) {
            self::createTaxes($this, $request->taxes);
        }

        if ($request->customFields) {
            $this->updateCustomFields($request->customFields);
        }

        $creditNote = CreditNote::with([
            'items',
            'items.fields',
            'items.fields.customField',
            'customer',
            'taxes',
        ])
            ->find($this->id);

        return $creditNote;
    }

    /**
     * Create Items for Credit Note
     *
     * @param CreditNote $creditNote
     * @param array $creditNoteItems
     * @return void
     */
    public static function createItems($creditNote, $creditNoteItems)
    {
        $exchange_rate = $creditNote->exchange_rate;

        foreach ($creditNoteItems as $creditNoteItem) {
            $creditNoteItem['company_id'] = $creditNote->company_id;
            $creditNoteItem['exchange_rate'] = $exchange_rate;
            $creditNoteItem['base_price'] = $creditNoteItem['price'] * $exchange_rate;
            $creditNoteItem['base_discount_val'] = $creditNoteItem['discount_val'] * $exchange_rate;
            $creditNoteItem['base_tax'] = $creditNoteItem['tax'] * $exchange_rate;
            $creditNoteItem['base_total'] = $creditNoteItem['total'] * $exchange_rate;

            $item = $creditNote->items()->create($creditNoteItem);

            if (array_key_exists('taxes', $creditNoteItem) && $creditNoteItem['taxes']) {
                foreach ($creditNoteItem['taxes'] as $tax) {
                    $tax['company_id'] = $creditNote->company_id;
                    $tax['exchange_rate'] = $creditNote->exchange_rate;
                    $tax['base_amount'] = $tax['amount'] * $exchange_rate;
                    $tax['currency_id'] = $creditNote->currency_id;

                    if (gettype($tax['amount']) !== 'NULL') {
                        $item->taxes()->create($tax);
                    }
                }
            }

            if (array_key_exists('custom_fields', $creditNoteItem) && $creditNoteItem['custom_fields']) {
                $item->addCustomFields($creditNoteItem['custom_fields']);
            }
        }
    }

    /**
     * Create Taxes for Credit Note
     *
     * @param CreditNote $creditNote
     * @param array $taxes
     * @return void
     */
    public static function createTaxes($creditNote, $taxes)
    {
        $exchange_rate = $creditNote->exchange_rate;

        foreach ($taxes as $tax) {
            $tax['company_id'] = $creditNote->company_id;
            $tax['exchange_rate'] = $creditNote->exchange_rate;
            $tax['base_amount'] = $tax['amount'] * $exchange_rate;
            $tax['currency_id'] = $creditNote->currency_id;

            if (gettype($tax['amount']) !== 'NULL') {
                $creditNote->taxes()->create($tax);
            }
        }
    }

    /**
     * Send Credit Note
     *
     * @param array $data
     * @return array
     */
    public function send($data)
    {
        $data = $this->sendCreditNoteData($data);

        \Mail::to($data['to'])->send(new SendCreditNoteMail($data));

        if ($this->status == CreditNote::STATUS_DRAFT) {
            $this->status = CreditNote::STATUS_SENT;
            $this->sent = true;
            $this->save();
        }

        return [
            'success' => true,
            'type' => 'send',
        ];
    }

    /**
     * Send Credit Note Data
     *
     * @param array $data
     * @return array
     */
    public function sendCreditNoteData($data)
    {
        $data['credit_note'] = $this->toArray();
        $data['customer'] = $this->customer->toArray();
        $data['company'] = Company::find($this->company_id);
        $data['subject'] = $this->getEmailString($data['subject']);
        $data['body'] = $this->getEmailString($data['body']);
        $data['attach']['data'] = ($this->getEmailAttachmentSetting()) ? $this->getPDFData() : null;

        return $data;
    }

    /**
     * Mark Credit Note as Viewed
     *
     * @return void
     */
    public function markAsViewed()
    {
        if ($this->status == self::STATUS_SENT) {
            $this->status = self::STATUS_VIEWED;
            $this->viewed = true;
            $this->save();
        }
    }

    /**
     * Mark Credit Note as Completed
     * Triggers IFRS posting via CreditNoteObserver
     *
     * @return void
     */
    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();
    }

    /**
     * Post to IFRS
     * This is called by CreditNoteObserver when status changes to COMPLETED
     *
     * @return void
     */
    public function post()
    {
        // Observer handles this via IfrsAdapter
        $this->markAsCompleted();
    }

    /**
     * Generate unique hash for public URL
     *
     * @return string
     */
    public function generateUniqueHash(): string
    {
        return Hashids::connection(CreditNote::class)->encode($this->id);
    }

    /**
     * Get PDF Data
     *
     * @return mixed
     */
    public function getPDFData()
    {
        $taxes = collect();

        if ($this->tax_per_item === 'YES') {
            foreach ($this->items as $item) {
                foreach ($item->taxes as $tax) {
                    $found = $taxes->filter(function ($item) use ($tax) {
                        return $item->tax_type_id == $tax->tax_type_id;
                    })->first();

                    if ($found) {
                        $found->amount += $tax->amount;
                    } else {
                        $taxes->push($tax);
                    }
                }
            }
        }

        $creditNoteTemplate = self::find($this->id)->template_name ?? 'credit-note-1';

        $company = Company::find($this->company_id);
        $locale = CompanySetting::getSetting('language', $company->id);
        $customFields = CustomField::where('model_type', 'CreditNoteItem')->get();

        App::setLocale($locale);

        // Verify logo file exists before using it
        $logo = $company->logo_path;

        if ($logo && !filter_var($logo, FILTER_VALIDATE_URL)) {
            if (!file_exists($logo)) {
                $logo = null;
            }
        }

        if (!$logo) {
            $defaultLogo = base_path('logo/facturino_logo.png');
            $logo = file_exists($defaultLogo) ? $defaultLogo : null;
        }

        view()->share([
            'credit_note' => $this,
            'customFields' => $customFields,
            'company_address' => $this->getCompanyAddress(),
            'shipping_address' => $this->getCustomerShippingAddress(),
            'billing_address' => $this->getCustomerBillingAddress(),
            'notes' => $this->getNotes(),
            'logo' => $logo ?? null,
            'taxes' => $taxes,
        ]);

        $template = PdfTemplateUtils::findFormattedTemplate('credit_note', $creditNoteTemplate, '');
        $templatePath = $template['custom'] ? sprintf('pdf_templates::credit_note.%s', $creditNoteTemplate) : sprintf('app.pdf.credit_note.%s', $creditNoteTemplate);

        if (request()->has('preview')) {
            return view($templatePath);
        }

        return PDF::loadView($templatePath);
    }

    /**
     * Get Email Attachment Setting
     *
     * @return bool
     */
    public function getEmailAttachmentSetting()
    {
        $creditNoteAsAttachment = CompanySetting::getSetting('credit_note_email_attachment', $this->company_id);

        if ($creditNoteAsAttachment == 'NO') {
            return false;
        }

        return true;
    }

    /**
     * Get Company Address
     *
     * @return string|bool
     */
    public function getCompanyAddress()
    {
        if ($this->company && (!$this->company->address()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('credit_note_company_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    /**
     * Get Customer Shipping Address
     *
     * @return string|bool
     */
    public function getCustomerShippingAddress()
    {
        if ($this->customer && (!$this->customer->shippingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('credit_note_shipping_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    /**
     * Get Customer Billing Address
     *
     * @return string|bool
     */
    public function getCustomerBillingAddress()
    {
        if ($this->customer && (!$this->customer->billingAddress()->exists())) {
            return false;
        }

        $format = CompanySetting::getSetting('credit_note_billing_address_format', $this->company_id);

        return $this->getFormattedString($format);
    }

    /**
     * Get Notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->getFormattedString($this->notes);
    }

    /**
     * Get Email String
     *
     * @param string $body
     * @return string
     */
    public function getEmailString($body)
    {
        $values = array_merge($this->getFieldsArray(), $this->getExtraFields());

        $body = strtr($body, $values);

        return preg_replace('/{(.*?)}/', '', $body);
    }

    /**
     * Get Extra Fields for Email Templates
     *
     * @return array
     */
    public function getExtraFields()
    {
        return [
            '{CREDIT_NOTE_DATE}' => $this->formattedCreditNoteDate,
            '{CREDIT_NOTE_NUMBER}' => $this->credit_note_number,
            '{CREDIT_NOTE_REF_NUMBER}' => $this->reference_number,
            '{INVOICE_NUMBER}' => $this->invoice ? $this->invoice->invoice_number : '',
        ];
    }

    /**
     * Delete Credit Notes
     *
     * @param array $ids
     * @return bool
     */
    public static function deleteCreditNotes($ids)
    {
        foreach ($ids as $id) {
            $creditNote = self::find($id);

            // Prevent deletion if posted to IFRS
            if ($creditNote->ifrs_transaction_id) {
                continue;
            }

            $creditNote->delete();
        }

        return true;
    }
}

// CLAUDE-CHECKPOINT
