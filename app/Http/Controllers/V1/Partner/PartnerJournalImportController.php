<?php

namespace App\Http\Controllers\V1\Partner;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Partner;
use App\Services\JournalImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Partner Journal Import Controller
 *
 * 3-step import wizard for partners to import journal entries
 * from Pantheon (.txt) or CSV files into their client companies.
 */
class PartnerJournalImportController extends Controller
{
    /**
     * Preview: Upload file, parse, validate — no DB writes.
     */
    public function preview(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:txt,csv|max:10240', // 10MB
            'firms_file' => 'nullable|file|mimes:txt|max:5120', // Optional firms file, 5MB
        ]);

        try {
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $filename = $file->getClientOriginalName();

            $service = new JournalImportService();

            // Parse optional firms file for counterparty name resolution
            if ($request->hasFile('firms_file')) {
                $firmsContent = file_get_contents($request->file('firms_file')->getRealPath());
                $firmsMap = $service->parseFirmsFile($firmsContent);
                $service->setFirmsMap($firmsMap);
            }

            $parsed = $service->parseFile($content, $filename);
            $validation = $service->validateNalozi($parsed['nalozi'], $company);

            return response()->json([
                'success' => true,
                'data' => [
                    'format' => $parsed['format'],
                    'nalozi' => $parsed['nalozi'],
                    'accounts' => $parsed['accounts'],
                    'firms' => $parsed['firms'],
                    'parse_warnings' => $parsed['parse_warnings'] ?? [],
                    'validation' => $validation,
                    'summary' => [
                        'total_nalozi' => count($parsed['nalozi']),
                        'total_line_items' => array_sum(array_column($parsed['nalozi'], 'line_count')),
                        'total_accounts' => count($parsed['accounts']),
                        'balanced' => count(array_filter($parsed['nalozi'], fn($n) => $n['balanced'])),
                        'unbalanced' => count(array_filter($parsed['nalozi'], fn($n) => !$n['balanced'])),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to parse file: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Import: Accept nalozi JSON + options, create IFRS transactions.
     */
    public function import(Request $request, int $company): JsonResponse
    {
        $partner = $this->getPartnerFromRequest($request);

        if (!$partner) {
            return response()->json([
                'success' => false,
                'message' => 'Partner not found',
            ], 404);
        }

        if (!$this->hasCompanyAccess($partner, $company)) {
            return response()->json([
                'success' => false,
                'message' => 'No access to this company',
            ], 403);
        }

        $request->validate([
            'nalozi' => 'required|array|min:1',
            'nalozi.*.nalog_id' => 'required|string',
            'nalozi.*.date' => 'required|date',
            'nalozi.*.line_items' => 'required|array|min:1',
            'nalozi.*.line_items.*.account_code' => 'required|string',
            'nalozi.*.line_items.*.account_name' => 'required|string',
            'nalozi.*.line_items.*.amount' => 'required|numeric|min:0.01',
            'nalozi.*.line_items.*.credited' => 'required|boolean',
            'auto_create_accounts' => 'sometimes|boolean',
        ]);

        try {
            $companyModel = Company::findOrFail($company);
            $service = new JournalImportService();

            $result = $service->importNalozi(
                $request->input('nalozi'),
                $companyModel,
                $request->boolean('auto_create_accounts', true)
            );

            return response()->json([
                'success' => $result['imported'] > 0,
                'data' => $result,
                'message' => $result['imported'] > 0
                    ? "Успешно внесени {$result['imported']} налози"
                    : 'Ниеден налог не беше внесен',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Return supported import formats.
     */
    public function formats(): JsonResponse
    {
        $service = new JournalImportService();

        return response()->json([
            'success' => true,
            'data' => $service->getSupportedFormats(),
        ]);
    }

    /**
     * Get partner from the authenticated user.
     */
    protected function getPartnerFromRequest(Request $request): ?Partner
    {
        $user = $request->user();

        if (!$user) {
            return null;
        }

        if ($user->role === 'super admin') {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = 'Super Admin';
            $fakePartner->email = $user->email;
            $fakePartner->is_super_admin = true;
            return $fakePartner;
        }

        // Allow company owners to use journal import (for onboarding)
        if ($user->isOwner()) {
            $fakePartner = new Partner();
            $fakePartner->id = 0;
            $fakePartner->user_id = $user->id;
            $fakePartner->name = $user->name;
            $fakePartner->email = $user->email;
            $fakePartner->is_company_owner = true;
            return $fakePartner;
        }

        return Partner::where('user_id', $user->id)->first();
    }

    /**
     * Check if partner has access to the company.
     */
    protected function hasCompanyAccess(Partner $partner, int $companyId): bool
    {
        if ($partner->is_super_admin ?? false) {
            return true;
        }

        // Company owners can access their own company
        if ($partner->is_company_owner ?? false) {
            return \App\Models\Company::where('id', $companyId)
                ->whereHas('users', fn ($q) => $q->where('users.id', $partner->user_id))
                ->exists();
        }

        return $partner->companies()
            ->where('companies.id', $companyId)
            ->where('partner_company_links.is_active', true)
            ->exists();
    }
}

// CLAUDE-CHECKPOINT
