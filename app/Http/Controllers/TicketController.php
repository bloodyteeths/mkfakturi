<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * Ticket API Stub Service for Facturino Support Demo
 * 
 * This is a demonstration service showing how ticket creation and management
 * would work in a full support system integration.
 * 
 * Features:
 * - Create support tickets
 * - List tickets with filtering
 * - Update ticket status
 * - Add comments/notes
 * - Priority and category management
 * 
 * Note: This is a stub implementation for demonstration purposes.
 * In production, this would integrate with a proper ticketing system like
 * Zendesk, Freshdesk, or a custom support platform.
 */
class TicketController extends Controller
{
    /**
     * Demo ticket storage (in production, this would be a database)
     */
    private static $demoTickets = [];

    /**
     * Create a new support ticket
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'subject' => 'required|string|max:255',
                'description' => 'required|string|max:2000',
                'priority' => 'required|in:low,medium,high,critical',
                'category' => 'required|in:technical,billing,migration,tax,banking,general',
                'user_email' => 'required|email',
                'user_name' => 'required|string|max:100',
                'company_id' => 'nullable|integer',
                'attachments' => 'nullable|array',
                'attachments.*' => 'string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Generate ticket ID
            $ticketId = $this->generateTicketId();
            
            // Create ticket data
            $ticket = [
                'id' => $ticketId,
                'subject' => $request->subject,
                'description' => $request->description,
                'priority' => $request->priority,
                'category' => $request->category,
                'status' => 'open',
                'user_email' => $request->user_email,
                'user_name' => $request->user_name,
                'company_id' => $request->company_id,
                'attachments' => $request->attachments ?? [],
                'created_at' => Carbon::now()->toISOString(),
                'updated_at' => Carbon::now()->toISOString(),
                'assigned_to' => $this->autoAssignTicket($request->category, $request->priority),
                'estimated_resolution' => $this->calculateEstimatedResolution($request->priority),
                'comments' => [
                    [
                        'id' => 1,
                        'author' => 'System',
                        'message' => 'Ticket created and automatically assigned to appropriate team.',
                        'created_at' => Carbon::now()->toISOString(),
                        'internal' => true
                    ]
                ]
            ];

            // Store ticket (in production, save to database)
            self::$demoTickets[$ticketId] = $ticket;

            // Log ticket creation
            Log::info('Demo support ticket created', [
                'ticket_id' => $ticketId,
                'category' => $request->category,
                'priority' => $request->priority,
                'user_email' => $request->user_email
            ]);

            // Send auto-reply email (demo)
            $this->sendAutoReplyEmail($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Support ticket created successfully',
                'data' => [
                    'ticket_id' => $ticketId,
                    'status' => 'open',
                    'assigned_to' => $ticket['assigned_to'],
                    'estimated_resolution' => $ticket['estimated_resolution'],
                    'next_steps' => $this->getNextStepsMessage($request->category)
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error creating support ticket', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error occurred while creating ticket',
                'error_code' => 'TICKET_CREATE_ERROR'
            ], 500);
        }
    }

    /**
     * List tickets with optional filtering
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tickets = collect(self::$demoTickets);

            // Apply filters
            if ($request->has('status')) {
                $tickets = $tickets->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $tickets = $tickets->where('priority', $request->priority);
            }

            if ($request->has('category')) {
                $tickets = $tickets->where('category', $request->category);
            }

            if ($request->has('user_email')) {
                $tickets = $tickets->where('user_email', $request->user_email);
            }

            // Pagination
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 10);
            $offset = ($page - 1) * $perPage;

            $totalTickets = $tickets->count();
            $paginatedTickets = $tickets->skip($offset)->take($perPage)->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'tickets' => $paginatedTickets,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $totalTickets,
                        'total_pages' => ceil($totalTickets / $perPage)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing tickets', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving tickets',
                'error_code' => 'TICKET_LIST_ERROR'
            ], 500);
        }
    }

    /**
     * Get a specific ticket by ID
     * 
     * @param string $ticketId
     * @return JsonResponse
     */
    public function show(string $ticketId): JsonResponse
    {
        try {
            if (!isset(self::$demoTickets[$ticketId])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                    'error_code' => 'TICKET_NOT_FOUND'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => self::$demoTickets[$ticketId]
            ]);

        } catch (\Exception $e) {
            Log::error('Error retrieving ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving ticket',
                'error_code' => 'TICKET_RETRIEVE_ERROR'
            ], 500);
        }
    }

    /**
     * Update ticket status
     * 
     * @param Request $request
     * @param string $ticketId
     * @return JsonResponse
     */
    public function updateStatus(Request $request, string $ticketId): JsonResponse
    {
        try {
            if (!isset(self::$demoTickets[$ticketId])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                    'error_code' => 'TICKET_NOT_FOUND'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:open,in_progress,waiting_customer,resolved,closed',
                'comment' => 'nullable|string|max:1000',
                'internal' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update ticket
            self::$demoTickets[$ticketId]['status'] = $request->status;
            self::$demoTickets[$ticketId]['updated_at'] = Carbon::now()->toISOString();

            // Add comment if provided
            if ($request->has('comment')) {
                $commentId = count(self::$demoTickets[$ticketId]['comments']) + 1;
                self::$demoTickets[$ticketId]['comments'][] = [
                    'id' => $commentId,
                    'author' => 'Support Agent', // In production, use authenticated user
                    'message' => $request->comment,
                    'created_at' => Carbon::now()->toISOString(),
                    'internal' => $request->get('internal', false)
                ];
            }

            // Log status change
            Log::info('Ticket status updated', [
                'ticket_id' => $ticketId,
                'old_status' => self::$demoTickets[$ticketId]['status'],
                'new_status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ticket status updated successfully',
                'data' => self::$demoTickets[$ticketId]
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating ticket status', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error updating ticket status',
                'error_code' => 'TICKET_UPDATE_ERROR'
            ], 500);
        }
    }

    /**
     * Add comment to ticket
     * 
     * @param Request $request
     * @param string $ticketId
     * @return JsonResponse
     */
    public function addComment(Request $request, string $ticketId): JsonResponse
    {
        try {
            if (!isset(self::$demoTickets[$ticketId])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found',
                    'error_code' => 'TICKET_NOT_FOUND'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'message' => 'required|string|max:2000',
                'internal' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Add comment
            $commentId = count(self::$demoTickets[$ticketId]['comments']) + 1;
            $comment = [
                'id' => $commentId,
                'author' => 'Support Agent', // In production, use authenticated user
                'message' => $request->message,
                'created_at' => Carbon::now()->toISOString(),
                'internal' => $request->get('internal', false)
            ];

            self::$demoTickets[$ticketId]['comments'][] = $comment;
            self::$demoTickets[$ticketId]['updated_at'] = Carbon::now()->toISOString();

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $comment
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding comment to ticket', [
                'ticket_id' => $ticketId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error adding comment',
                'error_code' => 'COMMENT_ADD_ERROR'
            ], 500);
        }
    }

    /**
     * Generate unique ticket ID
     * 
     * @return string
     */
    private function generateTicketId(): string
    {
        $prefix = 'FAC';
        $date = Carbon::now()->format('Ymd');
        $sequence = str_pad(count(self::$demoTickets) + 1, 4, '0', STR_PAD_LEFT);
        
        return "{$prefix}-{$date}-{$sequence}";
    }

    /**
     * Auto-assign ticket based on category and priority
     * 
     * @param string $category
     * @param string $priority
     * @return string
     */
    private function autoAssignTicket(string $category, string $priority): string
    {
        $assignments = [
            'technical' => $priority === 'critical' ? 'Development Team' : 'Technical Support L2',
            'billing' => 'Billing Team',
            'migration' => 'Migration Specialist',
            'tax' => 'Tax Compliance Expert',
            'banking' => 'Banking Integration Team',
            'general' => 'Level 1 Support'
        ];

        return $assignments[$category] ?? 'Level 1 Support';
    }

    /**
     * Calculate estimated resolution time
     * 
     * @param string $priority
     * @return string
     */
    private function calculateEstimatedResolution(string $priority): string
    {
        $resolutionTimes = [
            'critical' => '4 hours',
            'high' => '24 hours',
            'medium' => '48 hours',
            'low' => '72 hours'
        ];

        return $resolutionTimes[$priority] ?? '48 hours';
    }

    /**
     * Get next steps message based on category
     * 
     * @param string $category
     * @return string
     */
    private function getNextStepsMessage(string $category): string
    {
        $messages = [
            'technical' => 'Our technical team will investigate the issue and provide updates within 4 hours.',
            'billing' => 'Our billing team will review your account and respond within 24 hours.',
            'migration' => 'A migration specialist will contact you to schedule a call within 8 hours.',
            'tax' => 'Our tax compliance expert will review your request and provide guidance within 8 hours.',
            'banking' => 'Our banking integration team will check your connection and respond within 8 hours.',
            'general' => 'A support agent will review your request and respond within 4 hours.'
        ];

        return $messages[$category] ?? 'A support agent will review your request and respond soon.';
    }

    /**
     * Send auto-reply email (demo implementation)
     * 
     * @param array $ticket
     * @return void
     */
    private function sendAutoReplyEmail(array $ticket): void
    {
        // In production, this would send an actual email
        Log::info('Auto-reply email sent', [
            'ticket_id' => $ticket['id'],
            'user_email' => $ticket['user_email'],
            'template' => 'ticket_created'
        ]);
    }

    /**
     * Get ticket statistics (demo endpoint)
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $tickets = collect(self::$demoTickets);

            $stats = [
                'total_tickets' => $tickets->count(),
                'open_tickets' => $tickets->where('status', 'open')->count(),
                'in_progress_tickets' => $tickets->where('status', 'in_progress')->count(),
                'resolved_tickets' => $tickets->where('status', 'resolved')->count(),
                'closed_tickets' => $tickets->where('status', 'closed')->count(),
                'by_priority' => [
                    'critical' => $tickets->where('priority', 'critical')->count(),
                    'high' => $tickets->where('priority', 'high')->count(),
                    'medium' => $tickets->where('priority', 'medium')->count(),
                    'low' => $tickets->where('priority', 'low')->count()
                ],
                'by_category' => [
                    'technical' => $tickets->where('category', 'technical')->count(),
                    'billing' => $tickets->where('category', 'billing')->count(),
                    'migration' => $tickets->where('category', 'migration')->count(),
                    'tax' => $tickets->where('category', 'tax')->count(),
                    'banking' => $tickets->where('category', 'banking')->count(),
                    'general' => $tickets->where('category', 'general')->count()
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting ticket statistics', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving statistics',
                'error_code' => 'STATS_ERROR'
            ], 500);
        }
    }
}

