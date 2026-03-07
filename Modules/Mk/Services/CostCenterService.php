<?php

namespace Modules\Mk\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\CostCenter;
use Modules\Mk\Models\CostCenterRule;

class CostCenterService
{
    /**
     * List cost centers for a company, optionally as a tree.
     *
     * @return array
     */
    public function list(int $companyId, bool $tree = false): array
    {
        if ($tree) {
            return $this->getTree($companyId);
        }

        $centers = CostCenter::forCompany($companyId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (CostCenter $cc) => $this->formatCostCenter($cc))
            ->toArray();

        return $centers;
    }

    /**
     * Get cost centers as a nested tree structure.
     *
     * @return array
     */
    public function getTree(int $companyId): array
    {
        $allCenters = CostCenter::forCompany($companyId)
            ->with(['children' => function ($q) {
                $q->orderBy('sort_order')->orderBy('name');
            }])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Build tree from top-level nodes
        $topLevel = $allCenters->whereNull('parent_id');

        return $topLevel->map(fn (CostCenter $cc) => $this->buildTreeNode($cc, $allCenters))
            ->values()
            ->toArray();
    }

    /**
     * Recursively build a tree node.
     */
    protected function buildTreeNode(CostCenter $center, $allCenters): array
    {
        $children = $allCenters->where('parent_id', $center->id);

        $node = $this->formatCostCenter($center);
        $node['children'] = $children
            ->map(fn (CostCenter $child) => $this->buildTreeNode($child, $allCenters))
            ->values()
            ->toArray();

        return $node;
    }

    /**
     * Format a single cost center for API response.
     */
    protected function formatCostCenter(CostCenter $cc): array
    {
        return [
            'id' => $cc->id,
            'company_id' => $cc->company_id,
            'parent_id' => $cc->parent_id,
            'name' => $cc->name,
            'code' => $cc->code,
            'color' => $cc->color,
            'description' => $cc->description,
            'is_active' => $cc->is_active,
            'sort_order' => $cc->sort_order,
            'full_path' => $cc->fullPath(),
            'created_at' => $cc->created_at?->toIso8601String(),
            'updated_at' => $cc->updated_at?->toIso8601String(),
        ];
    }

    /**
     * Create a new cost center.
     */
    public function create(int $companyId, array $data): CostCenter
    {
        // Validate parent belongs to same company if specified
        if (! empty($data['parent_id'])) {
            $parent = CostCenter::forCompany($companyId)->find($data['parent_id']);
            if (! $parent) {
                throw new \InvalidArgumentException('Parent cost center not found or belongs to different company.');
            }
        }

        // Auto-assign sort_order if not provided
        if (! isset($data['sort_order'])) {
            $maxSort = CostCenter::forCompany($companyId)
                ->where('parent_id', $data['parent_id'] ?? null)
                ->max('sort_order') ?? -1;
            $data['sort_order'] = $maxSort + 1;
        }

        $cc = CostCenter::create(array_merge($data, [
            'company_id' => $companyId,
        ]));

        Log::info('Cost center created', [
            'cost_center_id' => $cc->id,
            'company_id' => $companyId,
            'name' => $cc->name,
        ]);

        return $cc;
    }

    /**
     * Update a cost center.
     */
    public function update(CostCenter $cc, array $data): CostCenter
    {
        // Validate parent belongs to same company and is not self/descendant
        if (array_key_exists('parent_id', $data) && $data['parent_id'] !== null) {
            if ((int) $data['parent_id'] === $cc->id) {
                throw new \InvalidArgumentException('A cost center cannot be its own parent.');
            }

            $parent = CostCenter::forCompany($cc->company_id)->find($data['parent_id']);
            if (! $parent) {
                throw new \InvalidArgumentException('Parent cost center not found or belongs to different company.');
            }

            // Check for circular reference: parent must not be a descendant
            $descendantIds = $cc->descendants()->pluck('id')->toArray();
            if (in_array((int) $data['parent_id'], $descendantIds)) {
                throw new \InvalidArgumentException('Cannot set a descendant as the parent (circular reference).');
            }
        }

        $cc->update($data);

        Log::info('Cost center updated', [
            'cost_center_id' => $cc->id,
            'company_id' => $cc->company_id,
        ]);

        return $cc->fresh();
    }

    /**
     * Soft-delete a cost center.
     * Fails if there are active children.
     */
    public function delete(CostCenter $cc): void
    {
        if ($cc->hasActiveChildren()) {
            throw new \InvalidArgumentException('Cannot delete a cost center that has active children. Deactivate or delete children first.');
        }

        $cc->delete();

        Log::info('Cost center deleted', [
            'cost_center_id' => $cc->id,
            'company_id' => $cc->company_id,
        ]);
    }

    /**
     * Suggest a cost center for a document based on rules.
     *
     * Returns ['cost_center_id' => int|null, 'cost_center' => array|null, 'confidence' => string]
     */
    public function suggestForDocument(int $companyId, array $context): array
    {
        $matchedId = CostCenterRule::matchDocument($companyId, $context);

        if (! $matchedId) {
            return [
                'cost_center_id' => null,
                'cost_center' => null,
                'confidence' => 'none',
            ];
        }

        $cc = CostCenter::find($matchedId);

        if (! $cc || ! $cc->is_active) {
            return [
                'cost_center_id' => null,
                'cost_center' => null,
                'confidence' => 'none',
            ];
        }

        return [
            'cost_center_id' => $cc->id,
            'cost_center' => $this->formatCostCenter($cc),
            'confidence' => 'rule_match',
        ];
    }

    /**
     * Update sort orders in bulk.
     *
     * @param  array  $orders  Array of ['id' => int, 'sort_order' => int, 'parent_id' => int|null]
     */
    public function reorder(int $companyId, array $orders): void
    {
        DB::transaction(function () use ($companyId, $orders) {
            foreach ($orders as $item) {
                CostCenter::where('id', $item['id'])
                    ->where('company_id', $companyId)
                    ->update([
                        'sort_order' => $item['sort_order'] ?? 0,
                        'parent_id' => $item['parent_id'] ?? null,
                    ]);
            }
        });

        Log::info('Cost centers reordered', [
            'company_id' => $companyId,
            'count' => count($orders),
        ]);
    }
}

// CLAUDE-CHECKPOINT
