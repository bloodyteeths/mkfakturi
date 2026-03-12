<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessInboundBillEmail;
use App\Models\CompanyInboundAlias;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InboundMailController extends Controller
{
    /**
     * Handle inbound email webhook from Postmark.
     *
     * Postmark sends JSON with base64-encoded attachments, not multipart uploads.
     * @see https://postmarkapp.com/developer/webhooks/inbound-webhook
     */
    public function handle(Request $request): Response
    {
        if (! $this->authenticateWebhook($request)) {
            return response('Unauthorized', 401);
        }

        $from = $request->input('From') ?? $request->input('from');
        $subject = $request->input('Subject') ?? $request->input('subject');
        $recipients = $this->extractRecipients($request);

        if (empty($recipients) || ! $from) {
            Log::warning('Inbound email missing to/from', [
                'from' => $from,
                'recipients' => $recipients,
            ]);

            return response('Missing to/from', 200);
        }

        $alias = $this->findAlias($recipients);

        if (! $alias) {
            Log::warning('Inbound email alias not found', [
                'recipients' => $recipients,
            ]);

            return response('Alias not found', 200);
        }

        $attachments = $request->input('Attachments') ?? $request->input('attachments') ?? [];
        $validAttachments = $this->processAttachments($attachments, $alias->company_id);

        if (empty($validAttachments)) {
            Log::info('Inbound email received with no valid PDF attachments', [
                'company_id' => $alias->company_id,
                'from' => $from,
            ]);

            return response('No valid attachments', 200);
        }

        ProcessInboundBillEmail::dispatch(
            $alias->company_id,
            $from,
            $subject,
            $validAttachments
        );

        Log::info('Inbound email accepted', [
            'company_id' => $alias->company_id,
            'from' => $from,
            'attachment_count' => count($validAttachments),
        ]);

        return response('Inbound email accepted', 200);
    }

    /**
     * Validate webhook auth token.
     *
     * Postmark doesn't send auth headers on inbound webhooks,
     * so the token is included as a query parameter in the webhook URL.
     */
    protected function authenticateWebhook(Request $request): bool
    {
        $token = config('services.postmark_inbound.token');

        // Skip auth if no token configured (development)
        if (empty($token)) {
            return true;
        }

        // Check query parameter (Postmark inbound webhook URL includes ?token=)
        $queryToken = $request->query('token');
        if ($queryToken) {
            return hash_equals($token, $queryToken);
        }

        // Fallback: check Authorization header (for manual testing)
        $authHeader = $request->header('Authorization');
        if ($authHeader && Str::startsWith($authHeader, 'Basic ')) {
            $provided = Str::after($authHeader, 'Basic ');

            return hash_equals($token, $provided);
        }

        return false;
    }

    /**
     * Extract recipient email addresses from Postmark payload.
     *
     * @return array<string>
     */
    protected function extractRecipients(Request $request): array
    {
        $emails = [];

        // Prefer ToFull (structured) over To (string)
        $toFull = $request->input('ToFull');
        if (is_array($toFull)) {
            foreach ($toFull as $recipient) {
                if (isset($recipient['Email'])) {
                    $emails[] = $recipient['Email'];
                }
            }
        }

        if (empty($emails)) {
            $to = $request->input('To') ?? $request->input('to');
            if (is_string($to)) {
                // Postmark To can be comma-separated
                $emails = array_map('trim', explode(',', $to));
            } elseif (is_array($to)) {
                $emails = $to;
            }
        }

        return array_filter($emails);
    }

    /**
     * Find a matching company alias from the list of recipients.
     */
    protected function findAlias(array $recipients): ?CompanyInboundAlias
    {
        foreach ($recipients as $email) {
            $localPart = Str::before($email, '@');
            $alias = CompanyInboundAlias::where('alias', $localPart)->first();

            if ($alias) {
                return $alias;
            }
        }

        return null;
    }

    /**
     * Process Postmark base64-encoded attachments.
     *
     * @param  array  $attachments  Postmark Attachments array
     * @return array<int, array{path: string, original_name: string}>
     */
    protected function processAttachments(array $attachments, int $companyId): array
    {
        $valid = [];
        // Use env() directly — config('filesystems.default') is unreliable
        // because FileDisk::setFilesystem() overrides it to 'temp_local'.
        $disk = env('FILESYSTEM_DISK', 'public');

        foreach ($attachments as $attachment) {
            $contentType = $attachment['ContentType'] ?? $attachment['content_type'] ?? null;
            $name = $attachment['Name'] ?? $attachment['name'] ?? 'unknown.pdf';
            $content = $attachment['Content'] ?? $attachment['content'] ?? null;

            if ($contentType !== 'application/pdf') {
                Log::info('Inbound email attachment skipped (non-pdf)', [
                    'mime' => $contentType,
                    'name' => $name,
                ]);

                continue;
            }

            if (empty($content)) {
                Log::warning('Inbound email attachment has no content', ['name' => $name]);

                continue;
            }

            $decoded = base64_decode($content, true);
            if ($decoded === false) {
                Log::warning('Inbound email attachment base64 decode failed', ['name' => $name]);

                continue;
            }

            $storagePath = 'inbound-bills/'.$companyId.'/'.Str::uuid().'_'.$name;
            $putResult = Storage::disk($disk)->put($storagePath, $decoded);

            if (! $putResult) {
                Log::error('Inbound email: Storage::put() failed', [
                    'disk' => $disk,
                    'path' => $storagePath,
                    'name' => $name,
                    'size' => strlen($decoded),
                    'company_id' => $companyId,
                ]);

                continue;
            }

            Log::info('Inbound email attachment stored', [
                'disk' => $disk,
                'path' => $storagePath,
                'size' => strlen($decoded),
            ]);

            $valid[] = [
                'path' => $storagePath,
                'original_name' => $name,
            ];
        }

        return $valid;
    }
}
// CLAUDE-CHECKPOINT
