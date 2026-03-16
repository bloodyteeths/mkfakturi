<?php

namespace App\Services;

use App\Models\AiDraft;
use App\Models\Bill;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\TaxType;
use App\Models\Unit;
use App\Models\User;
use App\Services\AiProvider\GeminiProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;

/**
 * AI Natural Language Accounting Service
 *
 * Processes natural-language commands like "Фактура за Марков, 10 часа по 3000"
 * into structured draft entities (invoice, bill, expense, payment, estimate, etc.).
 *
 * Flow:
 *   1. User types in AiChatWidget
 *   2. Gemini classifies intent + extracts structured data (with conversation history)
 *   3. Service fuzzy-matches customer/supplier names
 *   4. Creates AiDraft record → returns draft_id + redirect URL
 *   5. User lands on pre-filled form to review & submit
 *
 * AI suggests, human approves — never auto-posts.
 */
class AiNaturalLanguageService
{
    private ?GeminiProvider $provider = null;

    /** All creatable intents that produce drafts */
    private const CREATE_INTENTS = [
        'create_invoice',
        'create_bill',
        'create_expense',
        'record_payment',
        'create_estimate',
        'create_proforma',
        'create_credit_note',
        'create_recurring',
    ];

    /** Language labels per locale */
    private const LOCALE_NAMES = [
        'mk' => 'Macedonian (македонски)',
        'sq' => 'Albanian (shqip)',
        'tr' => 'Turkish (Türkçe)',
        'en' => 'English',
    ];

    /**
     * Process a natural language command.
     *
     * @return array{intent: string, draft_id: ?int, redirect_url: ?string, navigation_url: ?string, message: string, clarification_needed: ?string}
     */
    public function process(string $input, Company $company, User $user, array $history = [], string $locale = 'mk'): array
    {
        try {
            $provider = $this->getProvider();
            if (! $provider) {
                return $this->textResponse($this->t('ai_unavailable', $locale));
            }

            $prompt = $this->buildPrompt($input, $company, $history, $locale);

            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 2048,
                'thinking_budget' => 0,
            ]);

            // Track usage
            try {
                app(UsageLimitService::class)->incrementUsage($company, 'ai_queries_per_month');
            } catch (\Exception $e) {
                // Non-critical
            }

            $parsed = $this->parseResponse($response);
            if (! $parsed) {
                return $this->textResponse($this->t('parse_error', $locale));
            }

            $intent = $parsed['intent'] ?? 'question';

            // If it's a question, return the answer directly
            if ($intent === 'question') {
                return $this->textResponse($parsed['answer'] ?? $this->t('question_fallback', $locale));
            }

            // Navigate intent — guide user to a feature page
            if ($intent === 'navigate') {
                return [
                    'intent' => 'navigate',
                    'draft_id' => null,
                    'redirect_url' => null,
                    'navigation_url' => $parsed['navigation_url'] ?? null,
                    'message' => $parsed['answer'] ?? $parsed['navigation_instructions'] ?? '',
                    'clarification_needed' => null,
                ];
            }

            // If clarification is needed
            if (! empty($parsed['clarification_needed'])) {
                return [
                    'intent' => $intent,
                    'draft_id' => null,
                    'redirect_url' => null,
                    'navigation_url' => null,
                    'message' => $parsed['clarification_needed'],
                    'clarification_needed' => $parsed['clarification_needed'],
                ];
            }

            // Validate creatable entity type
            if (! in_array($intent, self::CREATE_INTENTS)) {
                return $this->textResponse($parsed['answer'] ?? $this->t('create_fallback', $locale));
            }

            // Extract entities and resolve names
            $entities = $parsed['entities'] ?? [];
            $entityType = $this->intentToEntityType($intent);
            $resolvedData = $this->resolveEntities($entities, $company, $entityType);

            // Try direct entity creation (invoice, bill, expense, estimate)
            $created = $this->createEntityDirectly($entityType, $resolvedData, $company, $user);

            if ($created) {
                $message = $this->buildCreatedMessage($entityType, $resolvedData, $created, $locale);

                return [
                    'intent' => $intent,
                    'draft_id' => null,
                    'redirect_url' => $created['view_url'],
                    'navigation_url' => null,
                    'message' => $message,
                    'clarification_needed' => null,
                ];
            }

            // Fallback to draft if direct creation fails
            $draft = AiDraft::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'entity_type' => $entityType,
                'entity_data' => $resolvedData,
                'status' => AiDraft::STATUS_PENDING,
                'expires_at' => now()->addHour(),
            ]);

            $redirectUrl = $this->getRedirectUrl($entityType, $draft->id);
            $message = $this->buildConfirmationMessage($entityType, $resolvedData, $parsed['confidence'] ?? 0.9, $locale);

            return [
                'intent' => $intent,
                'draft_id' => $draft->id,
                'redirect_url' => $redirectUrl,
                'navigation_url' => null,
                'message' => $message,
                'clarification_needed' => null,
            ];
        } catch (\Exception $e) {
            Log::warning('[NLAssistant] Processing failed', [
                'input' => substr($input, 0, 200),
                'error' => $e->getMessage(),
            ]);

            return $this->textResponse($this->t('generic_error', $locale));
        }
    }

    /**
     * Get a draft by ID (for form pre-fill).
     */
    public function getDraft(int $draftId, int $companyId): ?AiDraft
    {
        return AiDraft::where('id', $draftId)
            ->where('company_id', $companyId)
            ->usable()
            ->first();
    }

    /**
     * Build the Gemini prompt for intent classification + entity extraction.
     * Now includes conversation history and locale-aware instructions.
     */
    protected function buildPrompt(string $input, Company $company, array $history = [], string $locale = 'mk'): string
    {
        // Get recent customers (top 20 by recent activity)
        $customers = Customer::where('company_id', $company->id)
            ->select('id', 'name')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])
            ->toArray();

        // Get recent suppliers
        $suppliers = DB::table('suppliers')
            ->where('company_id', $company->id)
            ->whereNull('deleted_at')
            ->select('id', 'name')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])
            ->toArray();

        // Get popular items
        $items = DB::table('items')
            ->where('company_id', $company->id)
            ->select('id', 'name', 'price')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get()
            ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'price' => (int) $i->price])
            ->toArray();

        $currency = $company->currency?->code ?? 'MKD';
        $langName = self::LOCALE_NAMES[$locale] ?? 'Macedonian (македонски)';

        $customersJson = json_encode($customers, JSON_UNESCAPED_UNICODE);
        $suppliersJson = json_encode($suppliers, JSON_UNESCAPED_UNICODE);
        $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

        // Build conversation history section
        $historySection = '';
        if (! empty($history)) {
            $historySection = "\n=== CONVERSATION HISTORY (use this for context) ===\n";
            // Only include the last 8 messages to keep prompt manageable
            $recentHistory = array_slice($history, -8);
            foreach ($recentHistory as $msg) {
                $role = ($msg['role'] ?? '') === 'user' ? 'User' : 'Assistant';
                $content = substr($msg['content'] ?? '', 0, 500);
                $historySection .= "{$role}: {$content}\n";
            }
            $historySection .= "=== END HISTORY ===\n";
        }

        return <<<PROMPT
You are Facturino's AI accounting assistant for company "{$company->name}" (currency: {$currency}).
You MUST respond in {$langName}. All your text responses (answer, clarification_needed) MUST be in this language.
All amounts are in the smallest unit (cents/deni). 1 MKD = 100 deni. "3000 ден" = 300000 in cents.

Known customers: {$customersJson}
Known suppliers: {$suppliersJson}
Known items (prices in cents): {$itemsJson}
{$historySection}
Current user message: "{$input}"

=== YOUR CAPABILITIES ===
You can CREATE drafts for: invoices, bills, expenses, payments, estimates, proforma invoices, credit notes, recurring invoices.
You can NAVIGATE users to any Facturino feature page.
You can ANSWER questions about Facturino features and the user's financial data.

=== FACTURINO FEATURES (navigation URLs) ===
INVOICING: /admin/invoices (list), /admin/invoices/create (new)
ESTIMATES: /admin/estimates (list), /admin/estimates/create (new)
PROFORMA: /admin/proforma-invoices (list), /admin/proforma-invoices/create (new)
CREDIT NOTES: /admin/credit-notes (list), /admin/credit-notes/create (new)
RECURRING: /admin/recurring-invoices (list), /admin/recurring-invoices/create (new)
CUSTOMERS: /admin/customers (list), /admin/customers/create (new)
ITEMS: /admin/items (list), /admin/items/create (new)
PAYMENTS: /admin/payments (list), /admin/payments/create (new)
EXPENSES: /admin/expenses (list), /admin/expenses/create (new)
SUPPLIERS: /admin/suppliers (list), /admin/suppliers/create (new)
BILLS: /admin/bills (list), /admin/bills/create (new)
REPORTS: /admin/reports (all reports)
SETTINGS: /admin/settings (company settings)
TAX TYPES: /admin/settings/tax-types (VAT/DDV rates)
BANKING: /admin/banking/connections (bank connections), /admin/banking/reconciliation (reconciliation)
INVENTORY: /admin/inventory/stock-management (stock), /admin/inventory/warehouses (warehouses)
PURCHASE ORDERS: /admin/purchase-orders (list), /admin/purchase-orders/create (new)
PAYMENT ORDERS: /admin/payment-orders (list), /admin/payment-orders/create (new)
BUDGETS: /admin/budgets (list), /admin/budgets/create (new)
COST CENTERS: /admin/cost-centers (list)
TRAVEL ORDERS: /admin/travel-orders (list), /admin/travel-orders/create (new)
COMPENSATIONS: /admin/compensations (list)
COLLECTIONS: /admin/collections (receivable aging)
PAYROLL: /admin/payroll/employees (employees), /admin/payroll/runs (payroll runs)
E-INVOICING: /admin/e-invoices (e-faktura list)
DOCUMENTS: /admin/documents (AI document hub)
PROJECTS: /admin/projects (list)
DASHBOARD: /admin/dashboard (main dashboard)
BI DASHBOARD: /admin/bi-dashboard (business intelligence)
CUSTOM REPORTS: /admin/custom-reports (user-defined reports)
BATCH OPERATIONS: /admin/batch-operations (bulk journal entries)
FINANCIAL CONSOLIDATION: /admin/financial-consolidation
INTEREST CALCULATOR: /admin/interest-calculator

=== INSTRUCTIONS ===
Classify the user's intent and return ONLY valid JSON (no markdown, no code blocks):
{{
  "intent": "create_invoice|create_bill|create_expense|record_payment|create_estimate|create_proforma|create_credit_note|create_recurring|navigate|question",
  "entities": {{
    "customer_id": null or number,
    "customer_name": "string or null",
    "supplier_id": null or number,
    "supplier_name": "string or null",
    "items": [{{"name": "string", "description": "string or null", "quantity": number, "unit_price": number_in_cents, "tax_percentage": number or null}}],
    "amount": number_in_cents,
    "date": "YYYY-MM-DD",
    "due_date": "YYYY-MM-DD or null",
    "notes": "string or null",
    "category": "string or null",
    "invoice_reference": "string or null"
  }},
  "confidence": 0.0-1.0,
  "clarification_needed": null or "question in user's language",
  "answer": null or "text response in user's language",
  "navigation_url": null or "/admin/feature-page",
  "navigation_instructions": null or "instructions in user's language"
}}

=== CRITICAL RULES ===
1. USE CONVERSATION HISTORY: If the user refers to something from previous messages (e.g., "create it", "yes", "do it", "make that invoice"), look at the history for context. Extract the entity data from previous messages.
2. ALWAYS respond in {$langName}. All text fields (answer, clarification_needed, navigation_instructions) MUST be in this language.
3. Match customer/supplier names fuzzy (Cyrillic/Latin, ДООЕЛ/ДОО variations).
4. "фактура"/"invoice" = create_invoice, "понуда"/"estimate"/"quote" = create_estimate, "профактура"/"proforma" = create_proforma, "кредит нота"/"credit note" = create_credit_note, "повторувачка"/"recurring" = create_recurring, "сметка"/"bill" = create_bill, "трошок"/"expense" = create_expense, "уплата"/"payment" = record_payment
5. For navigation requests ("show me...", "where is...", "open...", "go to..."), use intent "navigate" with the URL.
6. All monetary amounts MUST be in cents (multiply by 100). If user says "50,000 dinar" → amount = 5000000.
7. If tax/DDV/ДДВ percentage is specified (e.g., "18% DDV"), include it in items[].tax_percentage.
8. Default date is today: {$this->today()}
9. When user says "create" or "make" something, ALWAYS use a create_* intent — NEVER give step-by-step instructions. Your job is to CREATE the draft, not explain how to use the UI.
10. NEVER say a feature doesn't exist. Facturino has ALL the features listed above.
PROMPT;
    }

    /**
     * Parse Gemini JSON response.
     */
    protected function parseResponse(string $response): ?array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*\n?/m', '', $response);
        $cleaned = preg_replace('/\n?```\s*$/m', '', $cleaned);
        $cleaned = trim($cleaned);

        $data = json_decode($cleaned, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            Log::warning('[NLAssistant] Failed to parse AI response', [
                'response' => substr($response, 0, 500),
                'json_error' => json_last_error_msg(),
            ]);

            return null;
        }

        if (! isset($data['intent'])) {
            return null;
        }

        return $data;
    }

    /**
     * Resolve entity references (fuzzy match customer/supplier names).
     */
    protected function resolveEntities(array $entities, Company $company, string $entityType): array
    {
        $resolved = $entities;

        // Resolve customer for invoices, estimates, proforma, credit notes, payments
        $customerEntities = [
            AiDraft::ENTITY_INVOICE,
            AiDraft::ENTITY_PAYMENT,
            AiDraft::ENTITY_ESTIMATE,
            AiDraft::ENTITY_PROFORMA,
            AiDraft::ENTITY_CREDIT_NOTE,
            AiDraft::ENTITY_RECURRING,
        ];

        if (in_array($entityType, $customerEntities)) {
            if (empty($resolved['customer_id']) && ! empty($resolved['customer_name'])) {
                $customer = $this->fuzzyFindCustomer($resolved['customer_name'], $company->id);
                if ($customer) {
                    $resolved['customer_id'] = $customer->id;
                    $resolved['customer_name'] = $customer->name;
                } else {
                    // Auto-create customer — the user explicitly asked to create an entity for this name
                    $customer = $this->autoCreateCustomer($resolved['customer_name'], $company);
                    $resolved['customer_id'] = $customer->id;
                    $resolved['customer_name'] = $customer->name;
                    $resolved['_customer_created'] = true;
                }
            }
        }

        // Resolve supplier for bills
        if ($entityType === AiDraft::ENTITY_BILL) {
            if (empty($resolved['supplier_id']) && ! empty($resolved['supplier_name'])) {
                $supplier = $this->fuzzyFindSupplier($resolved['supplier_name'], $company->id);
                if ($supplier) {
                    $resolved['supplier_id'] = $supplier->id;
                    $resolved['supplier_name'] = $supplier->name;
                } else {
                    // Auto-create supplier — the user explicitly asked to create a bill for this name
                    $supplier = $this->autoCreateSupplier($resolved['supplier_name'], $company);
                    $resolved['supplier_id'] = $supplier->id;
                    $resolved['supplier_name'] = $supplier->name;
                    $resolved['_supplier_created'] = true;
                }
            }
        }

        // Resolve item IDs — auto-create if not found
        if (! empty($resolved['items'])) {
            foreach ($resolved['items'] as $i => $item) {
                if (empty($item['item_id']) && ! empty($item['name'])) {
                    $found = $this->fuzzyFindItem($item['name'], $company->id);
                    if ($found) {
                        $resolved['items'][$i]['item_id'] = $found->id;
                        $resolved['items'][$i]['name'] = $found->name;
                    } else {
                        // Auto-create item with the AI-extracted name and price
                        $newItem = $this->autoCreateItem(
                            $item['name'],
                            $item['unit_price'] ?? 0,
                            $company
                        );
                        $resolved['items'][$i]['item_id'] = $newItem->id;
                        $resolved['items'][$i]['name'] = $newItem->name;
                        $resolved['items'][$i]['_item_created'] = true;
                    }
                }
            }
        }

        return $resolved;
    }

    /**
     * Fuzzy-find a customer by name.
     *
     * Matching priority:
     * 1. Exact match
     * 2. Case-insensitive exact match
     * 3. Input contained in DB name (e.g. "Adidas" matches "ADIDAS DOO")
     * 4. DB name contained in input (e.g. "Adidas shoes company" matches "Adidas")
     * 5. Cyrillic/Latin transliteration (e.g. "Адидас" matches "Adidas")
     * 6. Soundex-like similarity using Levenshtein on normalized names
     */
    protected function fuzzyFindCustomer(string $name, int $companyId): ?Customer
    {
        $allCustomers = Customer::where('company_id', $companyId)->get();

        return $this->duplicateService()->bestMatch($name, $allCustomers, 'name');
    }

    /**
     * Fuzzy-find a supplier by name.
     */
    protected function fuzzyFindSupplier(string $name, int $companyId): ?object
    {
        $allSuppliers = Supplier::where('company_id', $companyId)->get();

        return $this->duplicateService()->bestMatch($name, $allSuppliers, 'name');
    }

    /**
     * Fuzzy-find an item by name.
     */
    protected function fuzzyFindItem(string $name, int $companyId): ?object
    {
        $allItems = DB::table('items')
            ->where('company_id', $companyId)
            ->get();

        return $this->duplicateService()->bestMatch($name, $allItems, 'name');
    }

    /**
     * Get the DuplicateDetectionService instance.
     */
    protected function duplicateService(): DuplicateDetectionService
    {
        return app(DuplicateDetectionService::class);
    }

    /**
     * Auto-create a new Customer from the AI-extracted name.
     */
    protected function autoCreateCustomer(string $name, Company $company): Customer
    {
        $currencyId = CompanySetting::getSetting('currency', $company->id);

        $customer = Customer::create([
            'name' => $name,
            'company_id' => $company->id,
            'currency_id' => $currencyId,
            'creator_id' => Auth::id(),
        ]);

        Log::info('[NLAssistant] Auto-created customer', [
            'customer_id' => $customer->id,
            'name' => $name,
            'company_id' => $company->id,
        ]);

        return $customer;
    }

    /**
     * Auto-create a new Supplier from the AI-extracted name.
     */
    protected function autoCreateSupplier(string $name, Company $company): Supplier
    {
        $supplier = Supplier::create([
            'name' => $name,
            'company_id' => $company->id,
            'creator_id' => Auth::id(),
        ]);

        Log::info('[NLAssistant] Auto-created supplier', [
            'supplier_id' => $supplier->id,
            'name' => $name,
            'company_id' => $company->id,
        ]);

        return $supplier;
    }

    /**
     * Auto-create a new Item from the AI-extracted name and price.
     */
    protected function autoCreateItem(string $name, int $price, Company $company): Item
    {
        // Use the first available unit or default to null
        $unit = Unit::where('company_id', $company->id)->first()
            ?? Unit::whereNull('company_id')->first();

        $currencyId = CompanySetting::getSetting('currency', $company->id);

        $item = Item::create([
            'name' => $name,
            'price' => $price,
            'company_id' => $company->id,
            'unit_id' => $unit?->id,
            'currency_id' => $currencyId,
            'creator_id' => Auth::id(),
        ]);

        Log::info('[NLAssistant] Auto-created item', [
            'item_id' => $item->id,
            'name' => $name,
            'price' => $price,
            'company_id' => $company->id,
        ]);

        return $item;
    }

    /**
     * Map intent string to entity type.
     */
    protected function intentToEntityType(string $intent): string
    {
        return match ($intent) {
            'create_invoice' => AiDraft::ENTITY_INVOICE,
            'create_bill' => AiDraft::ENTITY_BILL,
            'create_expense' => AiDraft::ENTITY_EXPENSE,
            'record_payment' => AiDraft::ENTITY_PAYMENT,
            'create_estimate' => AiDraft::ENTITY_ESTIMATE,
            'create_proforma' => AiDraft::ENTITY_PROFORMA,
            'create_credit_note' => AiDraft::ENTITY_CREDIT_NOTE,
            'create_recurring' => AiDraft::ENTITY_RECURRING,
            default => AiDraft::ENTITY_INVOICE,
        };
    }

    /**
     * Get the redirect URL for the create form.
     */
    protected function getRedirectUrl(string $entityType, int $draftId): string
    {
        $routes = [
            AiDraft::ENTITY_INVOICE => '/admin/invoices/create',
            AiDraft::ENTITY_BILL => '/admin/bills/create',
            AiDraft::ENTITY_EXPENSE => '/admin/expenses/create',
            AiDraft::ENTITY_PAYMENT => '/admin/payments/create',
            AiDraft::ENTITY_ESTIMATE => '/admin/estimates/create',
            AiDraft::ENTITY_PROFORMA => '/admin/proforma-invoices/create',
            AiDraft::ENTITY_CREDIT_NOTE => '/admin/credit-notes/create',
            AiDraft::ENTITY_RECURRING => '/admin/recurring-invoices/create',
        ];

        $base = $routes[$entityType] ?? '/admin/invoices/create';

        return "{$base}?draft_id={$draftId}";
    }

    /**
     * Build a human-readable confirmation message in the user's locale.
     */
    protected function buildConfirmationMessage(string $entityType, array $data, float $confidence, string $locale = 'mk'): string
    {
        $typeLabels = $this->getEntityLabels($locale);
        $label = $typeLabels[$entityType] ?? $entityType;
        $name = $data['customer_name'] ?? $data['supplier_name'] ?? '';

        $creating = $this->t('creating', $locale);
        $parts = ["{$creating} **{$label}**"];

        if ($name) {
            $forWord = $this->t('for', $locale);
            $parts[0] .= " {$forWord} **{$name}**";
        }

        // Add items or amount summary
        if (! empty($data['items'])) {
            $itemCount = count($data['items']);
            $total = 0;
            foreach ($data['items'] as $item) {
                $qty = $item['quantity'] ?? 1;
                $price = $item['unit_price'] ?? 0;
                $total += $qty * $price;
            }
            $totalDisplay = number_format($total / 100, 2, ',', '.');
            $currency = 'ден';
            $itemsWord = $this->t('items', $locale);
            $totalWord = $this->t('total', $locale);
            $parts[] = "{$itemCount} {$itemsWord}, {$totalWord} **{$totalDisplay} {$currency}**";
        } elseif (! empty($data['amount'])) {
            $amountDisplay = number_format($data['amount'] / 100, 2, ',', '.');
            $amountWord = $this->t('amount', $locale);
            $parts[] = "{$amountWord} **{$amountDisplay} ден**";
        }

        return implode(' — ', $parts) . '.';
    }

    /**
     * Create the actual entity (Invoice, Bill, Expense, Estimate) directly as DRAFT.
     * Returns ['id' => int, 'number' => string, 'view_url' => string] or null on failure.
     */
    protected function createEntityDirectly(string $entityType, array $data, Company $company, User $user): ?array
    {
        try {
            return match ($entityType) {
                AiDraft::ENTITY_INVOICE => $this->createInvoiceDirectly($data, $company, $user),
                AiDraft::ENTITY_BILL => $this->createBillDirectly($data, $company, $user),
                AiDraft::ENTITY_EXPENSE => $this->createExpenseDirectly($data, $company, $user),
                AiDraft::ENTITY_ESTIMATE => $this->createEstimateDirectly($data, $company, $user),
                default => null, // Fall back to draft for unsupported types
            };
        } catch (\Exception $e) {
            Log::warning('[NLAssistant] Direct entity creation failed, falling back to draft', [
                'entity_type' => $entityType,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a real Invoice as DRAFT.
     */
    protected function createInvoiceDirectly(array $data, Company $company, User $user): ?array
    {
        $customerId = $data['customer_id'] ?? null;
        if (! $customerId) {
            return null; // Can't create invoice without customer
        }

        $customer = Customer::find($customerId);
        if (! $customer) {
            return null;
        }

        $currencyId = $customer->currency_id ?? CompanySetting::getSetting('currency', $company->id);
        $exchangeRate = 1;

        // Calculate totals from items
        $itemsData = $data['items'] ?? [];
        $subTotal = 0;
        $totalTax = 0;

        foreach ($itemsData as $item) {
            $qty = $item['quantity'] ?? 1;
            $price = $item['unit_price'] ?? 0;
            $lineTotal = $qty * $price;
            $subTotal += $lineTotal;

            $taxPct = $item['tax_percentage'] ?? 0;
            if ($taxPct > 0) {
                $totalTax += (int) round($lineTotal * $taxPct / 100);
            }
        }

        // Use direct amount if no items
        if (empty($itemsData) && ! empty($data['amount'])) {
            $subTotal = (int) $data['amount'];
        }

        $total = $subTotal + $totalTax;

        $invoice = Invoice::create([
            'invoice_date' => $data['date'] ?? now()->format('Y-m-d'),
            'due_date' => $data['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'invoice_number' => 'AI-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'customer_id' => $customer->id,
            'company_id' => $company->id,
            'status' => Invoice::STATUS_DRAFT,
            'paid_status' => Invoice::STATUS_UNPAID,
            'currency_id' => $currencyId,
            'exchange_rate' => $exchangeRate,
            'sub_total' => $subTotal,
            'tax' => $totalTax,
            'total' => $total,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'due_amount' => $total,
            'base_total' => $total * $exchangeRate,
            'base_sub_total' => $subTotal * $exchangeRate,
            'base_tax' => $totalTax * $exchangeRate,
            'base_discount_val' => 0,
            'base_due_amount' => $total * $exchangeRate,
            'tax_per_item' => CompanySetting::getSetting('tax_per_item', $company->id) ?? 'NO',
            'discount_per_item' => CompanySetting::getSetting('discount_per_item', $company->id) ?? 'NO',
            'template_name' => 'invoice1',
            'notes' => $data['notes'] ?? null,
            'creator_id' => $user->id,
        ]);

        // Set serial numbers
        $serial = (new SerialNumberFormatter)
            ->setModel($invoice)
            ->setCompany($invoice->company_id)
            ->setCustomer($invoice->customer_id)
            ->setNextNumbers();

        $invoice->sequence_number = $serial->nextSequenceNumber;
        $invoice->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $invoice->unique_hash = Hashids::connection(Invoice::class)->encode($invoice->id);
        $invoice->invoice_number = $serial->getNextNumber();
        $invoice->save();

        // Create items with taxes
        if (! empty($itemsData)) {
            $this->createInvoiceItems($invoice, $itemsData, $company->id, $currencyId, $exchangeRate);
        }

        return [
            'id' => $invoice->id,
            'number' => $invoice->invoice_number,
            'view_url' => "/admin/invoices/{$invoice->id}/view",
        ];
    }

    /**
     * Create invoice items with tax type resolution.
     */
    protected function createInvoiceItems(Invoice $invoice, array $itemsData, int $companyId, $currencyId, float $exchangeRate): void
    {
        $taxTypes = TaxType::where('company_id', $companyId)
            ->orWhereNull('company_id')
            ->get();

        foreach ($itemsData as $item) {
            $qty = $item['quantity'] ?? 1;
            $price = $item['unit_price'] ?? 0;
            $lineSubTotal = $qty * $price;
            $taxPct = $item['tax_percentage'] ?? 0;
            $taxAmount = $taxPct > 0 ? (int) round($lineSubTotal * $taxPct / 100) : 0;
            $lineTotal = $lineSubTotal + $taxAmount;

            $invoiceItem = $invoice->items()->create([
                'name' => $item['name'] ?? 'Item',
                'description' => $item['description'] ?? null,
                'quantity' => $qty,
                'price' => $price,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => $taxAmount,
                'total' => $lineTotal,
                'item_id' => $item['item_id'] ?? null,
                'company_id' => $companyId,
                'exchange_rate' => $exchangeRate,
                'base_price' => $price * $exchangeRate,
                'base_discount_val' => 0,
                'base_tax' => $taxAmount * $exchangeRate,
                'base_total' => $lineTotal * $exchangeRate,
                'unit_name' => null,
                'currency_id' => $currencyId,
            ]);

            // Attach tax type if percentage specified
            if ($taxPct > 0 && $taxAmount > 0) {
                $taxType = $taxTypes->first(fn ($t) => abs((float) $t->percent - $taxPct) < 0.5);

                if ($taxType) {
                    $invoiceItem->taxes()->create([
                        'tax_type_id' => $taxType->id,
                        'name' => $taxType->name,
                        'percent' => (float) $taxType->percent,
                        'amount' => $taxAmount,
                        'compound_tax' => $taxType->compound_tax ?? 0,
                        'company_id' => $companyId,
                        'exchange_rate' => $exchangeRate,
                        'base_amount' => $taxAmount * $exchangeRate,
                        'currency_id' => $currencyId,
                    ]);
                }
            }
        }
    }

    /**
     * Create a real Bill as DRAFT.
     */
    protected function createBillDirectly(array $data, Company $company, User $user): ?array
    {
        $supplierId = $data['supplier_id'] ?? null;
        if (! $supplierId) {
            return null;
        }

        $currencyId = CompanySetting::getSetting('currency', $company->id);

        $itemsData = $data['items'] ?? [];
        $subTotal = 0;
        $totalTax = 0;

        foreach ($itemsData as $item) {
            $qty = $item['quantity'] ?? 1;
            $price = $item['unit_price'] ?? 0;
            $lineTotal = $qty * $price;
            $subTotal += $lineTotal;

            $taxPct = $item['tax_percentage'] ?? 0;
            if ($taxPct > 0) {
                $totalTax += (int) round($lineTotal * $taxPct / 100);
            }
        }

        if (empty($itemsData) && ! empty($data['amount'])) {
            $subTotal = (int) $data['amount'];
        }

        $total = $subTotal + $totalTax;

        $bill = Bill::create([
            'bill_date' => $data['date'] ?? now()->format('Y-m-d'),
            'due_date' => $data['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'bill_number' => 'AI-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'supplier_id' => $supplierId,
            'company_id' => $company->id,
            'status' => Bill::STATUS_DRAFT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'sub_total' => $subTotal,
            'tax' => $totalTax,
            'total' => $total,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'due_amount' => $total,
            'base_total' => $total,
            'base_sub_total' => $subTotal,
            'base_tax' => $totalTax,
            'base_discount_val' => 0,
            'base_due_amount' => $total,
            'tax_per_item' => CompanySetting::getSetting('tax_per_item', $company->id) ?? 'NO',
            'discount_per_item' => CompanySetting::getSetting('discount_per_item', $company->id) ?? 'NO',
            'notes' => $data['notes'] ?? null,
            'creator_id' => $user->id,
        ]);

        // Set serial numbers
        $serial = (new SerialNumberFormatter)
            ->setModel($bill)
            ->setCompany($bill->company_id)
            ->setNextNumbers();

        $bill->sequence_number = $serial->nextSequenceNumber;
        $bill->unique_hash = Hashids::connection(Bill::class)->encode($bill->id);
        $bill->bill_number = $serial->getNextNumber();
        $bill->save();

        return [
            'id' => $bill->id,
            'number' => $bill->bill_number,
            'view_url' => "/admin/bills/{$bill->id}/view",
        ];
    }

    /**
     * Create a real Expense directly.
     */
    protected function createExpenseDirectly(array $data, Company $company, User $user): ?array
    {
        $amount = $data['amount'] ?? 0;

        // Calculate from items if no direct amount
        if (! $amount && ! empty($data['items'])) {
            foreach ($data['items'] as $item) {
                $qty = $item['quantity'] ?? 1;
                $price = $item['unit_price'] ?? 0;
                $amount += $qty * $price;
            }
        }

        if ($amount <= 0) {
            return null;
        }

        $currencyId = CompanySetting::getSetting('currency', $company->id);

        $expense = Expense::create([
            'expense_date' => $data['date'] ?? now()->format('Y-m-d'),
            'amount' => $amount,
            'company_id' => $company->id,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'base_amount' => $amount,
            'notes' => $data['notes'] ?? ($data['items'][0]['name'] ?? null),
            'expense_category_id' => null,
            'creator_id' => $user->id,
            'customer_id' => $data['customer_id'] ?? null,
        ]);

        $expense->unique_hash = Hashids::connection(Expense::class)->encode($expense->id);
        $expense->save();

        return [
            'id' => $expense->id,
            'number' => 'EXP-' . $expense->id,
            'view_url' => "/admin/expenses/{$expense->id}/view",
        ];
    }

    /**
     * Create a real Estimate as DRAFT.
     */
    protected function createEstimateDirectly(array $data, Company $company, User $user): ?array
    {
        $customerId = $data['customer_id'] ?? null;
        if (! $customerId) {
            return null;
        }

        $customer = Customer::find($customerId);
        if (! $customer) {
            return null;
        }

        $currencyId = $customer->currency_id ?? CompanySetting::getSetting('currency', $company->id);

        $itemsData = $data['items'] ?? [];
        $subTotal = 0;
        $totalTax = 0;

        foreach ($itemsData as $item) {
            $qty = $item['quantity'] ?? 1;
            $price = $item['unit_price'] ?? 0;
            $lineTotal = $qty * $price;
            $subTotal += $lineTotal;

            $taxPct = $item['tax_percentage'] ?? 0;
            if ($taxPct > 0) {
                $totalTax += (int) round($lineTotal * $taxPct / 100);
            }
        }

        $total = $subTotal + $totalTax;

        $estimate = Estimate::create([
            'estimate_date' => $data['date'] ?? now()->format('Y-m-d'),
            'expiry_date' => $data['due_date'] ?? now()->addDays(30)->format('Y-m-d'),
            'estimate_number' => 'AI-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'customer_id' => $customer->id,
            'company_id' => $company->id,
            'status' => Estimate::STATUS_DRAFT,
            'currency_id' => $currencyId,
            'exchange_rate' => 1,
            'sub_total' => $subTotal,
            'tax' => $totalTax,
            'total' => $total,
            'discount' => 0,
            'discount_val' => 0,
            'discount_type' => 'fixed',
            'base_total' => $total,
            'base_sub_total' => $subTotal,
            'base_tax' => $totalTax,
            'base_discount_val' => 0,
            'tax_per_item' => CompanySetting::getSetting('tax_per_item', $company->id) ?? 'NO',
            'discount_per_item' => CompanySetting::getSetting('discount_per_item', $company->id) ?? 'NO',
            'template_name' => 'estimate1',
            'notes' => $data['notes'] ?? null,
            'creator_id' => $user->id,
        ]);

        $serial = (new SerialNumberFormatter)
            ->setModel($estimate)
            ->setCompany($estimate->company_id)
            ->setCustomer($estimate->customer_id)
            ->setNextNumbers();

        $estimate->sequence_number = $serial->nextSequenceNumber;
        $estimate->customer_sequence_number = $serial->nextCustomerSequenceNumber;
        $estimate->unique_hash = Hashids::connection(Estimate::class)->encode($estimate->id);
        $estimate->estimate_number = $serial->getNextNumber();
        $estimate->save();

        // Create estimate items (same pattern as invoice)
        if (! empty($itemsData)) {
            $this->createEstimateItems($estimate, $itemsData, $company->id, $currencyId);
        }

        return [
            'id' => $estimate->id,
            'number' => $estimate->estimate_number,
            'view_url' => "/admin/estimates/{$estimate->id}/view",
        ];
    }

    /**
     * Create estimate items with taxes.
     */
    protected function createEstimateItems(Estimate $estimate, array $itemsData, int $companyId, $currencyId): void
    {
        $taxTypes = TaxType::where('company_id', $companyId)
            ->orWhereNull('company_id')
            ->get();

        foreach ($itemsData as $item) {
            $qty = $item['quantity'] ?? 1;
            $price = $item['unit_price'] ?? 0;
            $lineSubTotal = $qty * $price;
            $taxPct = $item['tax_percentage'] ?? 0;
            $taxAmount = $taxPct > 0 ? (int) round($lineSubTotal * $taxPct / 100) : 0;
            $lineTotal = $lineSubTotal + $taxAmount;

            $estimateItem = $estimate->items()->create([
                'name' => $item['name'] ?? 'Item',
                'description' => $item['description'] ?? null,
                'quantity' => $qty,
                'price' => $price,
                'discount_type' => 'fixed',
                'discount' => 0,
                'discount_val' => 0,
                'tax' => $taxAmount,
                'total' => $lineTotal,
                'item_id' => $item['item_id'] ?? null,
                'company_id' => $companyId,
                'exchange_rate' => 1,
                'base_price' => $price,
                'base_discount_val' => 0,
                'base_tax' => $taxAmount,
                'base_total' => $lineTotal,
                'currency_id' => $currencyId,
            ]);

            if ($taxPct > 0 && $taxAmount > 0) {
                $taxType = $taxTypes->first(fn ($t) => abs((float) $t->percent - $taxPct) < 0.5);

                if ($taxType) {
                    $estimateItem->taxes()->create([
                        'tax_type_id' => $taxType->id,
                        'name' => $taxType->name,
                        'percent' => (float) $taxType->percent,
                        'amount' => $taxAmount,
                        'compound_tax' => $taxType->compound_tax ?? 0,
                        'company_id' => $companyId,
                        'exchange_rate' => 1,
                        'base_amount' => $taxAmount,
                        'currency_id' => $currencyId,
                    ]);
                }
            }
        }
    }

    /**
     * Build confirmation message for a directly created entity (with link).
     */
    protected function buildCreatedMessage(string $entityType, array $data, array $created, string $locale = 'mk'): string
    {
        $typeLabels = $this->getEntityLabels($locale);
        $label = $typeLabels[$entityType] ?? $entityType;
        $name = $data['customer_name'] ?? $data['supplier_name'] ?? '';
        $number = $created['number'] ?? '';

        $createdWord = $this->t('created', $locale);
        $parts = ["{$createdWord} **{$label}** #{$number}"];

        if ($name) {
            $forWord = $this->t('for', $locale);
            $parts[0] .= " {$forWord} **{$name}**";
        }

        // Add items or amount summary
        if (! empty($data['items'])) {
            $itemCount = count($data['items']);
            $total = 0;
            foreach ($data['items'] as $item) {
                $qty = $item['quantity'] ?? 1;
                $price = $item['unit_price'] ?? 0;
                $total += $qty * $price;
            }
            $totalDisplay = number_format($total / 100, 2, ',', '.');
            $itemsWord = $this->t('items', $locale);
            $totalWord = $this->t('total', $locale);
            $parts[] = "{$itemCount} {$itemsWord}, {$totalWord} **{$totalDisplay} ден**";
        } elseif (! empty($data['amount'])) {
            $amountDisplay = number_format($data['amount'] / 100, 2, ',', '.');
            $amountWord = $this->t('amount', $locale);
            $parts[] = "{$amountWord} **{$amountDisplay} ден**";
        }

        // Note about auto-created entities
        $autoCreated = [];
        if (! empty($data['_customer_created'])) {
            $autoCreated[] = $this->t('new_customer', $locale);
        }
        if (! empty($data['_supplier_created'])) {
            $autoCreated[] = $this->t('new_supplier', $locale);
        }
        $itemsCreated = 0;
        foreach (($data['items'] ?? []) as $item) {
            if (! empty($item['_item_created'])) {
                $itemsCreated++;
            }
        }
        if ($itemsCreated > 0) {
            $autoCreated[] = $this->t('new_items', $locale) . " ({$itemsCreated})";
        }
        if (! empty($autoCreated)) {
            $alsoCreated = $this->t('also_created', $locale);
            $parts[] = "{$alsoCreated}: " . implode(', ', $autoCreated);
        }

        $draftNote = $this->t('draft_note', $locale);
        $parts[] = $draftNote;

        return implode(' — ', $parts) . '.';
    }

    /**
     * Get entity type labels in the given locale.
     */
    protected function getEntityLabels(string $locale): array
    {
        return match ($locale) {
            'sq' => [
                AiDraft::ENTITY_INVOICE => 'faturë',
                AiDraft::ENTITY_BILL => 'llogari',
                AiDraft::ENTITY_EXPENSE => 'shpenzim',
                AiDraft::ENTITY_PAYMENT => 'pagesë',
                AiDraft::ENTITY_ESTIMATE => 'ofertë',
                AiDraft::ENTITY_PROFORMA => 'profaturë',
                AiDraft::ENTITY_CREDIT_NOTE => 'notë krediti',
                AiDraft::ENTITY_RECURRING => 'faturë e përsëritur',
            ],
            'tr' => [
                AiDraft::ENTITY_INVOICE => 'fatura',
                AiDraft::ENTITY_BILL => 'gider faturası',
                AiDraft::ENTITY_EXPENSE => 'masraf',
                AiDraft::ENTITY_PAYMENT => 'ödeme',
                AiDraft::ENTITY_ESTIMATE => 'teklif',
                AiDraft::ENTITY_PROFORMA => 'proforma',
                AiDraft::ENTITY_CREDIT_NOTE => 'kredi notu',
                AiDraft::ENTITY_RECURRING => 'tekrarlayan fatura',
            ],
            'en' => [
                AiDraft::ENTITY_INVOICE => 'invoice',
                AiDraft::ENTITY_BILL => 'bill',
                AiDraft::ENTITY_EXPENSE => 'expense',
                AiDraft::ENTITY_PAYMENT => 'payment',
                AiDraft::ENTITY_ESTIMATE => 'estimate',
                AiDraft::ENTITY_PROFORMA => 'proforma invoice',
                AiDraft::ENTITY_CREDIT_NOTE => 'credit note',
                AiDraft::ENTITY_RECURRING => 'recurring invoice',
            ],
            default => [ // mk
                AiDraft::ENTITY_INVOICE => 'фактура',
                AiDraft::ENTITY_BILL => 'сметка',
                AiDraft::ENTITY_EXPENSE => 'трошок',
                AiDraft::ENTITY_PAYMENT => 'уплата',
                AiDraft::ENTITY_ESTIMATE => 'понуда',
                AiDraft::ENTITY_PROFORMA => 'профактура',
                AiDraft::ENTITY_CREDIT_NOTE => 'кредит нота',
                AiDraft::ENTITY_RECURRING => 'повторувачка фактура',
            ],
        };
    }

    /**
     * Simple locale-aware text snippets for confirmation messages.
     */
    protected function t(string $key, string $locale): string
    {
        $strings = [
            'mk' => [
                'creating' => 'Ќе креирам',
                'created' => 'Креирав',
                'for' => 'за',
                'items' => 'ставки',
                'total' => 'вкупно',
                'amount' => 'износ',
                'draft_note' => 'статус: **НАЦРТ** (прегледајте пред испраќање)',
                'also_created' => 'Додадов нови',
                'new_customer' => 'клиент',
                'new_supplier' => 'добавувач',
                'new_items' => 'артикли',
                'ai_unavailable' => 'AI провајдерот не е достапен. Обидете се повторно подоцна.',
                'parse_error' => 'Не можев да го разберам барањето. Обидете се со нешто како "Фактура за Марков, 10 часа консалтинг по 3000 ден".',
                'question_fallback' => 'Не сум сигурен како да одговорам. Обидете се да прашате за креирање фактури, сметки или трошоци.',
                'create_fallback' => 'Можам да помогнам со креирање фактури, сметки, трошоци, понуди, профактури, кредит ноти или повторувачки фактури.',
                'generic_error' => 'Нешто тргна наопаку. Обидете се повторно.',
            ],
            'sq' => [
                'creating' => 'Do të krijoj',
                'created' => 'Krijova',
                'for' => 'për',
                'items' => 'artikuj',
                'total' => 'gjithsej',
                'amount' => 'shuma',
                'draft_note' => 'statusi: **DRAFT** (rishikoni para se ta dërgoni)',
                'also_created' => 'Gjithashtu krijova',
                'new_customer' => 'klient',
                'new_supplier' => 'furnizues',
                'new_items' => 'artikuj',
                'ai_unavailable' => 'Ofresi AI nuk është i disponueshëm. Provoni përsëri më vonë.',
                'parse_error' => 'Nuk munda ta kuptoj kërkesën. Provoni diçka si "Faturë për Markovi, 10 orë konsulencë me 3000 den".',
                'question_fallback' => 'Nuk jam i sigurt si të përgjigjem. Provoni të pyesni për krijimin e faturave, llogarive ose shpenzimeve.',
                'create_fallback' => 'Mund t\'ju ndihmoj me krijimin e faturave, llogarive, shpenzimeve, ofertave, profaturave, notave të kreditit ose faturave të përsëritura.',
                'generic_error' => 'Diçka shkoi keq. Provoni përsëri.',
            ],
            'tr' => [
                'creating' => 'Oluşturulacak',
                'created' => 'Oluşturuldu',
                'for' => 'için',
                'items' => 'kalem',
                'total' => 'toplam',
                'amount' => 'tutar',
                'draft_note' => 'durum: **TASLAK** (göndermeden önce kontrol edin)',
                'also_created' => 'Ayrıca oluşturuldu',
                'new_customer' => 'müşteri',
                'new_supplier' => 'tedarikçi',
                'new_items' => 'ürünler',
                'ai_unavailable' => 'AI sağlayıcısı kullanılamıyor. Lütfen daha sonra tekrar deneyin.',
                'parse_error' => 'İsteğinizi anlayamadım. "Markov için fatura, 10 saat danışmanlık 3000 den" gibi bir şey deneyin.',
                'question_fallback' => 'Nasıl cevaplayacağımdan emin değilim. Fatura, gider veya masraf oluşturma hakkında sormayı deneyin.',
                'create_fallback' => 'Fatura, gider faturası, masraf, teklif, proforma, kredi notu veya tekrarlayan fatura oluşturmanıza yardımcı olabilirim.',
                'generic_error' => 'Bir hata oluştu. Lütfen tekrar deneyin.',
            ],
            'en' => [
                'creating' => 'Creating',
                'created' => 'Created',
                'for' => 'for',
                'items' => 'items',
                'total' => 'total',
                'amount' => 'amount',
                'draft_note' => 'status: **DRAFT** (review before sending)',
                'also_created' => 'Also created new',
                'new_customer' => 'customer',
                'new_supplier' => 'supplier',
                'new_items' => 'items',
                'ai_unavailable' => 'AI provider is not available. Please try again later.',
                'parse_error' => 'I couldn\'t understand that request. Try something like "Invoice for Markov, 10 hours consulting at 3000 den".',
                'question_fallback' => 'I\'m not sure how to answer that. Try asking about creating invoices, bills, or expenses.',
                'create_fallback' => 'I can help with creating invoices, bills, expenses, estimates, proformas, credit notes, or recurring invoices.',
                'generic_error' => 'Something went wrong. Please try again.',
            ],
        ];

        return $strings[$locale][$key] ?? $strings['mk'][$key] ?? $key;
    }

    protected function textResponse(string $message): array
    {
        return [
            'intent' => 'question',
            'draft_id' => null,
            'redirect_url' => null,
            'navigation_url' => null,
            'message' => $message,
            'clarification_needed' => null,
        ];
    }

    protected function today(): string
    {
        return now()->format('Y-m-d');
    }

    protected function getProvider(): ?GeminiProvider
    {
        if ($this->provider !== null) {
            return $this->provider;
        }

        try {
            $this->provider = app(GeminiProvider::class);

            return $this->provider;
        } catch (\Exception $e) {
            return null;
        }
    }
}
// CLAUDE-CHECKPOINT
