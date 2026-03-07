<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SupportContactRequest;
use App\Mail\SupportContactConfirmation;
use App\Mail\SupportContactNotification;
use App\Models\SupportContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

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
     * Admin listing of ALL support contacts (cross-tenant).
     */
    public function indexAll(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->isOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $query = SupportContact::query()
            ->with(['user', 'company'])
            ->orderBy('created_at', 'desc');

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
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
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
