<?php

namespace App\Http\Controllers\V1\Admin\Support;

use App\Http\Controllers\Controller;
use App\Http\Resources\TicketResource;
use App\Notifications\TicketClosedNotification;
use App\Notifications\TicketUpdatedNotification;
use Coderflex\LaravelTicket\Models\Ticket;
use Coderflex\LaravelTicket\Models\Message;
use Illuminate\Http\Request;
use App\Models\User;

/**
 * AdminTicketController
 *
 * Handles admin/agent operations on support tickets:
 * - View tickets from ALL companies (cross-tenant)
 * - Assign tickets to agents
 * - Update ticket status
 * - Add internal notes (not visible to customers)
 */
class AdminTicketController extends Controller
{
    /**
     * List all tickets across ALL companies (admin view)
     *
     * SECURITY: This is cross-tenant! Only accessible to admin/support users.
     * Regular users should use TicketController which enforces tenant isolation.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function listAllTickets(Request $request)
    {
        // CRITICAL: Verify user has admin/support role
        $user = $request->user();

        // Check if user is admin or has 'support' role
        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can access this endpoint'
            ], 403);
        }

        $limit = $request->has('limit') ? $request->limit : 25;

        // Query all tickets (no company_id filter = cross-tenant)
        $tickets = Ticket::query()
            ->with(['user', 'categories', 'labels', 'company'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->priority, function ($query, $priority) {
                $query->where('priority', $priority);
            })
            ->when($request->company_id, function ($query, $companyId) {
                $query->where('company_id', $companyId);
            })
            ->when($request->assigned_to, function ($query, $assignedTo) {
                $query->where('assigned_to', $assignedTo);
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
                'ticket_total_count' => Ticket::count(), // Total across all companies
                'open_count' => Ticket::where('status', 'open')->count(),
                'in_progress_count' => Ticket::where('status', 'in_progress')->count(),
                'urgent_count' => Ticket::where('priority', 'urgent')->count(),
            ]]);
    }

    /**
     * Assign ticket to an agent
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function assignTicket(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Only admins and support agents can assign tickets
        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can assign tickets'
            ], 403);
        }

        $request->validate([
            'assigned_to' => 'required|exists:users,id',
        ]);

        // Verify assigned user has support role or is owner
        $assignedUser = User::find($request->assigned_to);
        if (!$assignedUser->isOwner() && !$assignedUser->hasRole('support')) {
            return response()->json([
                'error' => 'Invalid Assignment',
                'message' => 'Can only assign tickets to support agents or admins'
            ], 422);
        }

        // Update ticket
        $ticket->update([
            'assigned_to' => $request->assigned_to,
            'status' => 'in_progress', // Auto-set to in_progress when assigned
        ]);

        $ticket->load(['user', 'categories', 'labels', 'assignedToUser']);

        return new TicketResource($ticket);
    }

    /**
     * Change ticket status
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function changeStatus(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Only admins and support agents can change status
        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can change ticket status'
            ], 403);
        }

        $request->validate([
            'status' => 'required|in:open,in_progress,resolved,closed',
        ]);

        $oldStatus = $ticket->status;
        $newStatus = $request->status;

        $ticket->update([
            'status' => $newStatus,
            'is_resolved' => in_array($newStatus, ['resolved', 'closed']),
            'is_locked' => $newStatus === 'closed',
        ]);

        $ticket->load(['user', 'categories', 'labels']);

        // Send appropriate notification to customer based on status change
        if ($newStatus === 'closed') {
            // Send closed notification
            $wasResolved = $ticket->is_resolved;
            $ticket->user->notify(new TicketClosedNotification($ticket, $wasResolved));
        } else {
            // Send status update notification for other status changes
            $ticket->user->notify(new TicketUpdatedNotification($ticket, $oldStatus, $newStatus));
        }

        return response()->json([
            'success' => true,
            'message' => 'Ticket status updated successfully',
            'data' => new TicketResource($ticket),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ]);
    }

    /**
     * Add internal note (not visible to customer)
     *
     * Uses the messages table with is_internal=true flag
     *
     * @param Request $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function addInternalNote(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        // Only admins and support agents can add internal notes
        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can add internal notes'
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        // Create message with is_internal flag
        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $request->message,
            'is_internal' => true, // CRITICAL: This hides note from customer
        ]);

        $message->load('user');

        return response()->json([
            'success' => true,
            'message' => 'Internal note added successfully',
            'data' => [
                'id' => $message->id,
                'ticket_id' => $ticket->id,
                'user_id' => $message->user_id,
                'message' => $message->message,
                'is_internal' => $message->is_internal,
                'created_at' => $message->created_at,
                'formatted_created_at' => $message->created_at->format('M d, Y H:i'),
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                ],
            ],
        ]);
    }

    /**
     * Get ticket statistics for admin dashboard
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics(Request $request)
    {
        $user = $request->user();

        if (!$user->isOwner() && !$user->hasRole('support')) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Only admins and support agents can access statistics'
            ], 403);
        }

        $stats = [
            'total_tickets' => Ticket::count(),
            'open_tickets' => Ticket::where('status', 'open')->count(),
            'in_progress_tickets' => Ticket::where('status', 'in_progress')->count(),
            'resolved_tickets' => Ticket::where('status', 'resolved')->count(),
            'closed_tickets' => Ticket::where('status', 'closed')->count(),
            'urgent_tickets' => Ticket::where('priority', 'urgent')->count(),
            'high_priority_tickets' => Ticket::where('priority', 'high')->count(),

            // If user is support agent (not admin), filter by assigned_to
            'my_assigned_tickets' => $user->hasRole('support')
                ? Ticket::where('assigned_to', $user->id)->count()
                : 0,
            'unassigned_tickets' => Ticket::whereNull('assigned_to')->count(),

            // Recent activity
            'tickets_today' => Ticket::whereDate('created_at', today())->count(),
            'tickets_this_week' => Ticket::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'tickets_this_month' => Ticket::whereMonth('created_at', now()->month)->count(),

            // Average response time (in hours)
            'avg_response_time_hours' => $this->calculateAverageResponseTime(),
        ];

        return response()->json($stats);
    }

    /**
     * Calculate average response time (time from ticket creation to first agent reply)
     *
     * @return float
     */
    private function calculateAverageResponseTime(): float
    {
        $tickets = Ticket::whereNotNull('assigned_to')
            ->with(['messages' => function ($query) {
                $query->where('is_internal', false)
                      ->orderBy('created_at', 'asc');
            }])
            ->get();

        $totalHours = 0;
        $ticketCount = 0;

        foreach ($tickets as $ticket) {
            if ($ticket->messages->count() > 1) {
                // First message is from customer, second is agent reply
                $firstReply = $ticket->messages->skip(1)->first();
                if ($firstReply) {
                    $responseTime = $ticket->created_at->diffInHours($firstReply->created_at);
                    $totalHours += $responseTime;
                    $ticketCount++;
                }
            }
        }

        return $ticketCount > 0 ? round($totalHours / $ticketCount, 2) : 0;
    }
}
// CLAUDE-CHECKPOINT
