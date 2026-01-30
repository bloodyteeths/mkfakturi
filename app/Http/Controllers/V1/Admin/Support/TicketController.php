<?php

namespace App\Http\Controllers\V1\Admin\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\CreateTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\Ticket;
use App\Notifications\TicketCreatedNotification;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the tickets for the current company.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Ticket::class);

        $limit = $request->has('limit') ? $request->limit : 10;
        $companyId = $request->header('company');

        // Get tickets for current company only (tenant isolation)
        $tickets = Ticket::query()
            ->where('company_id', $companyId)
            ->with(['user', 'categories', 'labels'])
            ->withCount('messages')
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'LIKE', "%{$search}%")
                        ->orWhere('message', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc');

        if ($limit == 'all') {
            $tickets = $tickets->get();
        } else {
            $tickets = $tickets->paginate($limit);
        }

        return TicketResource::collection($tickets)
            ->additional(['meta' => [
                'ticket_total_count' => Ticket::where('company_id', $companyId)->count(),
            ]]);
    }

    /**
     * Store a newly created ticket.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreateTicketRequest $request)
    {
        $this->authorize('create', Ticket::class);

        $companyId = $request->header('company');
        $user = $request->user();

        // Ensure user belongs to this company (double check)
        if (! $user->hasCompany($companyId)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You do not have access to this company',
            ], 403);
        }

        // Create ticket with company_id for tenant isolation
        $ticket = $user->tickets()->create([
            'uuid' => \Str::uuid(),
            'company_id' => $companyId,
            'title' => $request->title,
            'message' => $request->message,
            'priority' => $request->priority ?? 'normal',
            'status' => 'open',
            'is_resolved' => false,
            'is_locked' => false,
        ]);

        // Attach categories if provided
        if ($request->has('categories') && is_array($request->categories)) {
            $ticket->categories()->sync($request->categories);
        }

        // Load relationships for response
        $ticket->load(['user', 'categories', 'labels']);

        // Send notification to customer
        $user->notify(new TicketCreatedNotification($ticket));

        return new TicketResource($ticket);
    }

    /**
     * Display the specified ticket.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        // Eager load relationships including media for message attachments
        $ticket->load(['user', 'categories', 'labels', 'messages.user', 'messages.media']);

        return new TicketResource($ticket);
    }

    /**
     * Update the specified ticket.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);

        $user = $request->user();
        $isAdmin = $user->role === 'super admin' || $user->role === 'support';

        // Build allowed fields based on user role
        $allowedFields = ['title']; // All users can update title

        if ($isAdmin) {
            // Admins can update all fields
            $allowedFields = array_merge($allowedFields, ['priority', 'status', 'is_resolved', 'is_locked']);
        } else {
            // Regular users can only close their own tickets (not change to other statuses)
            if ($request->has('status') && $request->status === 'closed') {
                $allowedFields[] = 'status';
            }
        }

        $ticket->update($request->only($allowedFields));

        if ($request->has('categories')) {
            $ticket->categories()->sync($request->categories);
        }

        $ticket->load(['user', 'categories', 'labels']);

        return new TicketResource($ticket);
    }

    /**
     * Remove the specified ticket.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ticket deleted successfully',
        ]);
    }
}
// CLAUDE-CHECKPOINT
