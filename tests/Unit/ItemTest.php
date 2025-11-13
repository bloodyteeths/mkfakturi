<?php

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Item;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
});

test('an item belongs to unit', function () {
    $item = Item::factory()->forUnit()->create();

    $this->assertTrue($item->unit()->exists());
});

test('an item has many taxes', function () {
    $item = Item::factory()->hasTaxes(5)->create();

    $this->assertCount(5, $item->taxes);
    $this->assertTrue($item->taxes()->exists());
});

test('an item has many invoice items', function () {
    $item = Item::factory()->has(InvoiceItem::factory()->count(5)->state([
        'invoice_id' => Invoice::factory(),
    ]))->create();

    $this->assertCount(5, $item->invoiceItems);

    $this->assertTrue($item->invoiceItems()->exists());
});

test('an item has many estimate items', function () {
    $item = Item::factory()->has(EstimateItem::factory()
        ->count(5)
        ->state([
            'estimate_id' => Estimate::factory(),
        ]))
        ->create();

    $this->assertCount(5, $item->estimateItems);

    $this->assertTrue($item->estimateItems()->exists());
});

test('an item can have a barcode', function () {
    $barcode = '1234567890128'; // Valid EAN-13
    $item = Item::factory()->withBarcode($barcode)->create();

    expect($item->barcode)->toBe($barcode);
});

test('an item can have an auto-generated barcode', function () {
    $item = Item::factory()->withBarcode()->create();

    expect($item->barcode)->not()->toBeNull();
    expect($item->barcode)->toHaveLength(13);
    expect($item->barcode)->toMatch('/^\d{13}$/');
});

test('an item can have a SKU', function () {
    $sku = 'CUSTOM-SKU-001';
    $item = Item::factory()->withSku($sku)->create();

    expect($item->sku)->toBe($sku);
});

test('an item can have an auto-generated SKU', function () {
    $item = Item::factory()->withSku()->create();

    expect($item->sku)->not()->toBeNull();
    expect($item->sku)->toMatch('/^SKU-\d{4}-[A-Z0-9]{4}$/');
});

test('an item can have both barcode and SKU', function () {
    $item = Item::factory()
        ->withBarcode('9876543210987')
        ->withSku('TEST-SKU-123')
        ->create();

    expect($item->barcode)->toBe('9876543210987');
    expect($item->sku)->toBe('TEST-SKU-123');
});

test('item search scope includes barcode', function () {
    $item1 = Item::factory()->withBarcode('1234567890128')->create(['name' => 'Test Item 1']);
    $item2 = Item::factory()->withBarcode('9876543210987')->create(['name' => 'Test Item 2']);

    $results = Item::whereSearch('1234567890128')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($item1->id);
});

test('item search scope includes SKU', function () {
    $item1 = Item::factory()->withSku('SKU-001')->create(['name' => 'Test Item 1']);
    $item2 = Item::factory()->withSku('SKU-002')->create(['name' => 'Test Item 2']);

    $results = Item::whereSearch('SKU-001')->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($item1->id);
});

test('item search scope works with partial matches', function () {
    $item1 = Item::factory()->withSku('PROD-123-ABC')->create(['name' => 'Widget 1']);
    $item2 = Item::factory()->withSku('PROD-456-DEF')->create(['name' => 'Widget 2']);
    $item3 = Item::factory()->withBarcode('1234567890128')->create(['name' => 'Gadget 3']);

    // Search by SKU prefix - should find items 1 and 2 only
    $results = Item::whereSearch('PROD-')->get();

    expect($results)->toHaveCount(2);
});

test('generated EAN-13 barcode has valid check digit', function () {
    $item = Item::factory()->withBarcode()->create();

    // Validate EAN-13 check digit
    $barcode = $item->barcode;
    $sum = 0;
    for ($i = 0; $i < 12; $i++) {
        $sum += (int) $barcode[$i] * (($i % 2 === 0) ? 1 : 3);
    }
    $calculatedCheckDigit = (10 - ($sum % 10)) % 10;
    $actualCheckDigit = (int) $barcode[12];

    expect($actualCheckDigit)->toBe($calculatedCheckDigit);
});

// CLAUDE-CHECKPOINT
