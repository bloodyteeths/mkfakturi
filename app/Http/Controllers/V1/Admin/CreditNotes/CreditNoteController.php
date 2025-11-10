<?php

namespace App\Http\Controllers\V1\Admin\CreditNotes;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreditNoteRequest;
use App\Http\Requests\DeleteCreditNoteRequest;
use App\Http\Requests\SendCreditNoteRequest;
use App\Http\Resources\CreditNoteResource;
use App\Jobs\GenerateCreditNotePdfJob;
use App\Models\CreditNote;
use Illuminate\Http\Request;

/**
 * Credit Note Controller
 *
 * Handles CRUD operations for credit notes following InvoiceShelf patterns.
 * Credit notes are immutable once posted to IFRS (ifrs_transaction_id is set).
 *
 * @package App\Http\Controllers\V1\Admin\CreditNotes
 */
class CreditNoteController extends Controller
{
    /**
     * Relations required to render the credit note resource without N+1 queries.
     *
     * @return array<int, string>
     */
    private function creditNoteResourceRelations(): array
    {
        return [
            'customer.currency',
            'customer.company',
            'customer.billingAddress',
            'customer.shippingAddress',
            'customer.fields.customField',
            'customer.fields.company',
            'currency',
            'company',
            'creator',
            'invoice',
            'items.item',
            'items.taxes.taxType',
            'items.taxes.currency',
            'items.fields.customField',
            'items.fields.company',
            'taxes.taxType',
            'taxes.currency',
            'fields.customField',
            'fields.company',
        ];
    }

    /**
     * Display a listing of credit notes.
     *
     * Supports filtering by:
     * - customer_id
     * - status (DRAFT, SENT, VIEWED, COMPLETED)
     * - from_date / to_date range
     * - credit_note_number
     * - search (customer name/contact/company)
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', CreditNote::class);

        $limit = $request->input('limit', 10);

        $creditNotes = CreditNote::whereCompany()
            ->applyFilters($request->all())
            ->with($this->creditNoteResourceRelations())
            ->latest()
            ->paginateData($limit);

        return CreditNoteResource::collection($creditNotes)
            ->additional(['meta' => [
                'credit_note_total_count' => CreditNote::whereCompany()->count(),
            ]]);
    }

    /**
     * Store a newly created credit note.
     *
     * Automatically generates credit note number (CN-YYYY-XXXXX).
     * If creditNoteSend is present, also sends email to customer.
     *
     * @param \App\Http\Requests\CreditNoteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(CreditNoteRequest $request)
    {
        $this->authorize('create', CreditNote::class);

        $creditNote = CreditNote::createCreditNote($request);
        $creditNote->load($this->creditNoteResourceRelations());

        if ($request->has('creditNoteSend')) {
            $creditNote->send($request->all());
        }

        GenerateCreditNotePdfJob::dispatchAfterResponse($creditNote->id);

        return new CreditNoteResource($creditNote);
    }

    /**
     * Display the specified credit note.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CreditNote $creditNote
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, CreditNote $creditNote)
    {
        $this->authorize('view', $creditNote);

        $creditNote->load($this->creditNoteResourceRelations());

        return new CreditNoteResource($creditNote);
    }

    /**
     * Update the specified credit note.
     *
     * Updates are prevented if:
     * - Credit note is posted to IFRS (ifrs_transaction_id is set)
     * - Customer is being changed (enforced in model)
     *
     * @param \App\Http\Requests\CreditNoteRequest $request
     * @param \App\Models\CreditNote $creditNote
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(CreditNoteRequest $request, CreditNote $creditNote)
    {
        $this->authorize('update', $creditNote);

        // Check if credit note is posted to IFRS
        if ($creditNote->ifrs_transaction_id) {
            return response()->json([
                'error' => 'credit_note_cannot_be_changed_after_posting',
                'message' => 'This credit note has been posted to accounting and cannot be modified.',
            ], 422);
        }

        $result = $creditNote->updateCreditNote($request);

        // Handle error responses from model
        if (is_string($result)) {
            return response()->json([
                'error' => $result,
                'message' => $this->getErrorMessage($result),
            ], 422);
        }

        GenerateCreditNotePdfJob::dispatchAfterResponse($creditNote->id, true);

        $creditNote->load($this->creditNoteResourceRelations());

        return new CreditNoteResource($creditNote);
    }

    /**
     * Delete the specified credit notes.
     *
     * Deletion is prevented if credit note is posted to IFRS.
     * This check is handled in the model's deleteCreditNotes method.
     *
     * @param \App\Http\Requests\DeleteCreditNoteRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteCreditNoteRequest $request)
    {
        $this->authorize('delete multiple credit notes');

        CreditNote::deleteCreditNotes($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * Send credit note via email to customer.
     *
     * Updates status from DRAFT to SENT on first send.
     *
     * @param \App\Http\Requests\SendCreditNoteRequest $request
     * @param \App\Models\CreditNote $creditNote
     * @return \Illuminate\Http\JsonResponse
     */
    public function send(SendCreditNoteRequest $request, CreditNote $creditNote)
    {
        $this->authorize('send credit note', $creditNote);

        $result = $creditNote->send($request->all());

        return response()->json($result);
    }

    /**
     * Mark credit note as viewed by customer.
     *
     * Updates status from SENT to VIEWED.
     * This is typically called when customer opens the credit note link.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CreditNote $creditNote
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsViewed(Request $request, CreditNote $creditNote)
    {
        $this->authorize('view', $creditNote);

        $creditNote->markAsViewed();

        return response()->json([
            'success' => true,
            'status' => $creditNote->status,
        ]);
    }

    /**
     * Mark credit note as completed.
     *
     * This triggers IFRS posting via CreditNoteObserver.
     * Once completed and posted, the credit note becomes immutable.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CreditNote $creditNote
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsCompleted(Request $request, CreditNote $creditNote)
    {
        $this->authorize('update', $creditNote);

        // Check if already posted to IFRS
        if ($creditNote->ifrs_transaction_id) {
            return response()->json([
                'error' => 'credit_note_already_posted',
                'message' => 'This credit note has already been posted to accounting.',
            ], 422);
        }

        // Check if credit note is in valid status for completion
        if ($creditNote->status === CreditNote::STATUS_DRAFT) {
            return response()->json([
                'error' => 'credit_note_must_be_sent_first',
                'message' => 'Draft credit notes must be sent before they can be completed.',
            ], 422);
        }

        $creditNote->markAsCompleted();

        return response()->json([
            'success' => true,
            'status' => $creditNote->status,
            'ifrs_posted' => !is_null($creditNote->ifrs_transaction_id),
        ]);
    }

    /**
     * Get human-readable error message for model error codes.
     *
     * @param string $errorCode
     * @return string
     */
    private function getErrorMessage(string $errorCode): string
    {
        $messages = [
            'credit_note_cannot_be_changed_after_posting' => 'This credit note has been posted to accounting and cannot be modified.',
            'customer_cannot_be_changed_after_creation' => 'The customer cannot be changed after the credit note is created.',
        ];

        return $messages[$errorCode] ?? 'An error occurred while processing the credit note.';
    }
}

// CLAUDE-CHECKPOINT
