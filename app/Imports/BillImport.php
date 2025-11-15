<?php

namespace App\Imports;

use App\Models\Bill;
use App\Models\CompanySetting;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Validators\Failure;

class BillImport implements ToCollection, WithHeadingRow, SkipsOnFailure
{
    use Importable;

    private int $companyId;

    private int $creatorId;

    /**
     * Map of our field names to CSV column names
     *
     * @var array<string,string>
     */
    private array $columnMapping;

    private bool $isDryRun;

    private int $successCount = 0;

    private int $failureCount = 0;

    /**
     * @var array<int,array<string,mixed>>
     */
    private array $failures = [];

    private ?string $companyCurrencyId;

    public function __construct(
        int $companyId,
        int $creatorId,
        array $columnMapping = [],
        bool $isDryRun = false
    ) {
        $this->companyId = $companyId;
        $this->creatorId = $creatorId;
        $this->columnMapping = $columnMapping;
        $this->isDryRun = $isDryRun;
        $this->companyCurrencyId = CompanySetting::getSetting('currency', $companyId);
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowArray = $row->toArray();
            $mapped = $this->mapColumns($rowArray);

            try {
                $this->importRow($mapped, $index + 2); // +2 accounts for heading row
            } catch (\Throwable $e) {
                $this->failureCount++;
                $this->failures[] = [
                    'row' => $index + 2,
                    'attribute' => 'row',
                    'errors' => [$e->getMessage()],
                    'values' => $rowArray,
                ];

                Log::error('Bill import row failed', [
                    'company_id' => $this->companyId,
                    'row_number' => $index + 2,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param array<string,mixed> $row
     */
    private function importRow(array $row, int $rowNumber): void
    {
        // Required fields
        if (empty($row['bill_number']) || empty($row['bill_date']) || empty($row['total'])) {
            throw new \InvalidArgumentException('bill_number, bill_date, and total are required');
        }

        if ($this->isDryRun) {
            $this->successCount++;
            return;
        }

        $supplierName = $row['supplier_name'] ?? null;
        $supplierTaxId = $row['supplier_tax_id'] ?? null;

        $supplier = Supplier::updateOrCreate(
            [
                'company_id' => $this->companyId,
                'tax_id' => $supplierTaxId,
                'name' => $supplierName,
            ],
            [
                'company_id' => $this->companyId,
                'tax_id' => $supplierTaxId,
                'name' => $supplierName,
            ]
        );

        $billNumber = $row['bill_number'];
        $originalNumber = $billNumber;
        $counter = 1;
        while (Bill::where('company_id', $this->companyId)
            ->where('bill_number', $billNumber)
            ->exists()
        ) {
            $billNumber = $originalNumber.'-'.$counter;
            $counter++;
        }

        $total = $this->parseAmount($row['total']);
        $tax = $this->parseAmount($row['tax'] ?? 0);
        $subTotal = $this->parseAmount($row['sub_total'] ?? ($total - $tax));

        $bill = Bill::create([
            'company_id' => $this->companyId,
            'creator_id' => $this->creatorId,
            'supplier_id' => $supplier->id,
            'bill_number' => $billNumber,
            'bill_date' => $this->parseDate($row['bill_date']),
            'due_date' => $this->parseDate($row['due_date'] ?? null),
            'sub_total' => $subTotal,
            'tax' => $tax,
            'total' => $total,
            'discount' => 0,
            'discount_val' => 0,
            'due_amount' => $total,
            'notes' => $row['notes'] ?? null,
            'currency_id' => $this->companyCurrencyId,
            'exchange_rate' => 1.0,
            'base_total' => $total,
            'base_sub_total' => $subTotal,
            'base_tax' => $tax,
            'base_discount_val' => 0,
            'base_due_amount' => $total,
            'status' => Bill::STATUS_DRAFT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
        ]);

        // Optional single-line item if provided
        if (! empty($row['item_description']) || ! empty($row['item_price'])) {
            $qty = isset($row['item_quantity']) ? (float) $row['item_quantity'] : 1.0;
            $price = $this->parseAmount($row['item_price'] ?? $total);
            $itemTotal = $this->parseAmount($row['item_total'] ?? $total);

            $bill->items()->create([
                'company_id' => $this->companyId,
                'name' => $row['item_description'] ?? 'Item',
                'description' => $row['item_description'] ?? null,
                'quantity' => $qty,
                'price' => $price,
                'discount' => 0,
                'discount_val' => 0,
                'tax' => $tax,
                'total' => $itemTotal,
            ]);
        }

        $this->successCount++;

        Log::info('Bill imported from CSV/XLSX', [
            'company_id' => $this->companyId,
            'bill_id' => $bill->id,
            'row_number' => $rowNumber,
        ]);
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function mapColumns(array $row): array
    {
        if (empty($this->columnMapping)) {
            return $row;
        }

        $mapped = [];
        foreach ($this->columnMapping as $ourField => $csvColumn) {
            $normalizedColumn = strtolower(trim($csvColumn));
            $value = null;
            foreach ($row as $key => $val) {
                if (strtolower(trim((string) $key)) === $normalizedColumn) {
                    $value = $val;
                    break;
                }
            }

            $mapped[$ourField] = $value;
        }

        return $mapped;
    }

    private function parseDate($value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        try {
            if (is_numeric($value)) {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value));
            }

            return Carbon::parse($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function parseAmount($value): int
    {
        if (! $value) {
            return 0;
        }

        $value = str_replace(' ', '', (string) $value);

        if (preg_match('/\d+,\d{2}$/', $value)) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif (preg_match('/\d+\.\d{2}$/', $value)) {
            // US/UK style already ok
        } else {
            $value = preg_replace('/[^\d.]/', '', $value) ?? '0';
        }

        return (int) round(((float) $value) * 100);
    }

    public function onFailure(Failure ...$failures): void
    {
        foreach ($failures as $failure) {
            $this->failureCount++;
            $this->failures[] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    public function getSuccessCount(): int
    {
        return $this->successCount;
    }

    public function getFailureCount(): int
    {
        return $this->failureCount;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function getFailures(): array
    {
        return $this->failures;
    }
}

