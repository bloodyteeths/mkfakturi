<?php

namespace Modules\Mk\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Models\Manufacturing\Bom;
use Modules\Mk\Models\Manufacturing\BomLine;
use Modules\Mk\Services\ManufacturingService;

/**
 * Import manufacturing data from PANTHEON CSV/XML exports.
 *
 * PANTHEON exports: Šifrant > Sestavnice > Export CSV/XML
 * Format: product_code, product_name, material_code, material_name, quantity, unit, wastage
 */
class PantheonImportController extends Controller
{
    public function __construct(
        protected ManufacturingService $service,
    ) {}

    /**
     * Preview a PANTHEON export file — parse and show what would be imported.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xml|max:10240',
            'format' => 'nullable|in:csv,xml',
        ]);

        $companyId = (int) $request->header('company');
        $file = $request->file('file');
        $format = $request->input('format', $file->getClientOriginalExtension() === 'xml' ? 'xml' : 'csv');

        try {
            $parsed = $format === 'xml'
                ? $this->parseXml($file->getPathname())
                : $this->parseCsv($file->getPathname());

            // Match items by code or name
            $existingItems = \App\Models\Item::where('company_id', $companyId)
                ->get(['id', 'name', 'barcode'])
                ->keyBy(fn ($i) => mb_strtolower(trim($i->name)));

            $boms = [];
            foreach ($parsed as $product => $materials) {
                $productMatch = $existingItems->get(mb_strtolower(trim($product)));
                $matched = 0;
                $unmatched = 0;
                $materialPreview = [];

                foreach ($materials as $mat) {
                    $matMatch = $existingItems->get(mb_strtolower(trim($mat['name'])));
                    if ($matMatch) {
                        $matched++;
                    } else {
                        $unmatched++;
                    }
                    $materialPreview[] = [
                        'name' => $mat['name'],
                        'code' => $mat['code'] ?? null,
                        'quantity' => $mat['quantity'],
                        'unit' => $mat['unit'] ?? null,
                        'matched' => (bool) $matMatch,
                        'item_id' => $matMatch?->id,
                    ];
                }

                $boms[] = [
                    'product_name' => $product,
                    'product_matched' => (bool) $productMatch,
                    'product_item_id' => $productMatch?->id,
                    'materials' => $materialPreview,
                    'matched_count' => $matched,
                    'unmatched_count' => $unmatched,
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'boms' => $boms,
                    'total_boms' => count($boms),
                    'total_materials' => collect($boms)->sum(fn ($b) => count($b['materials'])),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import PANTHEON BOMs — creates items that don't exist, then creates BOMs.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,xml|max:10240',
            'format' => 'nullable|in:csv,xml',
            'create_missing_items' => 'nullable|boolean',
        ]);

        $companyId = (int) $request->header('company');
        $file = $request->file('file');
        $format = $request->input('format', $file->getClientOriginalExtension() === 'xml' ? 'xml' : 'csv');
        $createMissing = (bool) $request->input('create_missing_items', true);

        try {
            $parsed = $format === 'xml'
                ? $this->parseXml($file->getPathname())
                : $this->parseCsv($file->getPathname());

            $existingItems = \App\Models\Item::where('company_id', $companyId)
                ->get(['id', 'name', 'barcode'])
                ->keyBy(fn ($i) => mb_strtolower(trim($i->name)));

            $defaultUnit = \App\Models\Unit::where('company_id', $companyId)->first();
            $defaultCurrency = \App\Models\Currency::where('company_id', $companyId)
                ->where('code', 'MKD')->first()
                ?? \App\Models\Currency::where('company_id', $companyId)->first();

            $created = 0;
            $skipped = 0;
            $itemsCreated = 0;
            $errors = [];

            foreach ($parsed as $product => $materials) {
                // Find or create output item
                $outputItem = $existingItems->get(mb_strtolower(trim($product)));
                if (! $outputItem && $createMissing) {
                    $outputItem = \App\Models\Item::create([
                        'company_id' => $companyId,
                        'name' => trim($product),
                        'unit_id' => $defaultUnit?->id,
                        'currency_id' => $defaultCurrency?->id,
                        'price' => 0,
                        'type' => 'product',
                    ]);
                    $existingItems->put(mb_strtolower(trim($product)), $outputItem);
                    $itemsCreated++;
                }

                if (! $outputItem) {
                    $skipped++;
                    continue;
                }

                // Check if BOM already exists for this item
                $existingBom = Bom::where('company_id', $companyId)
                    ->where('output_item_id', $outputItem->id)
                    ->first();

                if ($existingBom) {
                    $skipped++;
                    continue;
                }

                // Create BOM
                $bom = Bom::create([
                    'company_id' => $companyId,
                    'name' => 'PANTHEON — ' . trim($product),
                    'code' => 'PTH-' . str_pad($created + 1, 4, '0', STR_PAD_LEFT),
                    'output_item_id' => $outputItem->id,
                    'output_quantity' => 1,
                    'currency_id' => $defaultCurrency?->id,
                    'is_active' => true,
                ]);

                // Add material lines
                foreach ($materials as $idx => $mat) {
                    $matItem = $existingItems->get(mb_strtolower(trim($mat['name'])));
                    if (! $matItem && $createMissing) {
                        $matItem = \App\Models\Item::create([
                            'company_id' => $companyId,
                            'name' => trim($mat['name']),
                            'unit_id' => $defaultUnit?->id,
                            'currency_id' => $defaultCurrency?->id,
                            'price' => 0,
                            'type' => 'material',
                        ]);
                        $existingItems->put(mb_strtolower(trim($mat['name'])), $matItem);
                        $itemsCreated++;
                    }

                    if (! $matItem) {
                        continue;
                    }

                    BomLine::create([
                        'bom_id' => $bom->id,
                        'item_id' => $matItem->id,
                        'quantity' => (float) $mat['quantity'],
                        'unit_id' => $defaultUnit?->id,
                        'wastage_percent' => (float) ($mat['wastage'] ?? 0),
                        'sort_order' => $idx + 1,
                    ]);
                }

                $created++;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'boms_created' => $created,
                    'boms_skipped' => $skipped,
                    'items_created' => $itemsCreated,
                ],
                'message' => "{$created} нормативи импортирани, {$itemsCreated} нови артикли креирани.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Parse PANTHEON CSV export.
     * Expected columns: product_code, product_name, material_code, material_name, quantity, unit, wastage
     * Semicolon-delimited (PANTHEON default).
     */
    private function parseCsv(string $path): array
    {
        $boms = [];
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw new \RuntimeException('Cannot open file');
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        $delimiter = str_contains($firstLine, ';') ? ';' : ',';

        // Skip header
        $header = fgetcsv($handle, 0, $delimiter);
        if (! $header || count($header) < 4) {
            fclose($handle);
            throw new \RuntimeException('Invalid CSV format. Expected: product_name, material_name, quantity, unit (minimum 4 columns)');
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (count($row) < 4) {
                continue;
            }

            $productName = trim($row[1] ?? $row[0] ?? '');
            $materialName = trim($row[3] ?? $row[2] ?? '');
            $quantity = (float) str_replace(',', '.', $row[4] ?? $row[2] ?? '1');

            if (! $productName || ! $materialName) {
                continue;
            }

            if (! isset($boms[$productName])) {
                $boms[$productName] = [];
            }

            $boms[$productName][] = [
                'code' => trim($row[2] ?? ''),
                'name' => $materialName,
                'quantity' => $quantity ?: 1,
                'unit' => trim($row[5] ?? ''),
                'wastage' => (float) str_replace(',', '.', $row[6] ?? '0'),
            ];
        }

        fclose($handle);

        return $boms;
    }

    /**
     * Parse PANTHEON XML export.
     * Uses LIBXML_NONET to prevent XXE external entity attacks.
     */
    private function parseXml(string $path): array
    {
        $boms = [];
        $content = file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Cannot read XML file');
        }

        $xml = simplexml_load_string($content, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_NONET);
        if (! $xml) {
            throw new \RuntimeException('Invalid XML file');
        }

        // PANTHEON XML structure: <Sestavnice><Sestavnica><Artikel>...<Material>...</Sestavnica>
        foreach ($xml->children() as $node) {
            $productName = trim((string) ($node->Naziv ?? $node->ProductName ?? $node->Name ?? ''));
            if (! $productName) {
                continue;
            }

            $materials = [];
            foreach ($node->children() as $child) {
                $childName = $child->getName();
                if (in_array($childName, ['Material', 'Komponenta', 'Component', 'Line'])) {
                    $matName = trim((string) ($child->Naziv ?? $child->MaterialName ?? $child->Name ?? ''));
                    if (! $matName) {
                        continue;
                    }
                    $materials[] = [
                        'code' => trim((string) ($child->Sifra ?? $child->Code ?? '')),
                        'name' => $matName,
                        'quantity' => (float) str_replace(',', '.', (string) ($child->Kolicina ?? $child->Quantity ?? '1')),
                        'unit' => trim((string) ($child->EM ?? $child->Unit ?? '')),
                        'wastage' => (float) str_replace(',', '.', (string) ($child->Kalo ?? $child->Wastage ?? '0')),
                    ];
                }
            }

            if (! empty($materials)) {
                $boms[$productName] = $materials;
            }
        }

        return $boms;
    }
}

// CLAUDE-CHECKPOINT
