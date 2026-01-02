<?php

namespace Modules\Mk\Bitrix\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Bitrix\Models\HubSpotLeadMap;
use Modules\Mk\Bitrix\Models\OutreachEvent;
use Modules\Mk\Bitrix\Models\OutreachSend;
use Modules\Mk\Bitrix\Models\Suppression;
use Modules\Mk\Bitrix\Services\HubSpotApiClient;

/**
 * Postmark Webhook Controller
 *
 * Handles webhook notifications from Postmark email service.
 * Processes delivery, open, click, bounce, and spam complaint events.
 * Syncs events to HubSpot CRM for tracking and updates deal stages.
 *
 * Event flow:
 * - Delivery: Updates send status to delivered, logs note to HubSpot
 * - Open: Updates send status, increments open_count, moves deal to followup_due
 * - Click: Updates send status, increments click_count, moves deal to followup_due
 * - Bounce: Adds to suppression list, moves deal to lost
 * - SpamComplaint: Adds to suppression list, moves deal to lost
 */
class PostmarkWebhookController extends Controller
{
    /**
     * HubSpot API client.
     */
    protected HubSpotApiClient $hubspot;

    /**
     * Create a new controller instance.
     *
     * @param HubSpotApiClient $hubspot
     */
    public function __construct(HubSpotApiClient $hubspot)
    {
        $this->hubspot = $hubspot;
    }

    /**
     * Handle incoming Postmark webhooks
     *
     * POST /webhooks/postmark
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function handle(Request $request): JsonResponse
    {
        $eventType = $request->input('RecordType');
        $messageId = $request->input('MessageID');
        $email = $request->input('Recipient') ?? $request->input('Email');
        $timestamp = $request->input('Timestamp') ?? time();

        // Generate unique event ID for idempotency
        $eventId = $messageId . '-' . $eventType . '-' . $timestamp;

        Log::info('Postmark webhook received', [
            'event_type' => $eventType,
            'message_id' => $messageId,
            'email' => $email,
            'event_id' => $eventId,
        ]);

        // Idempotency check using model
        if (OutreachEvent::where('provider', 'postmark')
            ->where('event_id', $eventId)
            ->exists()
        ) {
            Log::info('Postmark webhook already processed (idempotent)', [
                'event_id' => $eventId,
            ]);

            return response()->json(['status' => 'duplicate']);
        }

        try {
            // Store event using model
            $event = OutreachEvent::create([
                'provider' => 'postmark',
                'event_id' => $eventId,
                'event_type' => $eventType,
                'postmark_message_id' => $messageId,
                'recipient_email' => $email,
                'payload' => $request->all(),
            ]);

            // Find the send record by Postmark message ID
            $send = OutreachSend::findByPostmarkId($messageId);

            // Find HubSpot mapping by email
            $mapping = HubSpotLeadMap::findByEmail($email);

            // Handle by event type
            match ($eventType) {
                'Delivery' => $this->handleDelivery($send, $mapping, $event),
                'Open' => $this->handleOpen($send, $mapping, $event),
                'Click' => $this->handleClick($send, $mapping, $event),
                'Bounce' => $this->handleBounce($send, $mapping, $event, $email),
                'SpamComplaint' => $this->handleSpamComplaint($send, $mapping, $event, $email),
                default => Log::info('Unhandled Postmark event type', ['type' => $eventType]),
            };

            // Mark event as processed
            $event->update(['processed_at' => now()]);

            return response()->json(['status' => 'received', 'event_id' => $event->id]);

        } catch (\Exception $e) {
            Log::error('Postmark webhook processing failed', [
                'error' => $e->getMessage(),
                'message_id' => $messageId,
                'event_type' => $eventType,
            ]);

            // Still return 200 to prevent Postmark from retrying
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Handle delivery event.
     *
     * Updates send status to delivered and logs note to HubSpot contact.
     *
     * @param OutreachSend|null $send
     * @param HubSpotLeadMap|null $mapping
     * @param OutreachEvent $event
     * @return void
     */
    protected function handleDelivery(?OutreachSend $send, ?HubSpotLeadMap $mapping, OutreachEvent $event): void
    {
        // Update send status
        $send?->update([
            'status' => OutreachSend::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        // Log to HubSpot
        if ($mapping?->hubspot_contact_id) {
            try {
                $this->hubspot->createNote(
                    $mapping->hubspot_contact_id,
                    "Email delivered\nPostmark ID: {$event->postmark_message_id}"
                );
            } catch (\Exception $e) {
                Log::error('Failed to log delivery to HubSpot', [
                    'contact_id' => $mapping->hubspot_contact_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Email delivered', [
            'email' => $event->recipient_email,
            'message_id' => $event->postmark_message_id,
        ]);
    }

    /**
     * Handle open event.
     *
     * Updates send status, increments open_count, logs note to HubSpot,
     * and moves deal to followup_due stage for immediate follow-up.
     *
     * @param OutreachSend|null $send
     * @param HubSpotLeadMap|null $mapping
     * @param OutreachEvent $event
     * @return void
     */
    protected function handleOpen(?OutreachSend $send, ?HubSpotLeadMap $mapping, OutreachEvent $event): void
    {
        // Update send status and increment open count
        if ($send) {
            $send->increment('open_count');
            if (!$send->opened_at) {
                $send->update([
                    'status' => OutreachSend::STATUS_OPENED,
                    'opened_at' => now(),
                ]);
            }
        }

        // Log to HubSpot + move deal to followup_due
        if ($mapping) {
            try {
                // Log note to HubSpot contact
                if ($mapping->hubspot_contact_id) {
                    $this->hubspot->createNote(
                        $mapping->hubspot_contact_id,
                        "Email opened\nOpen count: " . ($send?->open_count ?? 1)
                    );
                }

                // Move deal to followup_due so wife sees it (only if currently in emailed stage)
                if ($mapping->hubspot_deal_id && in_array($mapping->deal_stage, ['emailed'])) {
                    $followupStageId = config('hubspot.deal_stages.followup');

                    if ($followupStageId) {
                        $this->hubspot->updateDealStage($mapping->hubspot_deal_id, $followupStageId);

                        // Set next followup date to today - call them!
                        $this->hubspot->updateDeal($mapping->hubspot_deal_id, [
                            'fct_next_followup_date' => now()->format('Y-m-d'),
                        ]);

                        $mapping->update(['deal_stage' => 'followup']);
                    }
                }

                // Update local lead status
                $mapping->outreachLead?->update([
                    'status' => 'followup',
                    'next_followup_at' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to sync email open to HubSpot', [
                    'email' => $event->recipient_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Email opened', [
            'email' => $event->recipient_email,
            'message_id' => $event->postmark_message_id,
            'open_count' => $send?->open_count ?? 1,
        ]);
    }

    /**
     * Handle click event.
     *
     * Updates send status, increments click_count, logs note to HubSpot,
     * creates a task for immediate follow-up, and moves deal to followup_due stage.
     *
     * @param OutreachSend|null $send
     * @param HubSpotLeadMap|null $mapping
     * @param OutreachEvent $event
     * @return void
     */
    protected function handleClick(?OutreachSend $send, ?HubSpotLeadMap $mapping, OutreachEvent $event): void
    {
        $clickedUrl = $event->payload['OriginalLink'] ?? $event->payload['Link'] ?? 'unknown';

        // Update send status and increment click count
        if ($send) {
            $send->increment('click_count');
            if (!$send->clicked_at) {
                $send->update([
                    'status' => OutreachSend::STATUS_CLICKED,
                    'clicked_at' => now(),
                ]);
            }
        }

        // Log to HubSpot + move to followup_due
        if ($mapping) {
            try {
                // Log note to HubSpot contact
                if ($mapping->hubspot_contact_id) {
                    $this->hubspot->createNote(
                        $mapping->hubspot_contact_id,
                        "Email link clicked\nClick count: " . ($send?->click_count ?? 1) .
                        "\nURL: " . $clickedUrl
                    );

                    // Create task "Call today" in HubSpot for immediate follow-up
                    $this->hubspot->createTask(
                        $mapping->hubspot_contact_id,
                        "Call today - Email click from {$event->recipient_email}",
                        "Lead clicked link in outreach email. Follow up immediately.\nClicked URL: {$clickedUrl}",
                        now()->endOfDay()
                    );
                }

                // Move deal to followup_due (from emailed or new_lead stages)
                if ($mapping->hubspot_deal_id && in_array($mapping->deal_stage, ['emailed', 'new', 'new_lead'])) {
                    $followupStageId = config('hubspot.deal_stages.followup');

                    if ($followupStageId) {
                        $this->hubspot->updateDealStage($mapping->hubspot_deal_id, $followupStageId);

                        // Set next followup date to today
                        $this->hubspot->updateDeal($mapping->hubspot_deal_id, [
                            'fct_next_followup_date' => now()->format('Y-m-d'),
                        ]);

                        $mapping->update(['deal_stage' => 'followup']);
                    }
                }

                // Update local lead status
                $mapping->outreachLead?->update([
                    'status' => 'followup',
                    'next_followup_at' => now(),
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to sync email click to HubSpot', [
                    'email' => $event->recipient_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Email link clicked', [
            'email' => $event->recipient_email,
            'message_id' => $event->postmark_message_id,
            'url' => $clickedUrl,
            'click_count' => $send?->click_count ?? 1,
        ]);
    }

    /**
     * Handle bounce event.
     *
     * Adds email to suppression list, updates send status,
     * logs note to HubSpot, and moves deal to lost stage.
     *
     * @param OutreachSend|null $send
     * @param HubSpotLeadMap|null $mapping
     * @param OutreachEvent $event
     * @param string $email
     * @return void
     */
    protected function handleBounce(?OutreachSend $send, ?HubSpotLeadMap $mapping, OutreachEvent $event, string $email): void
    {
        $bounceType = $event->payload['Type'] ?? 'unknown';
        $description = $event->payload['Description'] ?? '';

        // Add to suppression list using model
        Suppression::suppress($email, Suppression::REASON_BOUNCE, Suppression::SOURCE_POSTMARK, [
            'type' => $bounceType,
            'description' => $description,
            'message_id' => $event->postmark_message_id,
        ]);

        // Update send status
        $send?->update(['status' => OutreachSend::STATUS_BOUNCED, 'bounced_at' => now()]);

        // Update HubSpot - move deal to lost
        if ($mapping) {
            try {
                if ($mapping->hubspot_contact_id) {
                    $this->hubspot->createNote(
                        $mapping->hubspot_contact_id,
                        "Email bounced - added to suppression list\nType: {$bounceType}\nDescription: {$description}"
                    );
                }

                if ($mapping->hubspot_deal_id) {
                    $lostStageId = config('hubspot.deal_stages.lost');
                    if ($lostStageId) {
                        $this->hubspot->updateDealStage($mapping->hubspot_deal_id, $lostStageId);
                        $mapping->update(['deal_stage' => 'lost']);
                    }
                }

                // Update local lead status
                $mapping->outreachLead?->update(['status' => 'lost']);

            } catch (\Exception $e) {
                Log::error('Failed to sync email bounce to HubSpot', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::warning('Email bounced', [
            'email' => $email,
            'message_id' => $event->postmark_message_id,
            'bounce_type' => $bounceType,
        ]);
    }

    /**
     * Handle spam complaint event.
     *
     * Adds email to suppression list, updates send status,
     * logs note to HubSpot, and moves deal to lost stage.
     *
     * @param OutreachSend|null $send
     * @param HubSpotLeadMap|null $mapping
     * @param OutreachEvent $event
     * @param string $email
     * @return void
     */
    protected function handleSpamComplaint(?OutreachSend $send, ?HubSpotLeadMap $mapping, OutreachEvent $event, string $email): void
    {
        // Add to suppression list using model
        Suppression::suppress($email, Suppression::TYPE_COMPLAINT, Suppression::SOURCE_POSTMARK, [
            'message_id' => $event->postmark_message_id,
        ]);

        // Update send status
        $send?->update(['status' => 'complained']);

        // Update HubSpot - move deal to lost
        if ($mapping) {
            try {
                if ($mapping->hubspot_contact_id) {
                    $this->hubspot->createNote(
                        $mapping->hubspot_contact_id,
                        "Spam complaint received - added to suppression list"
                    );
                }

                if ($mapping->hubspot_deal_id) {
                    $lostStageId = config('hubspot.deal_stages.lost');
                    if ($lostStageId) {
                        $this->hubspot->updateDealStage($mapping->hubspot_deal_id, $lostStageId);
                        $mapping->update(['deal_stage' => 'lost']);
                    }
                }

                // Update local lead status
                $mapping->outreachLead?->update(['status' => 'lost']);

            } catch (\Exception $e) {
                Log::error('Failed to sync spam complaint to HubSpot', [
                    'email' => $email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::warning('Spam complaint received', [
            'email' => $email,
            'message_id' => $event->postmark_message_id,
        ]);
    }
}

// CLAUDE-CHECKPOINT
