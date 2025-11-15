<?php

use App\Http\Controllers\V1\Admin\AccountsPayable\BillsController;
use App\Http\Requests\BillRequest;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

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

test('bills index supports filters and ordering', function () {
    Bill::factory()->count(3)->create([
        'company_id' => User::find(1)->companies()->first()->id,
    ]);

    $response = getJson('api/v1/bills?page=1&limit=10&bill_number=BILL&orderByField=bill_date&orderBy=desc');

    $response->assertOk()
        ->assertJsonStructure([
            'data',
            'meta' => ['total'],
        ]);
});

test('bill controller uses BillRequest form request', function () {
    $this->assertActionUsesFormRequest(
        BillsController::class,
        'store',
        BillRequest::class
    );
    $this->assertActionUsesFormRequest(
        BillsController::class,
        'update',
        BillRequest::class
    );
});

test('bill status changes and pdf download work', function () {
    $bill = Bill::factory()->create([
        'status' => Bill::STATUS_DRAFT,
    ]);

    postJson("api/v1/bills/{$bill->id}/send", [
        'to' => 'supplier@example.com',
        'subject' => 'Bill',
        'body' => 'Body',
    ])->assertOk();

    $bill->refresh();
    expect($bill->status)->toBe(Bill::STATUS_SENT);

    postJson("api/v1/bills/{$bill->id}/mark-as-viewed")->assertOk();
    $bill->refresh();
    expect($bill->status)->toBe(Bill::STATUS_VIEWED);

    postJson("api/v1/bills/{$bill->id}/mark-as-completed")->assertOk();
    $bill->refresh();
    expect($bill->status)->toBe(Bill::STATUS_COMPLETED);

    $pdfResponse = getJson("api/v1/bills/{$bill->id}/download-pdf");
    $pdfResponse->assertOk();
});

test('bill payments CRUD maintain paid_status and isolation', function () {
    $company = Company::firstOrFail();
    $supplier = Supplier::factory()->create([
        'company_id' => $company->id,
    ]);

    $billData = Bill::factory()->raw([
        'company_id' => $company->id,
        'supplier_id' => $supplier->id,
        'items' => [BillItem::factory()->raw()],
        'taxes' => [Tax::factory()->raw()],
    ]);

    $billResponse = postJson('api/v1/bills', $billData);
    $billResponse->assertCreated();

    $billId = $billResponse->json('data.id');
    $bill = Bill::findOrFail($billId);

    $paymentPayload = [
        'payment_date' => now()->toDateString(),
        'amount' => $bill->total,
        'payment_method_id' => 1,
        'notes' => 'Full payment',
    ];

    $paymentResponse = postJson("api/v1/bills/{$billId}/payments", $paymentPayload);
    $paymentResponse->assertCreated();

    $bill->refresh();
    expect($bill->paid_status)->toBe(Bill::PAID_STATUS_PAID);

    $paymentId = $paymentResponse->json('data.id');

    $updatePayload = [
        'payment_date' => now()->toDateString(),
        'amount' => (int) ($bill->total / 2),
        'payment_method_id' => 1,
        'notes' => 'Half payment',
    ];

    putJson("api/v1/bills/{$billId}/payments/{$paymentId}", $updatePayload)->assertOk();

    $bill->refresh();
    expect($bill->paid_status)->toBe(Bill::PAID_STATUS_PARTIALLY_PAID);

    // Delete payment and ensure paid_status returns to UNPAID
    deleteJson("api/v1/bills/{$billId}/payments/{$paymentId}")->assertOk();

    $bill->refresh();
    expect($bill->paid_status)->toBe(Bill::PAID_STATUS_UNPAID);
});

test('bills multi-tenant isolation prevents cross-company access', function () {
    $companyA = Company::firstOrFail();
    $companyB = Company::factory()->create();

    $billA = Bill::factory()->create([
        'company_id' => $companyA->id,
    ]);

    // Set header as company B and ensure we cannot see company A bill in index
    $this->withHeaders([
        'company' => $companyB->id,
    ]);

    Sanctum::actingAs(User::factory()->create(), ['*']);

    $response = getJson('api/v1/bills');
    $response->assertOk();
    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->not()->toContain($billA->id);
});

