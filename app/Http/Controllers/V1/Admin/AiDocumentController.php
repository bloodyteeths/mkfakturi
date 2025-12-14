<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AiInsightsService;
use App\Services\McpDataProvider;
use App\Services\PdfImageConverter;
use App\Services\UsageLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * AI Document Analysis Controller
 *
 * Handles API endpoints for AI-powered document analysis including
 * PDFs, receipts, and invoices using vision APIs.
 */
class AiDocumentController extends Controller
{
    /**
     * Maximum file size in bytes (10MB)
     */
    private const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Supported file types
     */
    private const SUPPORTED_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
        'image/webp',
    ];

    /**
     * Create a new controller instance
     */
    public function __construct(
        private AiInsightsService $aiService,
        private PdfImageConverter $pdfConverter,
        private McpDataProvider $dataProvider
    ) {}

    /**
     * Analyze any document or image with AI vision
     *
     * POST /api/v1/ai/analyze-document
     */
    public function analyzeDocument(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        // Check if PDF analysis is enabled
        if (! $this->isPdfAnalysisEnabled($company)) {
            return response()->json([
                'error' => 'PDF analysis feature is not enabled',
                'message' => 'Please enable the pdf_analysis feature flag in settings',
            ], 403);
        }

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month'),
                403
            );
        }

        // Validate request
        $validated = $request->validate([
            'file' => 'required|file|max:'.(self::MAX_FILE_SIZE / 1024),
            'question' => 'nullable|string|max:500',
        ]);

        try {
            $file = $request->file('file');
            $question = $validated['question'] ?? 'Analyze this document and provide key information.';

            // Validate file type
            if (! in_array($file->getMimeType(), self::SUPPORTED_TYPES)) {
                return response()->json([
                    'error' => 'Unsupported file type',
                    'supported_types' => self::SUPPORTED_TYPES,
                ], 422);
            }

            // Validate file size
            if ($file->getSize() > self::MAX_FILE_SIZE) {
                return response()->json([
                    'error' => 'File too large',
                    'max_size_mb' => self::MAX_FILE_SIZE / 1024 / 1024,
                ], 422);
            }

            Log::info('[AiDocumentController] Document analysis started', [
                'company_id' => $company->id,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'has_question' => ! empty($validated['question']),
            ]);

            // Process document
            $analysis = $this->processDocument($file, $question, $company);

            // Increment usage after successful AI call
            $usageService->incrementUsage($company, 'ai_queries_per_month');

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'file_type' => $file->getMimeType(),
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            Log::error('[AiDocumentController] Document analysis failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze document',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze receipt image/PDF and extract structured data
     *
     * POST /api/v1/ai/analyze-receipt
     */
    public function analyzeReceipt(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('create expense', $company);

        // Check if PDF analysis is enabled
        if (! $this->isPdfAnalysisEnabled($company)) {
            return response()->json([
                'error' => 'PDF analysis feature is not enabled',
                'message' => 'Please enable the pdf_analysis feature flag in settings',
            ], 403);
        }

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month'),
                403
            );
        }

        // Validate request
        $validated = $request->validate([
            'file' => 'required|file|max:'.(self::MAX_FILE_SIZE / 1024),
        ]);

        try {
            $file = $request->file('file');

            // Validate file type
            if (! in_array($file->getMimeType(), self::SUPPORTED_TYPES)) {
                return response()->json([
                    'error' => 'Unsupported file type',
                    'supported_types' => self::SUPPORTED_TYPES,
                ], 422);
            }

            Log::info('[AiDocumentController] Receipt analysis started', [
                'company_id' => $company->id,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            // Build receipt extraction prompt
            $prompt = $this->buildReceiptExtractionPrompt($company);

            // Process document
            $analysis = $this->processDocument($file, $prompt, $company);

            // Parse structured data from response
            $extractedData = $this->parseReceiptData($analysis);

            // Increment usage after successful AI call
            $usageService->incrementUsage($company, 'ai_queries_per_month');

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'extracted_data' => $extractedData,
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            Log::error('[AiDocumentController] Receipt analysis failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to analyze receipt',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze invoice image/PDF and extract structured data
     *
     * POST /api/v1/ai/extract-invoice
     */
    public function extractInvoice(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('create invoice', $company);

        // Check if PDF analysis is enabled
        if (! $this->isPdfAnalysisEnabled($company)) {
            return response()->json([
                'error' => 'PDF analysis feature is not enabled',
                'message' => 'Please enable the pdf_analysis feature flag in settings',
            ], 403);
        }

        // Check usage limit for AI queries
        $usageService = app(UsageLimitService::class);
        if (! $usageService->canUse($company, 'ai_queries_per_month')) {
            return response()->json(
                $usageService->buildLimitExceededResponse($company, 'ai_queries_per_month'),
                403
            );
        }

        // Validate request
        $validated = $request->validate([
            'file' => 'required|file|max:'.(self::MAX_FILE_SIZE / 1024),
        ]);

        try {
            $file = $request->file('file');

            // Validate file type
            if (! in_array($file->getMimeType(), self::SUPPORTED_TYPES)) {
                return response()->json([
                    'error' => 'Unsupported file type',
                    'supported_types' => self::SUPPORTED_TYPES,
                ], 422);
            }

            Log::info('[AiDocumentController] Invoice extraction started', [
                'company_id' => $company->id,
                'file_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            // Build invoice extraction prompt
            $prompt = $this->buildInvoiceExtractionPrompt($company);

            // Process document
            $analysis = $this->processDocument($file, $prompt, $company);

            // Parse structured data from response
            $extractedData = $this->parseInvoiceData($analysis);

            // Increment usage after successful AI call
            $usageService->incrementUsage($company, 'ai_queries_per_month');

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'extracted_data' => $extractedData,
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            Log::error('[AiDocumentController] Invoice extraction failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to extract invoice data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get monthly trends data for chart generation
     *
     * GET /api/v1/ai/monthly-trends
     */
    public function monthlyTrends(Request $request): JsonResponse
    {
        $company = Company::find($request->header('company'));

        if (! $company) {
            return response()->json([
                'error' => 'Company not found',
            ], 404);
        }

        $this->authorize('view dashboard', $company);

        // Validate request
        $validated = $request->validate([
            'months' => 'nullable|integer|min:1|max:24',
        ]);

        try {
            $months = $validated['months'] ?? 12;

            Log::info('[AiDocumentController] Monthly trends requested', [
                'company_id' => $company->id,
                'months' => $months,
            ]);

            $trends = $this->dataProvider->getMonthlyTrends($company, $months);

            return response()->json([
                'success' => true,
                'trends' => $trends,
                'months' => $months,
                'timestamp' => now()->toDateTimeString(),
            ]);

        } catch (\Exception $e) {
            Log::error('[AiDocumentController] Failed to get monthly trends', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve monthly trends',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process document (PDF or image) and get AI analysis
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string AI analysis response
     *
     * @throws \Exception
     */
    private function processDocument($file, string $prompt, Company $company): string
    {
        $mimeType = $file->getMimeType();

        // If it's a PDF, convert to images first
        if ($mimeType === 'application/pdf') {
            return $this->processPdf($file, $prompt);
        }

        // It's already an image, process directly
        return $this->processImage($file, $prompt);
    }

    /**
     * Process PDF by converting to images and analyzing
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string AI analysis response
     *
     * @throws \Exception
     */
    private function processPdf($file, string $prompt): string
    {
        // Save PDF temporarily
        $tempPath = $file->store('temp/pdf-analysis');
        $fullPath = Storage::path($tempPath);

        try {
            // Convert PDF to images
            $images = $this->pdfConverter->convertToImages($fullPath);

            if (empty($images)) {
                throw new \Exception('PDF conversion produced no images');
            }

            Log::info('[AiDocumentController] PDF converted to images', [
                'page_count' => count($images),
            ]);

            // Use AI provider to analyze document
            $aiProvider = $this->resolveAiProvider();
            $response = $aiProvider->analyzeDocument($images, $prompt);

            return $response;

        } finally {
            // Clean up temp file
            Storage::delete($tempPath);
        }
    }

    /**
     * Process image file directly
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string AI analysis response
     *
     * @throws \Exception
     */
    private function processImage($file, string $prompt): string
    {
        // Read image data
        $imageData = base64_encode(file_get_contents($file->getRealPath()));
        $mediaType = $file->getMimeType();

        Log::info('[AiDocumentController] Processing image', [
            'media_type' => $mediaType,
            'size_bytes' => strlen($imageData),
        ]);

        // Use AI provider to analyze image
        $aiProvider = $this->resolveAiProvider();
        $response = $aiProvider->analyzeImage($imageData, $mediaType, $prompt);

        return $response;
    }

    /**
     * Build receipt extraction prompt
     */
    private function buildReceiptExtractionPrompt(Company $company): string
    {
        $currency = $company->currency ?? 'MKD';

        return <<<PROMPT
Analyze this receipt image and extract the following information in JSON format:

{
  "vendor": "Vendor/merchant name",
  "date": "Date in YYYY-MM-DD format",
  "total_amount": 123.45,
  "currency": "{$currency}",
  "tax_amount": 12.34,
  "items": [
    {
      "description": "Item description",
      "quantity": 1,
      "unit_price": 100.00,
      "total": 100.00
    }
  ],
  "payment_method": "Cash/Card/Other",
  "notes": "Any additional notes or observations"
}

If any field cannot be determined from the image, use null.
Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Build invoice extraction prompt
     */
    private function buildInvoiceExtractionPrompt(Company $company): string
    {
        $currency = $company->currency ?? 'MKD';

        return <<<PROMPT
Analyze this invoice image and extract the following information in JSON format:

{
  "invoice_number": "Invoice number",
  "customer_name": "Customer/client name",
  "customer_email": "customer@example.com",
  "customer_phone": "Phone number",
  "customer_address": "Full address",
  "invoice_date": "Date in YYYY-MM-DD format",
  "due_date": "Due date in YYYY-MM-DD format",
  "currency": "{$currency}",
  "items": [
    {
      "description": "Item/service description",
      "quantity": 1,
      "unit_price": 100.00,
      "tax_rate": 18.0,
      "total": 118.00
    }
  ],
  "subtotal": 100.00,
  "tax_total": 18.00,
  "total_amount": 118.00,
  "notes": "Any payment terms or notes",
  "payment_terms": "Payment terms if specified"
}

If any field cannot be determined from the image, use null.
Return ONLY valid JSON, no additional text.
PROMPT;
    }

    /**
     * Parse receipt data from AI response
     *
     * @return array<string, mixed>
     */
    private function parseReceiptData(string $response): array
    {
        try {
            // Extract JSON from response
            $jsonMatch = [];
            if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
                $response = $jsonMatch[0];
            }

            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data)) {
                throw new \RuntimeException('Invalid JSON structure');
            }

            return $data;

        } catch (\JsonException $e) {
            Log::warning('[AiDocumentController] Failed to parse receipt JSON', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            // Return empty structure
            return [
                'vendor' => null,
                'date' => null,
                'total_amount' => null,
                'currency' => null,
                'tax_amount' => null,
                'items' => [],
                'payment_method' => null,
                'notes' => null,
            ];
        }
    }

    /**
     * Parse invoice data from AI response
     *
     * @return array<string, mixed>
     */
    private function parseInvoiceData(string $response): array
    {
        try {
            // Extract JSON from response
            $jsonMatch = [];
            if (preg_match('/\{[\s\S]*\}/', $response, $jsonMatch)) {
                $response = $jsonMatch[0];
            }

            $data = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

            if (! is_array($data)) {
                throw new \RuntimeException('Invalid JSON structure');
            }

            return $data;

        } catch (\JsonException $e) {
            Log::warning('[AiDocumentController] Failed to parse invoice JSON', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            // Return empty structure
            return [
                'invoice_number' => null,
                'customer_name' => null,
                'customer_email' => null,
                'customer_phone' => null,
                'customer_address' => null,
                'invoice_date' => null,
                'due_date' => null,
                'currency' => null,
                'items' => [],
                'subtotal' => null,
                'tax_total' => null,
                'total_amount' => null,
                'notes' => null,
                'payment_terms' => null,
            ];
        }
    }

    /**
     * Check if PDF analysis feature is enabled
     */
    private function isPdfAnalysisEnabled(Company $company): bool
    {
        // Check feature flag
        $featureFlag = \App\Models\CompanySetting::where('company_id', $company->id)
            ->where('option', 'pdf_analysis')
            ->first();

        return $featureFlag && $featureFlag->value === '1';
    }

    /**
     * Resolve the AI provider instance
     *
     * @return \App\Services\AiProvider\AiProviderInterface
     *
     * @throws \Exception
     */
    private function resolveAiProvider()
    {
        $provider = strtolower((string) config('ai.default_provider', 'claude'));

        return match ($provider) {
            'claude' => new \App\Services\AiProvider\ClaudeProvider,
            'openai' => new \App\Services\AiProvider\OpenAiProvider,
            'gemini' => new \App\Services\AiProvider\GeminiProvider,
            default => throw new \RuntimeException("Unsupported AI provider: {$provider}"),
        };
    }
}

// CLAUDE-CHECKPOINT
