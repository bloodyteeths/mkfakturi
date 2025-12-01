<?php

namespace App\Models;

use App\Traits\CacheableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Item extends Model
{
    use CacheableTrait;
    use HasFactory;

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($item) {
            $item->taxes()->delete();
        });
    }

    protected $guarded = ['id'];

    protected $appends = [
        'formattedCreatedAt',
        'unit_name',
    ];

    /**
     * Default eager loaded relationships
     */
    protected $with = [
        'unit:id,name',
        'currency:id,name,code,symbol',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function getUnitNameAttribute(): ?string
    {
        return $this->unit ? $this->unit->name : null;
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'creator_id');
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Scope a query to search items by name, SKU, barcode, or description.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $search
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereSearch($query, $search)
    {
        return $query->where(function ($query) use ($search) {
            $query->where('items.name', 'LIKE', '%'.$search.'%')
                ->orWhere('items.sku', 'LIKE', '%'.$search.'%')
                ->orWhere('items.barcode', 'LIKE', '%'.$search.'%')
                ->orWhere('items.description', 'LIKE', '%'.$search.'%');
        });
    }

    // CLAUDE-CHECKPOINT

    public function scopeWherePrice($query, $price)
    {
        return $query->where('items.price', $price);
    }

    public function scopeWhereUnit($query, $unit_id)
    {
        return $query->where('items.unit_id', $unit_id);
    }

    public function scopeWhereOrder($query, $orderByField, $orderBy)
    {
        $query->orderBy($orderByField, $orderBy);
    }

    public function scopeWhereItem($query, $item_id)
    {
        $query->orWhere('id', $item_id);
    }

    public function scopeApplyFilters($query, array $filters)
    {
        $filters = collect($filters);

        if ($filters->get('search')) {
            $query->whereSearch($filters->get('search'));
        }

        if ($filters->get('price')) {
            $query->wherePrice($filters->get('price'));
        }

        if ($filters->get('unit_id')) {
            $query->whereUnit($filters->get('unit_id'));
        }

        if ($filters->get('item_id')) {
            $query->whereItem($filters->get('item_id'));
        }

        if ($filters->get('orderByField') || $filters->get('orderBy')) {
            $field = $filters->get('orderByField') ? $filters->get('orderByField') : 'name';
            $orderBy = $filters->get('orderBy') ? $filters->get('orderBy') : 'asc';
            $query->whereOrder($field, $orderBy);
        }

        // Filter by track_quantity (for stock-enabled items only)
        if ($filters->has('track_quantity')) {
            $query->where('track_quantity', (bool) $filters->get('track_quantity'));
        }
    }

    public function scopePaginateData($query, $limit)
    {
        if ($limit == 'all') {
            return $query->get();
        }

        return $query->paginate($limit);
    }

    public function getFormattedCreatedAtAttribute($value)
    {
        $dateFormat = CompanySetting::getSetting('carbon_date_format', request()->header('company'));

        return Carbon::parse($this->created_at)->translatedFormat($dateFormat);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(Tax::class)
            ->where('invoice_item_id', null)
            ->where('estimate_item_id', null);
    }

    public function scopeWhereCompany($query)
    {
        $query->where('items.company_id', request()->header('company'));
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function estimateItems(): HasMany
    {
        return $this->hasMany(EstimateItem::class);
    }

    /**
     * Get all stock movements for this item.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Check if this item has stock tracking enabled.
     */
    public function hasStockTracking(): bool
    {
        return (bool) $this->track_quantity;
    }

    public static function createItem($request)
    {
        $data = $request->validated();
        $data['company_id'] = $request->header('company');
        $data['creator_id'] = Auth::id();
        $company_currency = CompanySetting::getSetting('currency', $request->header('company'));
        $data['currency_id'] = $company_currency;
        $item = self::create($data);

        if ($request->has('taxes')) {
            foreach ($request->taxes as $tax) {
                $item->tax_per_item = true;
                $item->save();
                $tax['company_id'] = $request->header('company');
                $item->taxes()->create($tax);
            }
        }

        $item = self::with('taxes')->find($item->id);

        return $item;
    }

    public function updateItem($request)
    {
        $this->update($request->validated());

        $this->taxes()->delete();

        if ($request->has('taxes')) {
            foreach ($request->taxes as $tax) {
                $this->tax_per_item = true;
                $this->save();
                $tax['company_id'] = $request->header('company');
                $this->taxes()->create($tax);
            }
        }

        return Item::with('taxes')->find($this->id);
    }
}
