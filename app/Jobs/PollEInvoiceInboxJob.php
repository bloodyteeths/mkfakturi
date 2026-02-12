<?php

namespace App\Jobs;

use App\Models\EInvoice;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * PollEInvoiceInboxJob
 *
 * Queued job for polling the UJP portal inbox for new incoming e-invoices.
 * Fetches incoming invoices from the portal, parses sender information,
 * and creates EInvoice records with direction=inbound, status=RECEIVED.
 *
 * Job workflow:
 * 1. Connect to UJP portal inbox (via efaktura_download tool or API)
 * 2. Fetch list of new incoming invoices
 * 3. For each invoice XML:
 *    a. Parse sender VAT ID and name from UBL XML
 *    b. Check if already imported (by portal_inbox_id)
 *    c. Create EInvoice record with direction=inbound, status=RECEIVED
 * 4. Log success/failure
 *
 * Retry logic:
 * - Max tries: 3
 * - Backoff: [60, 300, 900] seconds (1 min, 5 min, 15 min)
 * - Timeout: 120 seconds per attempt
 * - Queue: 'einvoice'
 *
 * @property int $companyId Company ID to poll for
 * @property int|null $userId User ID who initiated the poll (optional)
 */
class PollEInvoiceInboxJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum number of retry attempts.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Backoff delays in seconds between retries.
     * [1 minute, 5 minutes, 15 minutes]
     *
     * @var array
     */
    public $backoff = [60, 300, 900];

    /**
     * Job execution timeout in seconds.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Company ID to poll for.
     */
    protected int $companyId;

    /**
     * User ID who initiated the poll (optional).
     */
    protected ?int $userId;

    /**
     * Create a new job instance.
     *
     * @param  int  $companyId  Company ID to poll inbox for
     * @param  int|null  $userId  User ID who initiated the poll
     * @return void
     */
    public function __construct(int $companyId, ?int $userId = null)
    {
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->onQueue('einvoice');
    }

    /**
     * Execute the job.
     *
     * Polls the UJP portal inbox and creates EInvoice records
     * for each new incoming invoice found.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        Log::info('PollEInvoiceInboxJob: Starting portal inbox poll', [
            'company_id' => $this->companyId,
            'user_id' => $this->userId,
            'attempt' => $this->attempts(),
        ]);

        try {
            $portalClient = app(\App\Services\EFaktura\EFakturaPortalClient::class);

            $inboxItems = $portalClient->pollInbox();

            $importedCount = 0;
            $skippedCount = 0;

            foreach ($inboxItems as $item) {
                $eInvoice = $this->processInboxItem($item);
                if ($eInvoice) {
                    $importedCount++;
                } else {
                    $skippedCount++;
                }
            }

            Log::info('PollEInvoiceInboxJob: Portal inbox poll completed', [
                'company_id' => $this->companyId,
                'total_items' => count($inboxItems),
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
            ]);

        } catch (Throwable $exception) {
            Log::error('PollEInvoiceInboxJob: Exception during inbox poll', [
                'company_id' => $this->companyId,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        }
    }

    /**
     * Handle job failure after all retries exhausted.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        Log::error('PollEInvoiceInboxJob: Job failed after all retry attempts', [
            'company_id' => $this->companyId,
            'attempts' => $this->attempts(),
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }

    /**
     * Process a single inbox item from the portal.
     *
     * Parses the UBL XML to extract sender information, checks for duplicates,
     * and creates an EInvoice record with direction=inbound.
     *
     * @param  array  $item  Portal inbox item containing xml, portal_id, etc.
     * @return EInvoice|null The created EInvoice or null if already imported
     */
    protected function processInboxItem(array $item): ?EInvoice
    {
        $portalInboxId = $item['portal_id'] ?? null;

        // Check for duplicate import
        if ($portalInboxId) {
            $existing = EInvoice::where('company_id', $this->companyId)
                ->where('portal_inbox_id', $portalInboxId)
                ->first();

            if ($existing) {
                Log::info('PollEInvoiceInboxJob: Skipping duplicate inbox item', [
                    'portal_inbox_id' => $portalInboxId,
                    'existing_e_invoice_id' => $existing->id,
                ]);

                return null;
            }
        }

        // Parse sender info from UBL XML
        $senderInfo = $this->parseSenderFromXml($item['xml'] ?? '');

        // Create inbound EInvoice record
        $eInvoice = EInvoice::create([
            'company_id' => $this->companyId,
            'invoice_id' => null, // Inbound invoices may not have a local invoice yet
            'ubl_xml' => $item['xml'] ?? null,
            'status' => EInvoice::STATUS_RECEIVED,
            'direction' => 'inbound',
            'sender_vat_id' => $senderInfo['vat_id'] ?? null,
            'sender_name' => $senderInfo['name'] ?? null,
            'portal_inbox_id' => $portalInboxId,
            'received_at' => now(),
        ]);

        Log::info('PollEInvoiceInboxJob: Inbound e-invoice created', [
            'e_invoice_id' => $eInvoice->id,
            'company_id' => $this->companyId,
            'sender_vat_id' => $senderInfo['vat_id'] ?? null,
            'sender_name' => $senderInfo['name'] ?? null,
            'portal_inbox_id' => $portalInboxId,
        ]);

        return $eInvoice;
    }

    /**
     * Parse sender information from UBL XML content.
     *
     * Extracts the supplier (sender) name and VAT ID from UBL 2.1 XML.
     *
     * @param  string  $xml  UBL XML content
     * @return array{name: string|null, vat_id: string|null}
     */
    protected function parseSenderFromXml(string $xml): array
    {
        $result = [
            'name' => null,
            'vat_id' => null,
        ];

        if (empty($xml)) {
            return $result;
        }

        try {
            $doc = new \DOMDocument;
            $doc->loadXML($xml);

            // Extract supplier name
            $supplierNames = $doc->getElementsByTagName('AccountingSupplierParty');
            if ($supplierNames->length > 0) {
                $supplierNode = $supplierNames->item(0);
                $partyNames = $supplierNode->getElementsByTagName('Name');
                if ($partyNames->length > 0) {
                    $result['name'] = $partyNames->item(0)->nodeValue;
                }

                // Extract VAT ID (CompanyID under PartyTaxScheme)
                $companyIds = $supplierNode->getElementsByTagName('CompanyID');
                if ($companyIds->length > 0) {
                    $result['vat_id'] = $companyIds->item(0)->nodeValue;
                }
            }
        } catch (Exception $e) {
            Log::warning('PollEInvoiceInboxJob: Failed to parse sender info from XML', [
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }
}

// CLAUDE-CHECKPOINT
