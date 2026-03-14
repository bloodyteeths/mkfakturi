<?php

namespace App\Services;

use App\Models\AiDraft;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use App\Services\AiProvider\GeminiProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * AI Natural Language Accounting Service
 *
 * Processes natural-language commands like "Фактура за Марков, 10 часа по 3000"
 * into structured draft entities (invoice, bill, expense, payment).
 *
 * Flow:
 *   1. User types in AiChatWidget
 *   2. Gemini classifies intent + extracts structured data
 *   3. Service fuzzy-matches customer/supplier names
 *   4. Creates AiDraft record → returns draft_id + redirect URL
 *   5. User lands on pre-filled form to review & submit
 *
 * AI suggests, human approves — never auto-posts.
 */
class AiNaturalLanguageService
{
    private ?GeminiProvider $provider = null;

    /**
     * Process a natural language command.
     *
     * @return array{intent: string, draft_id: ?int, redirect_url: ?string, message: string, clarification_needed: ?string}
     */
    public function process(string $input, Company $company, User $user): array
    {
        try {
            $provider = $this->getProvider();
            if (! $provider) {
                return $this->textResponse('AI provider is not available. Please try again later.');
            }

            $prompt = $this->buildPrompt($input, $company);

            $response = $provider->generate($prompt, [
                'temperature' => 0.1,
                'max_tokens' => 600,
            ]);

            // Track usage
            try {
                app(UsageLimitService::class)->incrementUsage($company, 'ai_queries_per_month');
            } catch (\Exception $e) {
                // Non-critical
            }

            $parsed = $this->parseResponse($response);
            if (! $parsed) {
                return $this->textResponse('I couldn\'t understand that request. Try something like "Invoice for Марков, 10 hours consulting at 3000 den".');
            }

            $intent = $parsed['intent'] ?? 'question';

            // If it's a question, return the answer directly
            if ($intent === 'question') {
                return $this->textResponse($parsed['answer'] ?? 'I\'m not sure how to answer that. Try asking about creating invoices, bills, or expenses.');
            }

            // If clarification is needed
            if (! empty($parsed['clarification_needed'])) {
                return [
                    'intent' => $intent,
                    'draft_id' => null,
                    'redirect_url' => null,
                    'message' => $parsed['clarification_needed'],
                    'clarification_needed' => $parsed['clarification_needed'],
                ];
            }

            // Validate entity type
            if (! in_array($intent, ['create_invoice', 'create_bill', 'create_expense', 'record_payment'])) {
                return $this->textResponse($parsed['answer'] ?? 'I can help with creating invoices, bills, expenses, or recording payments.');
            }

            // Extract entities and resolve names
            $entities = $parsed['entities'] ?? [];
            $entityType = $this->intentToEntityType($intent);
            $resolvedData = $this->resolveEntities($entities, $company, $entityType);

            // Create draft
            $draft = AiDraft::create([
                'company_id' => $company->id,
                'user_id' => $user->id,
                'entity_type' => $entityType,
                'entity_data' => $resolvedData,
                'status' => AiDraft::STATUS_PENDING,
                'expires_at' => now()->addHour(),
            ]);

            $redirectUrl = $this->getRedirectUrl($entityType, $draft->id);
            $message = $this->buildConfirmationMessage($entityType, $resolvedData, $parsed['confidence'] ?? 0.9);

            return [
                'intent' => $intent,
                'draft_id' => $draft->id,
                'redirect_url' => $redirectUrl,
                'message' => $message,
                'clarification_needed' => null,
            ];
        } catch (\Exception $e) {
            Log::warning('[NLAssistant] Processing failed', [
                'input' => substr($input, 0, 200),
                'error' => $e->getMessage(),
            ]);

            return $this->textResponse('Something went wrong processing your request. Please try again.');
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
     */
    protected function buildPrompt(string $input, Company $company): string
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

        $customersJson = json_encode($customers, JSON_UNESCAPED_UNICODE);
        $suppliersJson = json_encode($suppliers, JSON_UNESCAPED_UNICODE);
        $itemsJson = json_encode($items, JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
You are a Macedonian accounting assistant for company "{$company->name}" (currency: {$currency}).
All amounts are in the smallest unit (cents/deni). 1 MKD = 100 deni. A price of "3000 ден" = 300000 in cents.

Known customers: {$customersJson}
Known suppliers: {$suppliersJson}
Known items (prices in cents): {$itemsJson}

User said: "{$input}"

Classify intent and extract structured data. Return ONLY valid JSON (no markdown):
{{
  "intent": "create_invoice|create_bill|create_expense|record_payment|question",
  "entities": {{
    "customer_id": null or number (from known customers),
    "customer_name": "string" (if new or unresolved),
    "supplier_id": null or number (from known suppliers),
    "supplier_name": "string" (if new or unresolved),
    "items": [
      {{"name": "string", "description": "string or null", "quantity": number, "unit_price": number_in_cents}}
    ],
    "amount": number_in_cents (for expense/payment),
    "date": "YYYY-MM-DD" (default today if not specified),
    "due_date": "YYYY-MM-DD or null",
    "notes": "string or null",
    "category": "string or null" (for expenses: office_supplies, travel, rent, utilities, etc),
    "invoice_reference": "string or null" (for payments: which invoice number)
  }},
  "confidence": 0.0-1.0,
  "clarification_needed": null or "Дали мислевте на Марков ДОО или Марков ДООЕЛ?",
  "answer": null or "text response for questions"
}}

Rules:
- Match customer/supplier names fuzzy (handle Cyrillic/Latin, ДООЕЛ/ДОО variations)
- If customer_id/supplier_id can be resolved from known list, include it; otherwise use name only
- "фактура" = create_invoice, "сметка"/"фактура од" = create_bill, "трошок"/"платив" = create_expense, "примив"/"уплата" = record_payment
- Questions about revenue/profit/balance = "question" intent with answer
- All monetary amounts MUST be in cents (multiply visible amounts by 100)
- Default date is today: {$this->today()}
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

        // Resolve customer for invoices
        if (in_array($entityType, [AiDraft::ENTITY_INVOICE, AiDraft::ENTITY_PAYMENT])) {
            if (empty($resolved['customer_id']) && ! empty($resolved['customer_name'])) {
                $customer = $this->fuzzyFindCustomer($resolved['customer_name'], $company->id);
                if ($customer) {
                    $resolved['customer_id'] = $customer->id;
                    $resolved['customer_name'] = $customer->name;
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
                }
            }
        }

        // Resolve item IDs
        if (! empty($resolved['items'])) {
            foreach ($resolved['items'] as $i => $item) {
                if (empty($item['item_id']) && ! empty($item['name'])) {
                    $found = DB::table('items')
                        ->where('company_id', $company->id)
                        ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($item['name']) . '%'])
                        ->first();
                    if ($found) {
                        $resolved['items'][$i]['item_id'] = $found->id;
                        $resolved['items'][$i]['name'] = $found->name;
                    }
                }
            }
        }

        return $resolved;
    }

    /**
     * Fuzzy-find a customer by name.
     */
    protected function fuzzyFindCustomer(string $name, int $companyId): ?Customer
    {
        // Exact match first
        $customer = Customer::where('company_id', $companyId)
            ->where('name', $name)
            ->first();

        if ($customer) {
            return $customer;
        }

        // Partial match (LIKE is case-insensitive in SQLite, case-sensitive in MySQL with utf8mb4)
        $customer = Customer::where('company_id', $companyId)
            ->where('name', 'LIKE', '%' . $name . '%')
            ->first();

        if ($customer) {
            return $customer;
        }

        // Try mb_strtolower for MySQL compatibility
        return Customer::where('company_id', $companyId)
            ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($name) . '%'])
            ->first();
    }

    /**
     * Fuzzy-find a supplier by name.
     */
    protected function fuzzyFindSupplier(string $name, int $companyId): ?object
    {
        // Exact match first
        $supplier = DB::table('suppliers')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('name', $name)
            ->first();

        if ($supplier) {
            return $supplier;
        }

        // LIKE match (case-insensitive in SQLite — needed for Cyrillic which LOWER() can't handle)
        $supplier = DB::table('suppliers')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('name', 'LIKE', '%' . $name . '%')
            ->first();

        if ($supplier) {
            return $supplier;
        }

        // LOWER() fallback for MySQL compatibility
        return DB::table('suppliers')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($name) . '%'])
            ->first();
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
        ];

        $base = $routes[$entityType] ?? '/admin/invoices/create';

        return "{$base}?draft_id={$draftId}";
    }

    /**
     * Build a human-readable confirmation message.
     */
    protected function buildConfirmationMessage(string $entityType, array $data, float $confidence): string
    {
        $typeLabels = [
            AiDraft::ENTITY_INVOICE => 'фактура',
            AiDraft::ENTITY_BILL => 'сметка',
            AiDraft::ENTITY_EXPENSE => 'трошок',
            AiDraft::ENTITY_PAYMENT => 'уплата',
        ];

        $label = $typeLabels[$entityType] ?? $entityType;
        $name = $data['customer_name'] ?? $data['supplier_name'] ?? '';

        $parts = ["Ќе креирам **{$label}**"];

        if ($name) {
            $parts[0] .= " за **{$name}**";
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
            $parts[] = "{$itemCount} ставки, вкупно **{$totalDisplay} ден**";
        } elseif (! empty($data['amount'])) {
            $amountDisplay = number_format($data['amount'] / 100, 2, ',', '.');
            $parts[] = "износ **{$amountDisplay} ден**";
        }

        return implode(' — ', $parts) . '.';
    }

    protected function textResponse(string $message): array
    {
        return [
            'intent' => 'question',
            'draft_id' => null,
            'redirect_url' => null,
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
