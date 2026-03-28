<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportContactRequest;
use App\Mail\SupportContactConfirmation;
use App\Mail\SupportContactNotification;
use App\Models\SupportContact;
use App\Models\SupportContactReply;
use App\Services\ClawdNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class SupportContactController extends Controller
{
    /**
     * Store a new support contact submission.
     */
    public function store(SupportContactRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        // Add user and company IDs if authenticated
        if ($user) {
            $data['user_id'] = $user->id;
            $data['company_id'] = $user->company_id ?? null;
        }

        // Handle file attachments
        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('support-attachments', 'local');
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'size' => $this->formatFileSize($file->getSize()),
                    'mime_type' => $file->getMimeType(),
                ];
            }
            $data['attachments'] = $attachments;
        }

        // Create the support contact
        $contact = SupportContact::create($data);

        // Send notification email to support team
        try {
            Mail::to(config('support.email'))
                ->send(new SupportContactNotification($contact));
        } catch (\Exception $e) {
            \Log::error('Failed to send support notification email: '.$e->getMessage());
        }

        // Send confirmation email to user
        try {
            Mail::to($contact->email)
                ->send(new SupportContactConfirmation(
                    $contact,
                    config('support.response_time_hours', 48),
                    url('/support')
                ));
        } catch (\Exception $e) {
            \Log::error('Failed to send support confirmation email: '.$e->getMessage());
        }

        // Notify Clawd AI assistant in real-time
        ClawdNotifier::push('support_contact', [
            'email' => $contact->email,
            'name' => $contact->name,
            'subject' => $contact->subject,
            'category' => $contact->category,
            'priority' => $contact->priority,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your support inquiry has been submitted successfully. We will get back to you soon.',
            'reference_number' => $contact->reference_number,
            'data' => [
                'id' => $contact->id,
                'reference_number' => $contact->reference_number,
                'status' => $contact->status,
                'created_at' => $contact->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Display a listing of support contacts (Admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = SupportContact::query()
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc');

        $contacts = $query->paginate($request->get('per_page', 15));

        return response()->json($contacts);
    }

    /**
     * User closes their own ticket.
     */
    public function close(Request $request, SupportContact $supportContact): JsonResponse
    {
        $user = $request->user();
        if ((int) $supportContact->user_id !== (int) $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $supportContact->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ticket closed.',
            'data' => $supportContact,
        ]);
    }

    /**
     * Admin listing of ALL support contacts (cross-tenant).
     */
    public function indexAll(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $sortBy = in_array($request->get('sort_by'), ['id', 'created_at', 'subject', 'name', 'category', 'priority', 'status', 'assigned_to']) ? $request->get('sort_by') : 'created_at';
        $sortOrder = $request->get('sort_order') === 'asc' ? 'asc' : 'desc';

        $query = SupportContact::query()
            ->with(['user', 'company', 'assignedTo'])
            ->orderBy($sortBy, $sortOrder);

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
                // Search by ID for reference number (SUP-000123 → 123)
                if (preg_match('/SUP-?0*(\d+)/i', $search, $m)) {
                    $q->orWhere('id', (int) $m[1]);
                }
            });
        }

        $contacts = $query->paginate($request->get('per_page', 25));

        return response()->json($contacts);
    }

    /**
     * Display the specified support contact.
     */
    public function show(SupportContact $supportContact): JsonResponse
    {
        $supportContact->load(['user', 'company']);

        return response()->json([
            'success' => true,
            'data' => $supportContact,
        ]);
    }

    /**
     * Update the status of a support contact.
     */
    public function updateStatus(Request $request, SupportContact $supportContact): JsonResponse
    {
        $request->validate([
            'status' => 'required|in:new,in_progress,resolved',
        ]);

        $supportContact->update([
            'status' => $request->status,
            'resolved_at' => $request->status === 'resolved' ? now() : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.',
            'data' => $supportContact,
        ]);
    }

    /**
     * Admin reply to a support contact — saves reply and emails the user.
     */
    public function reply(Request $request, SupportContact $supportContact): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'reply' => 'required|string|min:5|max:5000',
        ]);

        $supportContact->update([
            'admin_reply' => $request->reply,
            'admin_replied_at' => now(),
            'admin_user_id' => $user->id,
            'status' => 'in_progress',
        ]);

        // Send reply email to the user
        try {
            Mail::to($supportContact->email)
                ->send(new \App\Mail\SupportContactReply($supportContact));
        } catch (\Exception $e) {
            \Log::error('Failed to send support reply email: '.$e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply sent successfully.',
            'data' => $supportContact,
        ]);
    }

    /**
     * Download a support contact attachment (admin only).
     */
    public function downloadAttachment(Request $request, SupportContact $supportContact, int $index)
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $attachments = $supportContact->attachments;
        if (! $attachments || ! isset($attachments[$index])) {
            return response()->json(['error' => 'Attachment not found'], 404);
        }

        $attachment = $attachments[$index];
        $path = storage_path('app/'.$attachment['path']);

        if (! file_exists($path)) {
            return response()->json(['error' => 'File not found on disk'], 404);
        }

        return response()->download($path, $attachment['name'], [
            'Content-Type' => $attachment['mime_type'] ?? 'application/octet-stream',
        ]);
    }

    /**
     * Get support contact statistics (Admin only).
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total' => SupportContact::count(),
            'new' => SupportContact::where('status', 'new')->count(),
            'in_progress' => SupportContact::where('status', 'in_progress')->count(),
            'resolved' => SupportContact::where('status', 'resolved')->count(),
            'by_category' => SupportContact::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->pluck('count', 'category'),
            'by_priority' => SupportContact::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority'),
            'recent_count' => SupportContact::where('created_at', '>=', now()->subDays(7))->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Bulk update status of multiple support contacts.
     */
    public function bulkUpdateStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:support_contacts,id',
            'status' => ['required', Rule::in(['new', 'in_progress', 'resolved'])],
        ]);

        $updateData = ['status' => $request->status];
        if ($request->status === 'resolved') {
            $updateData['resolved_at'] = now();
        }

        $count = SupportContact::whereIn('id', $request->ids)->update($updateData);

        return response()->json([
            'success' => true,
            'message' => "{$count} contacts updated.",
            'count' => $count,
        ]);
    }

    /**
     * Export support contacts as CSV.
     */
    public function export(Request $request)
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = SupportContact::query()
            ->with(['user', 'company', 'assignedTo'])
            ->orderBy('created_at', 'desc');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        $contacts = $query->get();

        $csv = "Ref #,Subject,Name,Email,Company,Category,Priority,Status,Assigned To,Created,Resolved\n";
        foreach ($contacts as $c) {
            $csv .= implode(',', [
                $c->reference_number,
                '"'.str_replace('"', '""', $c->subject).'"',
                '"'.str_replace('"', '""', $c->name).'"',
                $c->email,
                '"'.str_replace('"', '""', $c->company_name ?? ($c->company->name ?? '')).'"',
                $c->category,
                $c->priority,
                $c->status,
                '"'.str_replace('"', '""', $c->assignedTo->name ?? '').'"',
                $c->created_at?->format('Y-m-d H:i'),
                $c->resolved_at?->format('Y-m-d H:i') ?? '',
            ])."\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="support-contacts-'.date('Y-m-d').'.csv"',
        ]);
    }

    /**
     * Delete a support contact.
     */
    public function destroy(Request $request, SupportContact $supportContact): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $supportContact->replies()->delete();
        $supportContact->delete();

        return response()->json([
            'success' => true,
            'message' => 'Contact deleted.',
        ]);
    }

    /**
     * Assign a support contact to an admin/support user.
     */
    public function assign(Request $request, SupportContact $supportContact): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'assigned_to' => 'nullable|integer|exists:users,id',
        ]);

        $supportContact->update([
            'assigned_to' => $request->assigned_to,
        ]);

        if ($request->assigned_to && $supportContact->status === 'new') {
            $supportContact->update(['status' => 'in_progress']);
        }

        $supportContact->load('assignedTo');

        return response()->json([
            'success' => true,
            'message' => 'Contact assigned.',
            'data' => $supportContact,
        ]);
    }

    /**
     * List replies for a support contact.
     */
    public function listReplies(Request $request, SupportContact $supportContact): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $replies = $supportContact->replies()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $replies,
        ]);
    }

    /**
     * Add a threaded reply to a support contact.
     */
    public function addReply(Request $request, SupportContact $supportContact): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'message' => 'required|string|min:5|max:5000',
            'is_internal' => 'boolean',
        ]);

        $reply = $supportContact->replies()->create([
            'user_id' => $user->id,
            'message' => $request->message,
            'is_internal' => $request->boolean('is_internal', false),
        ]);

        // Also update legacy admin_reply for backward compat + email
        $supportContact->update([
            'admin_reply' => $request->message,
            'admin_replied_at' => now(),
            'admin_user_id' => $user->id,
            'status' => $supportContact->status === 'new' ? 'in_progress' : $supportContact->status,
        ]);

        // Send reply email (only if not internal note)
        if (! $request->boolean('is_internal', false)) {
            try {
                Mail::to($supportContact->email)
                    ->send(new \App\Mail\SupportContactReply($supportContact));
            } catch (\Exception $e) {
                \Log::error('Failed to send support reply email: '.$e->getMessage());
            }
        }

        $reply->load('user:id,name,email');

        return response()->json([
            'success' => true,
            'message' => 'Reply added.',
            'data' => $reply,
        ]);
    }

    /**
     * Bulk delete support contacts.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:support_contacts,id',
        ]);

        SupportContactReply::whereIn('support_contact_id', $request->ids)->delete();
        $count = SupportContact::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} contacts deleted.",
            'count' => $count,
        ]);
    }

    /**
     * Format file size to human-readable format.
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
