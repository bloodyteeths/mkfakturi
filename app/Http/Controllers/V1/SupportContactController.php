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
use Illuminate\Support\Facades\Storage;

class SupportContactController extends Controller
{
    /**
     * Store a new support contact submission.
     *
     * @param  SupportContactRequest  $request
     * @return JsonResponse
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
            \Log::error('Failed to send support notification email: ' . $e->getMessage());
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
            \Log::error('Failed to send support confirmation email: ' . $e->getMessage());
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
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = SupportContact::query()
            ->with(['user', 'company'])
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->has('category')) {
            $query->where('category', $request->category);
        }

        // Filter by priority
        if ($request->has('priority')) {
            $query->where('priority', $request->priority);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $contacts = $query->paginate($request->get('per_page', 15));

        return response()->json($contacts);
    }

    /**
     * Display the specified support contact.
     *
     * @param  SupportContact  $supportContact
     * @return JsonResponse
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
     *
     * @param  Request  $request
     * @param  SupportContact  $supportContact
     * @return JsonResponse
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
     * Get support contact statistics (Admin only).
     *
     * @return JsonResponse
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
     *
     * @param  int  $bytes
     * @return string
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}
// CLAUDE-CHECKPOINT
