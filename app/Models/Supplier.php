<?php

namespace App\Models;

use App\Traits\CacheableTrait;
use App\Traits\HasCustomFieldsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Contracts\Auditable;

class Supplier extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use CacheableTrait;
    use HasCustomFieldsTrait;
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [
        'id',
    ];

    protected $appends = [
        'formattedCreatedAt',
        'fullAddress',
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
            $dateFormat = CompanySetting::getSetting('carbon_date_format', $this->company_id);
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

        if (!empty($cityStateZip)) {
            $address[] = implode(', ', $cityStateZip);
        }

        if ($this->country) {
            $address[] = $this->country->name;
        }

        return implode("\n", $address);
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
     * Relationship: Supplier was created by User
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
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
     * Scope: Search across multiple fields
     */
    public function scopeWhereSearch($query, $search)
    {
        foreach (explode(' ', $search) as $term) {
            $query->where(function ($query) use ($term) {
                $query->where('name', 'LIKE', '%'.$term.'%')
                    ->orWhere('email', 'LIKE', '%'.$term.'%')
                    ->orWhere('phone', 'LIKE', '%'.$term.'%')
                    ->orWhere('contact_name', 'LIKE', '%'.$term.'%');
            });
        }
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
}

// CLAUDE-CHECKPOINT
