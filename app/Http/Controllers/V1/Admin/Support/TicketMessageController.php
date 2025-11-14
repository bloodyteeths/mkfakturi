<?php

namespace App\Http\Controllers\V1\Admin\Support;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ticket\ReplyTicketRequest;
use App\Http\Resources\TicketMessageResource;
use Coderflex\LaravelTicket\Models\Message;
use Coderflex\LaravelTicket\Models\Ticket;
use Illuminate\Http\Request;

class TicketMessageController extends Controller
{
    /**
     * Display messages for a specific ticket.
     *
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Ticket $ticket)
    {
        $this->authorize('view', $ticket);

        $messages = $ticket->messages()
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        return TicketMessageResource::collection($messages);
    }

    /**
     * Store a new reply/message on a ticket.
     *
     * @param ReplyTicketRequest $request
     * @param Ticket $ticket
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ReplyTicketRequest $request, Ticket $ticket)
    {
        $this->authorize('reply', $ticket);

        $user = $request->user();

        // Create message using the package's method
        $message = $ticket->messages()->create([
            'user_id' => $user->id,
            'message' => $request->message,
        ]);

        // Update ticket timestamp
        $ticket->touch();

        // If ticket was resolved, re-open it
        if ($ticket->is_resolved) {
            $ticket->update([
                'is_resolved' => false,
                'status' => 'open',
            ]);
        }

        $message->load('user');

        return new TicketMessageResource($message);
    }

    /**
     * Update a message.
     *
     * @param Request $request
     * @param Ticket $ticket
     * @param Message $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Ticket $ticket, Message $message)
    {
        $this->authorize('view', $ticket);

        // Only allow the message author to edit
        if ($message->user_id !== $request->user()->id) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only edit your own messages'
            ], 403);
        }

        $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $message->update([
            'message' => $request->message,
        ]);

        $message->load('user');

        return new TicketMessageResource($message);
    }

    /**
     * Remove a message.
     *
     * @param Ticket $ticket
     * @param Message $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Ticket $ticket, Message $message)
    {
        $this->authorize('view', $ticket);

        // Only allow admins or message author to delete
        if ($message->user_id !== $request->user()->id && !$request->user()->isOwner()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'You can only delete your own messages'
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
    }
}
// CLAUDE-CHECKPOINT
