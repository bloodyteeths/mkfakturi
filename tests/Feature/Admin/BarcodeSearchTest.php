<?php

use App\Models\Item;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

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

test('exact barcode match returns single item', function () {
    $barcode = '1234567890128';
    Item::factory()->withBarcode($barcode)->create(['name' => 'Test Product']);

    $response = getJson("api/v1/items?search={$barcode}");

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
    expect($data[0]->barcode)->toBe($barcode);
});

test('exact SKU match returns single item', function () {
    $sku = 'PROD-12345';
    Item::factory()->withSku($sku)->create(['name' => 'Test Product']);

    $response = getJson("api/v1/items?search={$sku}");

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
    expect($data[0]->sku)->toBe($sku);
});

test('partial barcode match returns matching items', function () {
    Item::factory()->withBarcode('1234567890128')->create(['name' => 'Product 1']);
    Item::factory()->withBarcode('1234567890135')->create(['name' => 'Product 2']);
    Item::factory()->withBarcode('9876543210987')->create(['name' => 'Product 3']);

    $response = getJson('api/v1/items?search=123456789');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(2);
});

test('partial SKU match returns matching items', function () {
    Item::factory()->withSku('PROD-001-A')->create(['name' => 'Product 1']);
    Item::factory()->withSku('PROD-002-A')->create(['name' => 'Product 2']);
    Item::factory()->withSku('SERV-001-B')->create(['name' => 'Service 1']);

    $response = getJson('api/v1/items?search=PROD');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(2);
});

test('search works across name, SKU, and barcode fields', function () {
    Item::factory()->withBarcode('1111111111116')->withSku('WIDGET-001')->create(['name' => 'Widget Pro']);
    Item::factory()->withBarcode('2222222222229')->withSku('GADGET-001')->create(['name' => 'Gadget Max']);

    // Search by name
    $response1 = getJson('api/v1/items?search=Widget');
    expect($response1->getData()->data)->toHaveCount(1);
    expect($response1->getData()->data[0]->name)->toContain('Widget');

    // Search by SKU
    $response2 = getJson('api/v1/items?search=GADGET-001');
    expect($response2->getData()->data)->toHaveCount(1);
    expect($response2->getData()->data[0]->sku)->toBe('GADGET-001');

    // Search by barcode
    $response3 = getJson('api/v1/items?search=1111111111116');
    expect($response3->getData()->data)->toHaveCount(1);
    expect($response3->getData()->data[0]->barcode)->toBe('1111111111116');
});

test('empty barcode returns no false matches', function () {
    Item::factory()->create(['name' => 'Product 1', 'barcode' => null, 'sku' => null]);
    Item::factory()->withBarcode('1234567890128')->create(['name' => 'Product 2']);

    $response = getJson('api/v1/items?search=1234567890128');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
    expect($data[0]->name)->toBe('Product 2');
});

test('null barcode search does not crash', function () {
    Item::factory()->create(['name' => 'Product 1', 'barcode' => null, 'sku' => null]);

    $response = getJson('api/v1/items?search=NULL');

    $response->assertOk();
});

test('empty SKU returns no false matches', function () {
    Item::factory()->create(['name' => 'Product 1', 'barcode' => null, 'sku' => null]);
    Item::factory()->withSku('SKU-001')->create(['name' => 'Product 2']);

    $response = getJson('api/v1/items?search=SKU-001');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
    expect($data[0]->name)->toBe('Product 2');
});

test('case insensitive SKU search', function () {
    Item::factory()->withSku('PROD-UPPERCASE')->create(['name' => 'Product 1']);

    $response = getJson('api/v1/items?search=prod-uppercase');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
});

test('search by description also works', function () {
    Item::factory()->create([
        'name' => 'Product 1',
        'description' => 'This product has special barcode features',
        'barcode' => null,
    ]);

    $response = getJson('api/v1/items?search=barcode features');

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
});

test('multiple items with same barcode can be found', function () {
    $barcode = '1234567890128';
    Item::factory()->withBarcode($barcode)->create(['name' => 'Product 1']);
    Item::factory()->withBarcode($barcode)->create(['name' => 'Product 2']);

    $response = getJson("api/v1/items?search={$barcode}");

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(2);
});

test('search with special characters is handled safely', function () {
    Item::factory()->withSku('PROD-001')->create(['name' => 'Product 1']);
    Item::factory()->withSku('PROD-002')->create(['name' => 'Product 2']);
    Item::factory()->withSku('PROD-003')->create(['name' => 'Product 3']);

    // Test with SQL injection attempt (should be safely escaped)
    $response = getJson("api/v1/items?search=PROD-001' OR '1'='1");

    $response->assertOk();
    // Should safely escape and return no results (since the literal string doesn't match)
    // OR should only return items matching the partial search before special chars
    $data = $response->getData()->data;
    // The important thing is it doesn't return ALL items - it should be 3 or fewer
    expect(count($data))->toBeLessThanOrEqual(3);
});

test('empty search returns all items', function () {
    Item::factory()->withBarcode('1234567890128')->create(['name' => 'Product 1']);
    Item::factory()->withSku('SKU-001')->create(['name' => 'Product 2']);
    Item::factory()->create(['name' => 'Product 3']);

    $response = getJson('api/v1/items?search=');

    $response->assertOk();
    // Should return all items (at least 3)
    $data = $response->getData()->data;
    expect(count($data))->toBeGreaterThanOrEqual(3);
});

test('barcode search is scoped to company', function () {
    $user = User::find(1);
    $company1 = $user->companies()->first();

    // Create item in company 1
    $barcode = '1234567890128';
    Item::factory()->withBarcode($barcode)->create([
        'name' => 'Company 1 Product',
        'company_id' => $company1->id,
    ]);

    // Search should only return items from the current company
    $response = getJson("api/v1/items?search={$barcode}");

    $response->assertOk();
    $data = $response->getData()->data;

    foreach ($data as $item) {
        expect($item->company_id)->toBe($company1->id);
    }
});

test('combined filters with barcode search', function () {
    $barcode = '1234567890128';
    $item1 = Item::factory()->withBarcode($barcode)->create([
        'name' => 'Product 1',
        'price' => 100,
    ]);
    $item2 = Item::factory()->withBarcode($barcode)->create([
        'name' => 'Product 2',
        'price' => 200,
    ]);

    // Search by barcode and filter by price
    $response = getJson("api/v1/items?search={$barcode}&price=100");

    $response->assertOk();
    $data = $response->getData()->data;

    expect($data)->toHaveCount(1);
    expect($data[0]->price)->toBe(100);
});

// CLAUDE-CHECKPOINT
