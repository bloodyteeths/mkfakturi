<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use PDF;

class SupplierReportsController extends Controller
{
    /**
     * Aging report for all suppliers (or a single supplier).
     * Groups unpaid bills into aging buckets: 0-30, 31-60, 61-90, 90+
     */
    public function aging(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');
        $asOfDate = $request->query('as_of_date')
            ? Carbon::parse($request->query('as_of_date'))
            : Carbon::now();

        $supplierId = $request->query('supplier_id');

        $query = Bill::where('company_id', $companyId)
            ->where('status', '!=', 'COMPLETED')
            ->where('bill_date', '<=', $asOfDate)
            ->with('supplier:id,name,tax_id,vat_number,city');

        if ($supplierId) {
            $query->where('supplier_id', $supplierId);
        }

        $bills = $query->get();

        $aging = [];

        foreach ($bills as $bill) {
            if (! $bill->supplier) {
                continue;
            }

            $sid = $bill->supplier_id;
            if (! isset($aging[$sid])) {
                $aging[$sid] = [
                    'supplier_id' => $sid,
                    'supplier_name' => $bill->supplier->name,
                    'tax_id' => $bill->supplier->tax_id,
                    'vat_number' => $bill->supplier->vat_number,
                    'city' => $bill->supplier->city,
                    'current' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'days_over_90' => 0,
                    'total' => 0,
                ];
            }

            $paidAmount = BillPayment::where('bill_id', $bill->id)->sum('amount');
            $outstanding = $bill->total - $paidAmount;

            if ($outstanding <= 0) {
                continue;
            }

            $dueDate = $bill->due_date ? Carbon::parse($bill->due_date) : Carbon::parse($bill->bill_date);
            $daysOverdue = $asOfDate->diffInDays($dueDate, false);

            if ($daysOverdue <= 0) {
                // Not yet due or due today
                $aging[$sid]['current'] += $outstanding;
            } elseif ($daysOverdue <= 30) {
                $aging[$sid]['current'] += $outstanding;
            } elseif ($daysOverdue <= 60) {
                $aging[$sid]['days_31_60'] += $outstanding;
            } elseif ($daysOverdue <= 90) {
                $aging[$sid]['days_61_90'] += $outstanding;
            } else {
                $aging[$sid]['days_over_90'] += $outstanding;
            }

            $aging[$sid]['total'] += $outstanding;
        }

        $agingData = collect($aging)->sortByDesc('total')->values();

        $totals = [
            'current' => $agingData->sum('current'),
            'days_31_60' => $agingData->sum('days_31_60'),
            'days_61_90' => $agingData->sum('days_61_90'),
            'days_over_90' => $agingData->sum('days_over_90'),
            'total' => $agingData->sum('total'),
        ];

        return response()->json([
            'data' => $agingData,
            'meta' => [
                'as_of_date' => $asOfDate->toDateString(),
                'totals' => $totals,
                'supplier_count' => $agingData->count(),
            ],
        ]);
    }

    /**
     * Aging report PDF.
     */
    public function agingPdf(Request $request)
    {
        $companyId = (int) $request->header('company');
        $company = \App\Models\Company::findOrFail($companyId);

        $locale = CompanySetting::getSetting('language', $companyId) ?: 'mk';
        App::setLocale($locale);

        $asOfDate = $request->query('as_of_date')
            ? Carbon::parse($request->query('as_of_date'))
            : Carbon::now();

        // Reuse the aging logic
        $response = $this->aging($request)->getData(true);

        $currencyId = CompanySetting::getSetting('currency', $companyId);
        $currency = $currencyId ? Currency::find($currencyId) : null;
        if (! $currency) {
            $currency = Currency::where('code', 'MKD')->first() ?: Currency::first();
        }

        $langFile = lang_path($locale . '.json');
        $t = file_exists($langFile) ? json_decode(file_get_contents($langFile), true) : [];

        view()->share([
            'company' => $company,
            'aging_data' => collect($response['data']),
            'totals' => $response['meta']['totals'],
            'as_of_date' => $asOfDate->translatedFormat(
                CompanySetting::getSetting('carbon_date_format', $companyId) ?: 'd/m/Y'
            ),
            'currency' => $currency,
            'labels' => [
                'title' => data_get($t, 'suppliers.aging_report') ?: 'Старосна структура на обврски',
                'supplier' => data_get($t, 'suppliers.name') ?: 'Добавувач',
                'tax_id' => data_get($t, 'suppliers.tax_id') ?: 'ЕМБС',
                'city' => data_get($t, 'suppliers.city') ?: 'Град',
                'current' => data_get($t, 'suppliers.aging_current') ?: '0-30 дена',
                'days_31_60' => data_get($t, 'suppliers.aging_31_60') ?: '31-60 дена',
                'days_61_90' => data_get($t, 'suppliers.aging_61_90') ?: '61-90 дена',
                'days_over_90' => data_get($t, 'suppliers.aging_over_90') ?: '90+ дена',
                'total' => data_get($t, 'general.total') ?: 'Вкупно',
                'as_of' => data_get($t, 'suppliers.as_of_date') ?: 'Состојба на',
                'prepared_by' => data_get($t, 'customers.prepared_by') ?: 'Составил',
                'approved_by' => data_get($t, 'customers.approved_by') ?: 'Одобрил',
            ],
        ]);

        $pdf = PDF::loadView('app.pdf.reports.supplier-aging');
        $pdf->setPaper('A4', 'landscape');

        $filename = 'aging-report-' . $asOfDate->toDateString() . '.pdf';

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * IOS — Извод на отворени ставки (Statement of Open Items)
     */
    public function ios(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $asOfDate = $request->query('as_of_date')
            ? Carbon::parse($request->query('as_of_date'))
            : Carbon::now();

        $bills = Bill::where('supplier_id', $supplier->id)
            ->where('bill_date', '<=', $asOfDate)
            ->orderBy('bill_date')
            ->get();

        $openItems = [];
        $totalOpen = 0;

        foreach ($bills as $bill) {
            $paidAmount = BillPayment::where('bill_id', $bill->id)
                ->where('payment_date', '<=', $asOfDate)
                ->sum('amount');
            $outstanding = $bill->total - $paidAmount;

            if ($outstanding <= 0) {
                continue;
            }

            $dueDate = $bill->due_date ?: $bill->bill_date;
            $daysOverdue = Carbon::parse($dueDate)->diffInDays($asOfDate, false);

            $openItems[] = [
                'bill_number' => $bill->bill_number,
                'bill_date' => $bill->bill_date,
                'due_date' => $dueDate,
                'total' => $bill->total,
                'paid' => $paidAmount,
                'outstanding' => $outstanding,
                'days_overdue' => max(0, $daysOverdue),
            ];

            $totalOpen += $outstanding;
        }

        return response()->json([
            'data' => $openItems,
            'meta' => [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'supplier_tax_id' => $supplier->tax_id,
                'supplier_vat_number' => $supplier->vat_number,
                'as_of_date' => $asOfDate->toDateString(),
                'total_open' => $totalOpen,
                'item_count' => count($openItems),
            ],
        ]);
    }

    /**
     * IOS PDF — Извод на отворени ставки
     */
    public function iosPdf(Request $request, Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $company = $supplier->company;
        $locale = CompanySetting::getSetting('language', $company->id) ?: 'mk';
        App::setLocale($locale);

        $response = $this->ios($request, $supplier)->getData(true);

        $currencyId = CompanySetting::getSetting('currency', $company->id);
        $currency = $currencyId ? Currency::find($currencyId) : null;
        if (! $currency) {
            $currency = Currency::where('code', 'MKD')->first() ?: Currency::first();
        }

        $dateFormat = CompanySetting::getSetting('carbon_date_format', $company->id) ?: 'd/m/Y';
        $asOfDate = Carbon::parse($response['meta']['as_of_date'])->translatedFormat($dateFormat);

        $langFile = lang_path($locale . '.json');
        $t = file_exists($langFile) ? json_decode(file_get_contents($langFile), true) : [];

        view()->share([
            'company' => $company,
            'supplier' => $supplier,
            'open_items' => collect($response['data']),
            'meta' => $response['meta'],
            'as_of_date' => $asOfDate,
            'currency' => $currency,
            'date_format' => $dateFormat,
            'labels' => [
                'title' => data_get($t, 'suppliers.ios_title') ?: 'Извод на отворени ставки (ИОС)',
                'bill_number' => data_get($t, 'bills.bill_number') ?: 'Број на фактура',
                'bill_date' => data_get($t, 'bills.bill_date') ?: 'Датум',
                'due_date' => data_get($t, 'bills.due_date') ?: 'Доспева',
                'total' => data_get($t, 'general.total') ?: 'Вкупно',
                'paid' => data_get($t, 'suppliers.paid') ?: 'Платено',
                'outstanding' => data_get($t, 'suppliers.outstanding') ?: 'Неплатено',
                'days_overdue' => data_get($t, 'suppliers.days_overdue') ?: 'Дена задоцнување',
                'as_of' => data_get($t, 'suppliers.as_of_date') ?: 'Состојба на',
                'supplier' => data_get($t, 'suppliers.name') ?: 'Добавувач',
                'tax_id' => data_get($t, 'suppliers.tax_id') ?: 'ЕМБС',
                'vat_number' => data_get($t, 'suppliers.vat_number') ?: 'ЕДБ',
                'total_open' => data_get($t, 'suppliers.total_open') ?: 'Вкупно отворено',
                'confirmation_text' => data_get($t, 'suppliers.ios_confirmation') ?: 'Го потврдуваме салдото наведено погоре. Во случај на неусогласеност, ве молиме известете нè во рок од 8 дена.',
                'our_signature' => data_get($t, 'suppliers.our_signature') ?: 'Потпис и печат (наша фирма)',
                'their_signature' => data_get($t, 'suppliers.their_signature') ?: 'Потпис и печат (добавувач)',
                'prepared_by' => data_get($t, 'customers.prepared_by') ?: 'Составил',
            ],
        ]);

        $pdf = PDF::loadView('app.pdf.reports.supplier-ios');
        $pdf->setPaper('A4', 'portrait');

        $filename = 'IOS-' . str_replace(' ', '_', $supplier->name) . '-' . $response['meta']['as_of_date'] . '.pdf';

        if ($request->has('download')) {
            return $pdf->download($filename);
        }

        return $pdf->stream($filename);
    }

    /**
     * Supplier statement — summary for a date range.
     */
    public function statement(Request $request, Supplier $supplier): JsonResponse
    {
        $this->authorize('view', $supplier);

        $fromDate = $request->query('from_date')
            ? Carbon::parse($request->query('from_date'))
            : Carbon::now()->startOfYear();
        $toDate = $request->query('to_date')
            ? Carbon::parse($request->query('to_date'))
            : Carbon::now()->endOfYear();

        // Opening balance: bills before period - payments before period
        $billsBefore = Bill::where('supplier_id', $supplier->id)
            ->where('bill_date', '<', $fromDate)
            ->sum('total');
        $paymentsBefore = BillPayment::whereHas('bill', function ($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id);
        })->where('payment_date', '<', $fromDate)->sum('amount');
        $openingBalance = $billsBefore - $paymentsBefore;

        // Period totals
        $billsInPeriod = Bill::where('supplier_id', $supplier->id)
            ->whereBetween('bill_date', [$fromDate, $toDate])
            ->sum('total');
        $paymentsInPeriod = BillPayment::whereHas('bill', function ($q) use ($supplier) {
            $q->where('supplier_id', $supplier->id);
        })->whereBetween('payment_date', [$fromDate, $toDate])->sum('amount');

        $closingBalance = $openingBalance + $billsInPeriod - $paymentsInPeriod;

        return response()->json([
            'data' => [
                'opening_balance' => $openingBalance,
                'bills_total' => $billsInPeriod,
                'payments_total' => $paymentsInPeriod,
                'closing_balance' => $closingBalance,
            ],
            'meta' => [
                'supplier_id' => $supplier->id,
                'supplier_name' => $supplier->name,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
            ],
        ]);
    }

    /**
     * Generate PP30 for all unpaid bills of a supplier.
     */
    public function pp30(Request $request, Supplier $supplier)
    {
        $this->authorize('view', $supplier);

        $supplier->load('company');
        $company = $supplier->company;

        if (empty($supplier->iban) && empty($supplier->bank_account)) {
            return response()->json([
                'success' => false,
                'message' => 'Добавувачот нема банковна сметка. Додајте IBAN или жиро сметка.',
            ], 422);
        }

        $unpaidBills = Bill::where('supplier_id', $supplier->id)
            ->where('status', '!=', 'COMPLETED')
            ->orderBy('due_date')
            ->get();

        if ($unpaidBills->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Нема неплатени фактури за овој добавувач.',
            ], 422);
        }

        // Use the first unpaid bill for PP30 generation
        $bill = $unpaidBills->first();
        $bill->load(['supplier', 'currency', 'company']);

        $bankAccountId = $request->query('bank_account_id');
        $bankAccount = $bankAccountId ? \App\Models\BankAccount::find($bankAccountId) : null;

        try {
            $pp30Service = app(\Modules\Mk\Services\Pp30PdfService::class);
            $pdf = $pp30Service->generateForBill($bill, $company, $bankAccount);

            return $pdf->download("PP30_{$supplier->name}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * CSV import for suppliers.
     */
    public function import(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $rows = [];
        if (in_array($extension, ['csv', 'txt'])) {
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);
            if (! $header) {
                fclose($handle);
                return response()->json(['success' => false, 'message' => 'Empty file'], 422);
            }

            // Normalize header names
            $header = array_map(function ($h) {
                return strtolower(trim(str_replace(["\xEF\xBB\xBF", '"'], '', $h)));
            }, $header);

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) !== count($header)) {
                    continue;
                }
                $rows[] = array_combine($header, $row);
            }
            fclose($handle);
        } else {
            return response()->json(['success' => false, 'message' => 'Use CSV format'], 422);
        }

        $fieldMap = [
            'name' => ['name', 'име', 'назив', 'supplier', 'vendor', 'добавувач', 'компанија', 'company'],
            'email' => ['email', 'е-пошта', 'e-mail', 'емаил'],
            'phone' => ['phone', 'телефон', 'тел', 'tel'],
            'tax_id' => ['tax_id', 'ембс', 'embs', 'матичен'],
            'vat_number' => ['vat_number', 'едб', 'edb', 'пиб', 'pib', 'vat', 'даночен број'],
            'city' => ['city', 'град', 'место'],
            'address_line_1' => ['address', 'адреса', 'address_line_1'],
            'bank_account' => ['bank_account', 'жиро сметка', 'сметка', 'account'],
            'iban' => ['iban'],
            'contact_name' => ['contact_name', 'контакт', 'contact', 'лице за контакт'],
            'authorized_person' => ['authorized_person', 'овластено лице', 'одговорно лице'],
            'activity_code' => ['activity_code', 'нкд', 'дејност', 'nkd'],
        ];

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $mapped = [];
            foreach ($fieldMap as $field => $synonyms) {
                foreach ($synonyms as $syn) {
                    if (isset($row[$syn]) && ! empty(trim($row[$syn]))) {
                        $mapped[$field] = trim($row[$syn]);
                        break;
                    }
                }
            }

            if (empty($mapped['name'])) {
                $skipped++;
                $errors[] = 'Row ' . ($i + 2) . ': missing name';
                continue;
            }

            // Check for duplicates by tax_id or name
            $exists = Supplier::where('company_id', $companyId)
                ->where(function ($q) use ($mapped) {
                    if (! empty($mapped['tax_id'])) {
                        $q->where('tax_id', $mapped['tax_id']);
                    } else {
                        $q->where('name', $mapped['name']);
                    }
                })
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $mapped['company_id'] = $companyId;
            $mapped['creator_id'] = $request->user()->id;

            Supplier::create($mapped);
            $imported++;
        }

        return response()->json([
            'success' => true,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => array_slice($errors, 0, 10),
            'message' => "Imported {$imported} suppliers, skipped {$skipped}.",
        ]);
    }
}

// CLAUDE-CHECKPOINT
