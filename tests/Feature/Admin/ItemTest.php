<?php

use App\Http\Controllers\V1\Admin\Item\ItemsController;
use App\Http\Requests\ItemsRequest;
use App\Models\Item;
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

test('get items', function () {
    $response = getJson('api/v1/items?page=1');

    $response->assertOk();
});

test('create item', function () {
    $item = Item::factory()->raw([
        'taxes' => [
            Tax::factory()->raw(),
            Tax::factory()->raw(),
        ],
    ]);

    $response = postJson('api/v1/items', $item);

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'description' => $item['description'],
        'price' => $item['price'],
        'company_id' => $item['company_id'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'item_id' => $response->getData()->data->id,
    ]);

    $response->assertOk();
});

test('store validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        ItemsController::class,
        'store',
        ItemsRequest::class
    );
});

test('get item', function () {
    $item = Item::factory()->create();

    $response = getJson("api/v1/items/{$item->id}");

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'description' => $item['description'],
        'price' => $item['price'],
        'company_id' => $item['company_id'],
    ]);
});

test('update item', function () {
    $item = Item::factory()->create();

    $update_item = Item::factory()->raw([
        'taxes' => [
            Tax::factory()->raw(),
        ],
    ]);

    $response = putJson('api/v1/items/'.$item->id, $update_item);

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $update_item['name'],
        'description' => $update_item['description'],
        'price' => $update_item['price'],
        'company_id' => $update_item['company_id'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'item_id' => $item->id,
    ]);
});

test('update validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        ItemsController::class,
        'update',
        ItemsRequest::class
    );
});

test('delete multiple items', function () {
    $items = Item::factory()->count(5)->create();

    $data = [
        'ids' => $items->pluck('id'),
    ];

    postJson('/api/v1/items/delete', $data)->assertOk();

    foreach ($items as $item) {
        $this->assertModelMissing($item);
    }
});

test('search items', function () {
    $filters = [
        'page' => 1,
        'limit' => 15,
        'search' => 'doe',
        'price' => 6,
        'unit' => 'kg',
    ];

    $queryString = http_build_query($filters, '', '&');

    $response = getJson('api/v1/items?'.$queryString);

    $response->assertOk();
});

test('create item with fixed amount tax', function () {
    $item = Item::factory()->raw([
        'taxes' => [
            Tax::factory()->raw([
                'calculation_type' => 'fixed',
                'fixed_amount' => 5000,
            ]),
        ],
    ]);

    $response = postJson('api/v1/items', $item);

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'description' => $item['description'],
        'price' => $item['price'],
        'company_id' => $item['company_id'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'item_id' => $response->getData()->data->id,
        'calculation_type' => 'fixed',
        'fixed_amount' => 5000,
    ]);
});

test('create item with SKU and barcode', function () {
    $item = Item::factory()->raw([
        'sku' => 'TEST-SKU-001',
        'barcode' => '1234567890128',
    ]);

    $response = postJson('api/v1/items', $item);

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'sku' => 'TEST-SKU-001',
        'barcode' => '1234567890128',
        'company_id' => $item['company_id'],
    ]);
});

test('SKU uniqueness is scoped by company_id', function () {
    $user = User::find(1);
    $companyId = $user->companies()->first()->id;

    // Create first item with SKU
    $item1 = Item::factory()->raw([
        'sku' => 'UNIQUE-SKU-001',
        'company_id' => $companyId,
    ]);
    postJson('api/v1/items', $item1)->assertOk();

    // Try to create another item with same SKU in same company (should fail)
    $item2 = Item::factory()->raw([
        'sku' => 'UNIQUE-SKU-001',
        'company_id' => $companyId,
    ]);
    $response = postJson('api/v1/items', $item2);

    // Expecting validation error for duplicate SKU
    $response->assertStatus(422);
});

test('can search items by barcode', function () {
    Item::factory()->withBarcode('1111111111116')->create(['name' => 'Item 1']);
    Item::factory()->withBarcode('2222222222229')->create(['name' => 'Item 2']);

    $response = getJson('api/v1/items?search=1111111111116');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
    expect($data[0]->barcode)->toBe('1111111111116');
});

test('can search items by SKU', function () {
    Item::factory()->withSku('SKU-SEARCH-001')->create(['name' => 'Item 1']);
    Item::factory()->withSku('SKU-SEARCH-002')->create(['name' => 'Item 2']);

    $response = getJson('api/v1/items?search=SKU-SEARCH-001');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
    expect($data[0]->sku)->toBe('SKU-SEARCH-001');
});

test('search handles null barcode and SKU gracefully', function () {
    Item::factory()->create(['name' => 'Item without barcode', 'barcode' => null, 'sku' => null]);

    $response = getJson('api/v1/items?search=Item');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
});

test('can update item barcode and SKU', function () {
    $item = Item::factory()->withBarcode('1234567890128')->withSku('OLD-SKU')->create();

    $updateData = [
        'name' => $item->name,
        'description' => $item->description,
        'price' => $item->price,
        'unit_id' => $item->unit_id,
        'company_id' => $item->company_id,
        'sku' => 'NEW-SKU-001',
        'barcode' => '9876543210987',
    ];

    $response = putJson("api/v1/items/{$item->id}", $updateData);

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'id' => $item->id,
        'sku' => 'NEW-SKU-001',
        'barcode' => '9876543210987',
    ]);
});

test('duplicate barcode detection across items', function () {
    Item::factory()->withBarcode('1234567890128')->create();
    Item::factory()->withBarcode('1234567890128')->create();

    $response = getJson('api/v1/items?search=1234567890128');

    $response->assertOk();
    $data = $response->getData()->data;

    // Should find both items with same barcode
    expect($data)->toHaveCount(2);
});

test('multi-field search finds items by name, SKU, or barcode', function () {
    Item::factory()->withBarcode('1111111111116')->withSku('SKU-001')->create(['name' => 'Widget']);
    Item::factory()->withBarcode('2222222222229')->withSku('SKU-002')->create(['name' => 'Gadget']);

    // Search by name
    $response1 = getJson('api/v1/items?search=Widget');
    expect($response1->getData()->data)->toHaveCount(1);

    // Search by SKU
    $response2 = getJson('api/v1/items?search=SKU-002');
    expect($response2->getData()->data)->toHaveCount(1);

    // Search by barcode
    $response3 = getJson('api/v1/items?search=1111111111116');
    expect($response3->getData()->data)->toHaveCount(1);
});

// CLAUDE-CHECKPOINT
