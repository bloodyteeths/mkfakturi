<?php

namespace Modules\Mk\Models;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class CostCenterRule extends Model
{
    protected $table = 'cost_center_rules';

    protected $fillable = [
        'company_id',
        'cost_center_id',
        'match_type',
        'match_value',
        'priority',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    // ---- Relationships ----

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    // ---- Static Match Logic ----

    /**
     * Match a document against cost center rules and return the first matching cost_center_id.
     *
     * Context structure:
     * [
     *   'vendor_id'    => int|null,       // supplier/customer ID
     *   'account_code' => string|null,     // GL account code
     *   'description'  => string|null,     // document description/narration
     *   'item_ids'     => array<int>|null, // item IDs on the document
     * ]
     *
     * @return int|null The matched cost_center_id or null
     */
    public static function matchDocument(int $companyId, array $context): ?int
    {
        $rules = static::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($rules as $rule) {
            if ($rule->matches($context)) {
                return $rule->cost_center_id;
            }
        }

        return null;
    }

    /**
     * Check whether this rule matches the given document context.
     */
    public function matches(array $context): bool
    {
        return match ($this->match_type) {
            'vendor' => $this->matchVendor($context),
            'account' => $this->matchAccount($context),
            'description' => $this->matchDescription($context),
            'item' => $this->matchItem($context),
            default => false,
        };
    }

    /**
     * Match by vendor/supplier ID.
     * match_value is expected to be the vendor/supplier ID as a string.
     */
    protected function matchVendor(array $context): bool
    {
        if (empty($context['vendor_id'])) {
            return false;
        }

        return (string) $context['vendor_id'] === $this->match_value;
    }

    /**
     * Match by GL account code.
     * match_value is the account code (e.g., "5000", "400").
     * Supports prefix matching: "50" matches "500", "5001", etc.
     */
    protected function matchAccount(array $context): bool
    {
        if (empty($context['account_code'])) {
            return false;
        }

        return str_starts_with($context['account_code'], $this->match_value)
            || $context['account_code'] === $this->match_value;
    }

    /**
     * Match by description keyword (case-insensitive substring).
     */
    protected function matchDescription(array $context): bool
    {
        if (empty($context['description'])) {
            return false;
        }

        return mb_stripos($context['description'], $this->match_value) !== false;
    }

    /**
     * Match by item ID.
     * match_value is the item ID as a string.
     */
    protected function matchItem(array $context): bool
    {
        if (empty($context['item_ids']) || ! is_array($context['item_ids'])) {
            return false;
        }

        return in_array((int) $this->match_value, $context['item_ids']);
    }
}

// CLAUDE-CHECKPOINT
