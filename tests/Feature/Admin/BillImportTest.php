<?php

use App\Http\Controllers\V1\Admin\AccountsPayable\BillsImportController;
use App\Http\Requests\ImportBillsRequest;
use App\Jobs\ProcessImportJob;
use App\Models\Bill;
use App\Models\Company;
use App\Models\ImportJob;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('bills import controller uses form request', function () {
    $this->assertActionUsesFormRequest(
        BillsImportController::class,
        'import',
        ImportBillsRequest::class
    );
});

test('bills import endpoint queues import job for company', function () {
    $company = Company::firstOrFail();

    Queue::fake();

    $csvContent = implode("\n", [
        'bill_number,supplier_name,supplier_tax_id,bill_date,total,tax',
        'BILL-1,ACME Ltd,MK1234567,2025-11-15,1000,180',
    ]);

    $file = UploadedFile::fake()->createWithContent('bills.csv', $csvContent);

    $response = postJson('/api/v1/bills/import', [
        'file' => $file,
    ], [
        'company' => $company->id,
    ]);

    $response->assertAccepted();

    $jobId = $response->json('job_id');
    expect($jobId)->not()->toBeNull();

    $importJob = ImportJob::find($jobId);
    expect($importJob)->not()->toBeNull();
    expect($importJob->company_id)->toBe($company->id);
    expect($importJob->type)->toBe('bills');

    Queue::assertPushed(ProcessImportJob::class);
});

test('bill import creates supplier and bill for correct company', function () {
    $company = Company::firstOrFail();

    $csvContent = implode("\n", [
        'bill_number,supplier_name,supplier_tax_id,bill_date,total,tax',
        'BILL-CSV-1,ACME Ltd,MK1234567,2025-11-15,1000,180',
    ]);

    $filePath = 'imports/bills/'.$company->id.'/test_bills.csv';
    \Storage::put($filePath, $csvContent);

    $importJob = ImportJob::create([
        'company_id' => $company->id,
        'creator_id' => User::find(1)->id,
        'type' => 'bills',
        'status' => ImportJob::STATUS_PENDING,
        'file_info' => [
            'path' => $filePath,
        ],
        'mapping_config' => [
            'bill_number' => 'bill_number',
            'supplier_name' => 'supplier_name',
            'supplier_tax_id' => 'supplier_tax_id',
            'bill_date' => 'bill_date',
            'total' => 'total',
            'tax' => 'tax',
        ],
    ]);

    $job = new ProcessImportJob($importJob, false);
    $job->handle(new \App\Services\Migration\ImportPresetService);

    $bill = Bill::where('company_id', $company->id)
        ->where('bill_number', 'BILL-CSV-1')
        ->first();

    expect($bill)->not()->toBeNull();
    expect($bill->total)->toBe(100000); // 1000 * 100 cents

    $supplier = Supplier::where('company_id', $company->id)
        ->where('tax_id', 'MK1234567')
        ->first();

    expect($supplier)->not()->toBeNull();
});

test('bill import respects tenant isolation', function () {
    $companyA = Company::firstOrFail();
    $companyB = Company::factory()->create();

    $csvContent = implode("\n", [
        'bill_number,supplier_name,supplier_tax_id,bill_date,total,tax',
        'BILL-ISO-1,ACME Ltd,MK0000000,2025-11-15,500,90',
    ]);

    $filePath = 'imports/bills/'.$companyA->id.'/test_iso_bills.csv';
    \Storage::put($filePath, $csvContent);

    $importJob = ImportJob::create([
        'company_id' => $companyA->id,
        'creator_id' => User::find(1)->id,
        'type' => 'bills',
        'status' => ImportJob::STATUS_PENDING,
        'file_info' => [
            'path' => $filePath,
        ],
        'mapping_config' => [
            'bill_number' => 'bill_number',
            'supplier_name' => 'supplier_name',
            'supplier_tax_id' => 'supplier_tax_id',
            'bill_date' => 'bill_date',
            'total' => 'total',
            'tax' => 'tax',
        ],
    ]);

    $job = new ProcessImportJob($importJob, false);
    $job->handle(new \App\Services\Migration\ImportPresetService);

    $billA = Bill::where('company_id', $companyA->id)
        ->where('bill_number', 'BILL-ISO-1')
        ->first();
    $billB = Bill::where('company_id', $companyB->id)
        ->where('bill_number', 'BILL-ISO-1')
        ->first();

    expect($billA)->not()->toBeNull();
    expect($billB)->toBeNull();
});
