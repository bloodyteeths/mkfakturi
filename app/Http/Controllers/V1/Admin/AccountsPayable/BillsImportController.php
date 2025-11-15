<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportBillsRequest;
use App\Jobs\ProcessImportJob;
use App\Models\ImportJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class BillsImportController extends Controller
{
    public function import(ImportBillsRequest $request): JsonResponse
    {
        $user = $request->user();
        $companyId = (int) $request->header('company');

        $this->authorize('create', \App\Models\Bill::class);

        $file = $request->file('file');
        $disk = config('filesystems.default', 'local');
        $path = $file->store('imports/bills/'.$companyId, ['disk' => $disk]);

        $importJob = ImportJob::create([
            'company_id' => $companyId,
            'creator_id' => $user->id,
            'type' => 'bills',
            'status' => ImportJob::STATUS_PENDING,
            'file_info' => [
                'path' => $path,
            ],
            'source_system' => 'csv',
        ]);

        ProcessImportJob::dispatch($importJob, false)->onQueue('migration');

        return response()->json([
            'message' => 'Bills import started',
            'job_id' => $importJob->id,
        ], 202);
    }
}

