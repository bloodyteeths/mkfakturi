<?php

namespace App\Models;

use App\Traits\CacheableTrait;
use App\Traits\HasAuditing;
use App\Traits\HasCustomFieldsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use CacheableTrait;
    use HasAuditing;
    use HasCustomFieldsTrait;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [
        'id',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'fullAddress',
        'due_amount',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Get formatted created at date
     */
    public function getFormattedCreatedAtAttribute()
    {
        return $this->cacheComputed('formatted_created_at', function () {
            $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id) ?: 'Y-m-d';

            return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
        });
    }

    /**
     * Get full address formatted
     */
    public function getFullAddressAttribute()
    {
        $address = [];

        if ($this->address_line_1) {
            $address[] = $this->address_line_1;
        }

        if ($this->address_line_2) {
            $address[] = $this->address_line_2;
        }

        $cityStateZip = [];
        if ($this->city) {
            $cityStateZip[] = $this->city;
        }
        if ($this->state) {
            $cityStateZip[] = $this->state;
        }
        if ($this->zip) {
            $cityStateZip[] = $this->zip;
        }

        if (! empty($cityStateZip)) {
            $address[] = implode(', ', $cityStateZip);
        }

        if ($this->country) {
            $address[] = $this->country->name;
        }

        return implode("\n", $address);
    }

    /**
     * Get the total amount due to this supplier
     * (Total bills - Total payments made)
     */
    public function getDueAmountAttribute()
    {
        return $this->cacheComputed('due_amount', function () {
            $totalBills = $this->bills()->sum('total');
            $totalPayments = \App\Models\BillPayment::whereHas('bill', function ($query) {
                $query->where('supplier_id', $this->id);
            })->sum('amount');

            return $totalBills - $totalPayments;
        });
    }

    /**
     * Relationship: Supplier belongs to Company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relationship: Supplier belongs to Country
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * Relationship: Supplier has many Bills
     */
    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class);
    }

    /**
     * Relationship: Supplier belongs to Currency
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Relationship: Supplier was created by User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function linkedCustomer(): HasOne
    {
        return $this->hasOne(Customer::class, 'linked_supplier_id');
    }

    /**
     * Scope: Filter by company
     */
    public function scopeWhereCompany($query, $companyId = null)
    {
        $companyId = $companyId ?? request()->header('company');

        return $query->where('suppliers.company_id', $companyId);
    }

    /**
     * Scope: Filter by name
     */
    public function scopeWhereName($query, $name)
    {
        return $query->where('name', 'LIKE', '%'.$name.'%');
    }

    /**
     * Scope: Filter by contact name
     */
    public function scopeWhereContactName($query, $contactName)
    {
        return $query->where('contact_name', 'LIKE', '%'.$contactName.'%');
    }

    /**
     * Scope: Filter by phone
     */
    public function scopeWherePhone($query, $phone)
    {
        return $query->where('phone', 'LIKE', '%'.$phone.'%');
    }

    /**
     * Scope: Search across multiple fields
     */
    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('email', 'LIKE', '%'.$term.'%')
                    ->orWhere('phone', 'LIKE', '%'.$term.'%')
                    ->orWhere('contact_name', 'LIKE', '%'.$term.'%')
                    ->orWhere('tax_id', 'LIKE', '%'.$term.'%')
                    ->orWhere('vat_number', 'LIKE', '%'.$term.'%');
            });
        }
    }

    /**
     * Scope: Filter suppliers with outstanding balance
     */
    public function scopeWhereHasOutstanding($query)
    {
        $query->whereHas('bills', function ($q) {
            $q->whereRaw('total > COALESCE((SELECT SUM(amount) FROM bill_payments WHERE bill_payments.bill_id = bills.id AND bill_payments.deleted_at IS NULL), 0)');
        });
    }

    /**
     * Scope: Order results
     */
    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    /**
     * Scope: Apply filters
     */
    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('name')) {
            $query->whereName($filters->get('name'));
        }

        if ($filters->get('contact_name')) {
            $query->whereContactName($filters->get('contact_name'));
        }

        if ($filters->get('phone')) {
            $query->wherePhone($filters->get('phone'));
        }

        if ($filters->get('has_outstanding')) {
            $query->whereHasOutstanding();
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'name';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }
    }

    /**
     * Scope: Paginate data
     */
    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    /**
     * Delete suppliers and their related records
     */
    public static function deleteSuppliers($ids)
    {
        foreach ($ids as $id) {
            $supplier = self::find($id);

            if (! $supplier) {
                continue;
            }

            if (Customer::where('linked_supplier_id', $supplier->id)->exists()) {
                throw new \Exception(__('suppliers.cannot_delete_linked'));
            }

            if ($supplier->bills()->exists()) {
                $supplier->bills->map(function ($bill) {
                    if ($bill->items()->exists()) {
                        $bill->items()->delete();
                    }
                    if ($bill->payments()->exists()) {
                        $bill->payments()->delete();
                    }
                    $bill->delete();
                });
            }

            $supplier->delete();
        }

        return true;
    }

    /**
     * Find potential duplicate suppliers by name, tax_id, email, or phone.
     */
    public static function findPotentialDuplicates(int $companyId, array $criteria, ?int $excludeId = null): \Illuminate\Support\Collection
    {
        $duplicates = collect();

        // Exact field matches: tax_id, email, phone
        foreach (['tax_id', 'email', 'phone'] as $field) {
            if (! empty($criteria[$field])) {
                $query = self::where('company_id', $companyId)
                    ->where($field, $criteria[$field]);
                if ($excludeId) {
                    $query->where('id', '!=', $excludeId);
                }
                $query->get()->each(function ($record) use (&$duplicates, $field) {
                    if (! $duplicates->contains('id', $record->id)) {
                        $duplicates->push((object) [
                            'id' => $record->id,
                            'name' => $record->name,
                            'email' => $record->email,
                            'phone' => $record->phone,
                            'tax_id' => $record->tax_id,
                            'match_reason' => $field,
                        ]);
                    }
                });
            }
        }

        // Fuzzy name match
        if (! empty($criteria['name'])) {
            $service = app(\App\Services\DuplicateDetectionService::class);
            $matches = $service->findSimilarByName(self::class, $companyId, $criteria['name'], $excludeId);
            $matches->each(function ($match) use (&$duplicates) {
                if (! $duplicates->contains('id', $match['record']->id)) {
                    $duplicates->push((object) [
                        'id' => $match['record']->id,
                        'name' => $match['record']->name,
                        'email' => $match['record']->email ?? null,
                        'phone' => $match['record']->phone ?? null,
                        'tax_id' => $match['record']->tax_id ?? null,
                        'match_reason' => $match['match_reason'],
                    ]);
                }
            });
        }

        return $duplicates;
    }
}

