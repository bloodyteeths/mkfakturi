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
    public function handle(Request $request): Response
    {
        $to = $request->input('to');
        $from = $request->input('from');
        $subject = $request->input('subject');

        if (! $to || ! $from) {
            Log::warning('Inbound email missing to/from', [
                'to' => $to,
                'from' => $from,
            ]);

            return response('Missing to/from', 400);
        }

        $recipient = is_array($to) ? ($to[0] ?? null) : $to;
        if (! is_string($recipient)) {
            Log::warning('Inbound email invalid recipient format', ['to' => $to]);

            return response('Invalid recipient', 400);
        }

        $localPart = Str::before($recipient, '@');

        $alias = CompanyInboundAlias::where('alias', $localPart)->first();

        if (! $alias) {
            Log::warning('Inbound email alias not found', [
                'recipient' => $recipient,
                'local_part' => $localPart,
            ]);

            // Return 200 to avoid repeated retries from provider
            return response('Alias not found', 200);
        }

        $files = $request->file('attachments', []);
        $validAttachments = [];

        foreach ($files as $file) {
            if (! $file->isValid()) {
                continue;
            }

            $mime = $file->getClientMimeType();

            if ($mime !== 'application/pdf') {
                Log::info('Inbound email attachment skipped (non-pdf)', [
                    'mime' => $mime,
                    'name' => $file->getClientOriginalName(),
                ]);
                continue;
            }

            $path = $file->store('inbound-bills/'.$alias->company_id, [
                'disk' => config('filesystems.default', 'local'),
            ]);

            $validAttachments[] = [
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
            ];
        }

        if (empty($validAttachments)) {
            Log::info('Inbound email received with no valid PDF attachments', [
                'recipient' => $recipient,
            ]);

            return response('No valid attachments', 200);
        }

        ProcessInboundBillEmail::dispatch(
            $alias->company_id,
            $from,
            $subject,
            $validAttachments
        );

        return response('Inbound email accepted', 200);
    }
}

