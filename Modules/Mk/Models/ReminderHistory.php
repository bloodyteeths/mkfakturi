<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderHistory extends Model
{
    protected $table = 'reminder_history';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'invoice_id',
        'customer_id',
        'template_id',
        'escalation_level',
        'sent_at',
        'sent_via',
        'opened_at',
        'paid_at',
        'amount_due',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'opened_at' => 'datetime',
            'paid_at' => 'datetime',
            'amount_due' => 'integer',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(ReminderTemplate::class, 'template_id');
    }

    // ---- Scopes ----

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('reminder_history.company_id', $companyId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('reminder_history.customer_id', $customerId);
    }

    public function scopeForInvoice($query, int $invoiceId)
    {
        return $query->where('reminder_history.invoice_id', $invoiceId);
    }
}

// CLAUDE-CHECKPOINT
