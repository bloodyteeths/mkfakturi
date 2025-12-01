<?php

namespace App\Models;

use App\Notifications\CustomerMailResetPasswordNotification;
use App\Traits\CacheableTrait;
use App\Traits\HasCustomFieldsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Customer extends Authenticatable implements HasMedia
{
    use CacheableTrait;
    use HasApiTokens;
    use HasCustomFieldsTrait;
    use HasFactory;
    use HasRolesAndAbilities;
    use InteractsWithMedia;
    use Notifiable;

    protected $guarded = [
        'id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $with = [
        'currency',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'avatar',
    ];

    protected function casts(): array
    {
        return [
            'enable_portal' => 'boolean',
        ];
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        return $this->cacheComputed('formatted_created_at', function () {
            $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);

            return Carbon::parse($this->created_at)->translatedFormat($dateFormat ?? 'Y-m-d');
        });
    }

    public function setPasswordAttribute($value)
    {
        if ($value != null) {
            $this->attributes['password'] = bcrypt($value);
        }
    }

    public function estimates(): HasMany
    {
        return $this->hasMany(Estimate::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function recurringInvoices(): HasMany
    {
        return $this->hasMany(RecurringInvoice::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'creator_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function billingAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('type', Address::BILLING_TYPE);
    }

    public function shippingAddress(): HasOne
    {
        return $this->hasOne(Address::class)->where('type', Address::SHIPPING_TYPE);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomerMailResetPasswordNotification($token));
    }

    public function getAvatarAttribute()
    {
        $avatar = $this->getMedia('customer_avatar')->first();

        if ($avatar) {
            return asset($avatar->getUrl());
        }

        return 0;
    }

    public static function deleteCustomers($ids)
    {
        foreach ($ids as $id) {
            try {
                // Use whereKey()->first() instead of find() to avoid collection issues with global scopes
                $customer = self::whereKey($id)->first();

                if (! $customer) {
                    \Log::warning('Customer not found for deletion', ['customer_id' => $id]);

                    continue;
                }

                // Verify we have a model instance, not a collection
                if ($customer instanceof \Illuminate\Database\Eloquent\Collection) {
                    \Log::error('Got collection instead of model for customer deletion', ['customer_id' => $id]);

                    continue;
                }

                // Load all relationships at once
                $customer->load([
                    'estimates',
                    'invoices.transactions',
                    'payments',
                    'addresses',
                    'expenses',
                    'recurringInvoices.items',
                ]);

                // Delete estimates
                foreach ($customer->estimates as $estimate) {
                    $estimate->delete();
                }

                // Delete invoices and their transactions
                foreach ($customer->invoices as $invoice) {
                    foreach ($invoice->transactions as $transaction) {
                        $transaction->delete();
                    }
                    $invoice->delete();
                }

                // Delete payments
                foreach ($customer->payments as $payment) {
                    $payment->delete();
                }

                // Delete addresses
                foreach ($customer->addresses as $address) {
                    $address->delete();
                }

                // Delete expenses
                foreach ($customer->expenses as $expense) {
                    $expense->delete();
                }

                // Delete recurring invoices and their items
                foreach ($customer->recurringInvoices as $recurringInvoice) {
                    foreach ($recurringInvoice->items as $item) {
                        $item->delete();
                    }
                    $recurringInvoice->delete();
                }

                $customer->delete();
                \Log::info('Successfully deleted customer', ['customer_id' => $id]);
            } catch (\Exception $e) {
                \Log::error('Error deleting customer', [
                    'customer_id' => $id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return true;
    }

    public static function createCustomer($request)
    {
        $customer = Customer::create($request->getCustomerPayload());

        if ($request->shipping) {
            if ($request->hasAddress($request->shipping)) {
                $customer->addresses()->create($request->getShippingAddress());
            }
        }

        if ($request->billing) {
            if ($request->hasAddress($request->billing)) {
                $customer->addresses()->create($request->getBillingAddress());
            }
        }

        $customFields = $request->customFields;

        if ($customFields) {
            $customer->addCustomFields($customFields);
        }

        $customer = Customer::with('billingAddress', 'shippingAddress', 'fields')->find($customer->id);

        return $customer;
    }

    public static function updateCustomer($request, $customer)
    {
        $condition = $customer->estimates()->exists() || $customer->invoices()->exists() || $customer->payments()->exists() || $customer->recurringInvoices()->exists();

        if (($customer->currency_id !== $request->currency_id) && $condition) {
            return 'you_cannot_edit_currency';
        }

        $customer->update($request->getCustomerPayload());

        $customer->addresses()->delete();

        if ($request->shipping) {
            if ($request->hasAddress($request->shipping)) {
                $customer->addresses()->create($request->getShippingAddress());
            }
        }

        if ($request->billing) {
            if ($request->hasAddress($request->billing)) {
                $customer->addresses()->create($request->getBillingAddress());
            }
        }

        $customFields = $request->customFields;

        if ($customFields) {
            $customer->updateCustomFields($customFields);
        }

        $customer = Customer::with('billingAddress', 'shippingAddress', 'fields')->find($customer->id);

        return $customer;
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public function scopeWhereCompany($query)
    {
        return $query->where('customers.company_id', request()->header('company'));
    }

    public function scopeWhereContactName($query, $contactName)
    {
        return $query->where('contact_name', 'LIKE', '%'.$contactName.'%');
    }

    public function scopeWhereDisplayName($query, $displayName)
    {
        return $query->where('name', 'LIKE', '%'.$displayName.'%');
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('email', 'LIKE', '%'.$term.'%')
                    ->orWhere('phone', 'LIKE', '%'.$term.'%');
            });
        }
    }

    public function scopeWherePhone($query, $phone)
    {
        return $query->where('phone', 'LIKE', '%'.$phone.'%');
    }

    public function scopeWhereCustomer($query, $customer_id)
    {
        $query->orWhere('customers.id', $customer_id);
    }

    public function scopeApplyInvoiceFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('from_date') && $filters->get('to_date')) {
            $start = Carbon::createFromFormat('Y-m-d', $filters->get('from_date'));
            $end = Carbon::createFromFormat('Y-m-d', $filters->get('to_date'));
            $query->invoicesBetween($start, $end);
        }
    }

    public function scopeInvoicesBetween($query, $start, $end)
    {
        $query->whereHas('invoices', function ($query) use ($start, $end) {
            $query->whereBetween(
                'invoice_date',
                [$start->format('Y-m-d'), $end->format('Y-m-d')]
            );
        });
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('contact_name')) {
            $query->whereContactName($filters->get('contact_name'));
        }

        if ($filters->get('display_name')) {
            $query->whereDisplayName($filters->get('display_name'));
        }

        if ($filters->get('customer_id')) {
            $query->whereCustomer($filters->get('customer_id'));
        }

        if ($filters->get('phone')) {
            $query->wherePhone($filters->get('phone'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'name';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }
}
