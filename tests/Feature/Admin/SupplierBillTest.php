<?php

use App\Http\Controllers\V1\Admin\AccountsPayable\BillsController;
use App\Http\Controllers\V1\Admin\AccountsPayable\SuppliersController;
use App\Http\Requests\BillRequest;
use App\Http\Requests\SupplierRequest;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Supplier;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\deleteJson;
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

test('suppliers index returns data', function () {
    Supplier::factory()->create();

    $response = getJson('api/v1/suppliers');

    $response->assertOk();
});

test('create supplier', function () {
    $payload = Supplier::factory()->raw();

    $response = postJson('api/v1/suppliers', $payload);

    $response->assertCreated();

    $this->assertDatabaseHas('suppliers', [
        'name' => $payload['name'],
    ]);
});

test('update supplier', function () {
    $supplier = Supplier::factory()->create();
    $payload = Supplier::factory()->raw();

    $response = putJson("api/v1/suppliers/{$supplier->id}", $payload);

    $response->assertOk();

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => $payload['name'],
    ]);
});

test('delete suppliers', function () {
    $suppliers = Supplier::factory()->count(2)->create();

    $response = postJson('api/v1/suppliers/delete', [
        'ids' => $suppliers->pluck('id')->all(),
    ]);

    $response->assertOk();

    foreach ($suppliers as $supplier) {
        $this->assertSoftDeleted('suppliers', ['id' => $supplier->id]);
    }
});

test('supplier controller uses form request', function () {
    $this->assertActionUsesFormRequest(
        SuppliersController::class,
        'store',
        SupplierRequest::class
    );
    $this->assertActionUsesFormRequest(
        SuppliersController::class,
        'update',
        SupplierRequest::class
    );
});

test('create bill', function () {
    $bill = Bill::factory()->raw([
        'items' => [BillItem::factory()->raw()],
        'taxes' => [Tax::factory()->raw()],
    ]);

    $response = postJson('api/v1/bills', $bill);

    $response->assertCreated();

    $this->assertDatabaseHas('bills', [
        'bill_number' => $bill['bill_number'],
        'total' => $bill['total'],
    ]);

    $this->assertDatabaseHas('bill_items', [
        'name' => $bill['items'][0]['name'],
    ]);
});

test('update bill', function () {
    $billModel = Bill::factory()->create();

    $bill = Bill::factory()->raw([
        'items' => [BillItem::factory()->raw()],
        'taxes' => [Tax::factory()->raw()],
    ]);

    $response = putJson("api/v1/bills/{$billModel->id}", $bill);

    $response->assertOk();

    $this->assertDatabaseHas('bills', [
        'id' => $billModel->id,
        'bill_number' => $bill['bill_number'],
    ]);
});

test('delete bills', function () {
    $bills = Bill::factory()->count(2)->create();

    $response = postJson('api/v1/bills/delete', [
        'ids' => $bills->pluck('id')->all(),
    ]);

    $response->assertOk();

    foreach ($bills as $bill) {
        $this->assertSoftDeleted('bills', ['id' => $bill->id]);
    }
});

test('bill controller uses form request', function () {
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

