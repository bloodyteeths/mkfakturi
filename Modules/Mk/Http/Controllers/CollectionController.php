<?php

namespace Modules\Mk\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Mk\Models\ReminderTemplate;
use Modules\Mk\Services\CollectionService;

class CollectionController extends Controller
{
    protected CollectionService $service;

    public function __construct(CollectionService $service)
    {
        $this->service = $service;
    }

    /**
     * List overdue invoices with escalation levels.
     */
    public function overdueInvoices(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $filters = [
            'customer_id' => $request->query('customer_id'),
            'escalation_level' => $request->query('escalation_level'),
            'search' => $request->query('search'),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 50),
        ];

        $result = $this->service->getOverdueInvoices($companyId, $filters);
        $invoices = $result['invoices'];

        // Calculate summary from current page data
        $totalOverdue = array_sum(array_column($invoices, 'due_amount'));
        $customerCount = count(array_unique(array_filter(array_column($invoices, 'customer_id'))));
        $avgDays = count($invoices) > 0
            ? round(array_sum(array_column($invoices, 'days_overdue')) / count($invoices))
            : 0;

        return response()->json([
            'success' => true,
            'data' => $invoices,
            'summary' => [
                'total_overdue_amount' => $totalOverdue,
                'invoice_count' => $result['pagination']['total'],
                'customer_count' => $customerCount,
                'avg_days_overdue' => $avgDays,
                'total_interest' => $result['total_interest'],
                'interest_rate' => $result['interest_rate'],
            ],
            'aging' => $result['aging'],
            'pagination' => $result['pagination'],
        ]);
    }

    /**
     * Send a reminder for a specific invoice.
     */
    public function sendReminder(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $request->validate([
            'invoice_id' => 'required|integer',
            'level' => 'required|in:friendly,firm,final,legal',
        ]);

        try {
            $result = $this->service->sendReminder(
                $companyId,
                (int) $request->input('invoice_id'),
                $request->input('level')
            );

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Reminder sent successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * List reminder templates.
     */
    public function templates(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $templates = $this->service->getTemplates($companyId);

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Create a new reminder template.
     */
    public function storeTemplate(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $request->validate([
            'escalation_level' => 'required|in:friendly,firm,final,legal',
            'days_after_due' => 'required|integer|min:1',
            'subject_mk' => 'required|string|max:500',
            'subject_en' => 'required|string|max:500',
            'subject_tr' => 'required|string|max:500',
            'subject_sq' => 'required|string|max:500',
            'body_mk' => 'required|string',
            'body_en' => 'required|string',
            'body_tr' => 'required|string',
            'body_sq' => 'required|string',
            'is_active' => 'boolean',
            'auto_send' => 'boolean',
        ]);

        $template = ReminderTemplate::create(array_merge(
            $request->only([
                'escalation_level', 'days_after_due',
                'subject_mk', 'subject_en', 'subject_tr', 'subject_sq',
                'body_mk', 'body_en', 'body_tr', 'body_sq',
                'is_active', 'auto_send',
            ]),
            ['company_id' => $companyId]
        ));

        return response()->json([
            'success' => true,
            'data' => $template,
            'message' => 'Template created successfully.',
        ], 201);
    }

    /**
     * Update a reminder template.
     */
    public function updateTemplate(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $template = ReminderTemplate::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 404);
        }

        $request->validate([
            'escalation_level' => 'in:friendly,firm,final,legal',
            'days_after_due' => 'integer|min:1',
            'subject_mk' => 'string|max:500',
            'subject_en' => 'string|max:500',
            'subject_tr' => 'string|max:500',
            'subject_sq' => 'string|max:500',
            'body_mk' => 'string',
            'body_en' => 'string',
            'body_tr' => 'string',
            'body_sq' => 'string',
            'is_active' => 'boolean',
            'auto_send' => 'boolean',
        ]);

        $template->update($request->only([
            'escalation_level', 'days_after_due',
            'subject_mk', 'subject_en', 'subject_tr', 'subject_sq',
            'body_mk', 'body_en', 'body_tr', 'body_sq',
            'is_active', 'auto_send',
        ]));

        return response()->json([
            'success' => true,
            'data' => $template->fresh(),
            'message' => 'Template updated successfully.',
        ]);
    }

    /**
     * Delete a reminder template.
     */
    public function deleteTemplate(Request $request, int $id): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $template = ReminderTemplate::forCompany($companyId)
            ->where('id', $id)
            ->first();

        if (! $template) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 404);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully.',
        ]);
    }

    /**
     * Get reminder history.
     */
    public function history(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $filters = [
            'customer_id' => $request->query('customer_id'),
            'from_date' => $request->query('from_date'),
            'to_date' => $request->query('to_date'),
            'page' => $request->query('page', 1),
            'per_page' => $request->query('per_page', 50),
        ];

        $result = $this->service->getHistory($companyId, $filters);

        return response()->json([
            'success' => true,
            'data' => $result['items'],
            'pagination' => $result['pagination'],
        ]);
    }

    /**
     * Get effectiveness analytics.
     */
    public function effectiveness(Request $request): JsonResponse
    {
        $companyId = (int) $request->header('company');

        $effectiveness = $this->service->getEffectiveness($companyId);

        return response()->json([
            'success' => true,
            'data' => $effectiveness,
        ]);
    }

    /**
     * Generate Опомена (formal dunning letter) PDF.
     */
    public function opomena(Request $request, int $invoiceId)
    {
        $companyId = (int) $request->header('company');

        try {
            $data = $this->service->getOpomenaData($companyId, $invoiceId);

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('app.pdf.reports.opomena', $data);
            $pdf->setPaper('A4', 'portrait');

            $filename = "opomena-{$data['invoice']->invoice_number}.pdf";

            return $pdf->download($filename);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}

// CLAUDE-CHECKPOINT
