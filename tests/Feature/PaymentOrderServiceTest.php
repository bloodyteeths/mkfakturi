<?php

namespace Tests\Feature;

use App\Models\Bill;
use App\Models\BillPayment;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Modules\Mk\Models\PaymentBatch;
use Modules\Mk\Models\PaymentBatchItem;
use Modules\Mk\Services\PaymentOrderService;
use Tests\TestCase;

class PaymentOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentOrderService $service;

    protected Company $company;

    protected User $user;

    protected Supplier $supplier;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PaymentOrderService();

        // Create test data
        $this->user = User::factory()->create(['role' => 'super admin']);
        $this->company = Company::factory()->create(['owner_id' => $this->user->id]);
        $this->currency = Currency::factory()->create(['code' => 'MKD']);
        $this->supplier = Supplier::factory()->create([
            'company_id' => $this->company->id,
            'iban' => 'MK07210001234567890',
            'bic' => 'KOBSMK2X',
            'bank_name' => 'Комерцијална Банка',
        ]);
    }

    // ---------------------------------------------------------------
    // Helper: create a bill with sensible defaults
    // ---------------------------------------------------------------
    protected function createBill(array $overrides = []): Bill
    {
        $defaults = [
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'currency_id' => $this->currency->id,
            'bill_number' => 'BILL-' . fake()->unique()->numerify('####'),
            'bill_date' => now()->subDays(10)->toDateString(),
            'due_date' => now()->subDays(5)->toDateString(),
            'total' => 150000, // 1500.00 in cents
            'sub_total' => 150000,
            'tax' => 0,
            'due_amount' => 150000,
            'discount' => 0,
            'discount_val' => 0,
            'status' => Bill::STATUS_SENT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
            'unique_hash' => \Illuminate\Support\Str::random(20),
        ];

        return Bill::create(array_merge($defaults, $overrides));
    }

    // ===============================================================
    // 1. generateBatchNumber
    // ===============================================================

    /** @test */
    public function test_generate_batch_number_creates_sequential_numbers()
    {
        $year = date('Y');

        // Create first batch — should get NAL-YYYY-0001
        $batch1 = PaymentBatch::create([
            'company_id' => $this->company->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_DRAFT,
        ]);

        $this->assertEquals("NAL-{$year}-0001", $batch1->batch_number);

        // Create second batch — should get NAL-YYYY-0002
        $batch2 = PaymentBatch::create([
            'company_id' => $this->company->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_DRAFT,
        ]);

        $this->assertEquals("NAL-{$year}-0002", $batch2->batch_number);
    }

    /** @test */
    public function test_generate_batch_number_does_not_count_soft_deleted_batches()
    {
        $year = date('Y');

        // Create and soft-delete a batch
        $batch1 = PaymentBatch::create([
            'company_id' => $this->company->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_DRAFT,
        ]);
        $this->assertEquals("NAL-{$year}-0001", $batch1->batch_number);

        // Soft delete it
        $batch1->delete();

        // The next batch should still be NAL-YYYY-0002 because SoftDeletes
        // still includes the soft-deleted row in the default query scope.
        // PaymentBatch uses SoftDeletes, so the generateBatchNumber query
        // (which uses static::where without withTrashed) will NOT see the deleted batch.
        $batch2 = PaymentBatch::create([
            'company_id' => $this->company->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_DRAFT,
        ]);

        // Since the soft-deleted batch is excluded, the next one starts at 1 again
        $this->assertEquals("NAL-{$year}-0001", $batch2->batch_number);
    }

    /** @test */
    public function test_generate_batch_number_is_scoped_per_company()
    {
        $year = date('Y');
        $otherCompany = Company::factory()->create();

        // Create batch for company 1
        $batch1 = PaymentBatch::create([
            'company_id' => $this->company->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_DRAFT,
        ]);
        $this->assertEquals("NAL-{$year}-0001", $batch1->batch_number);

        // Create batch for company 2 — numbering should start fresh
        $batch2 = PaymentBatch::create([
            'company_id' => $otherCompany->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_DRAFT,
        ]);
        $this->assertEquals("NAL-{$year}-0001", $batch2->batch_number);
    }

    // ===============================================================
    // 2. getPayableBills
    // ===============================================================

    /** @test */
    public function test_get_payable_bills_returns_unpaid_and_partially_paid()
    {
        $unpaid = $this->createBill([
            'paid_status' => Bill::PAID_STATUS_UNPAID,
        ]);
        $partial = $this->createBill([
            'paid_status' => Bill::PAID_STATUS_PARTIALLY_PAID,
        ]);

        $results = $this->service->getPayableBills($this->company->id);

        $ids = $results->pluck('id')->toArray();
        $this->assertContains($unpaid->id, $ids);
        $this->assertContains($partial->id, $ids);
    }

    /** @test */
    public function test_get_payable_bills_excludes_drafts()
    {
        $draft = $this->createBill([
            'status' => Bill::STATUS_DRAFT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
        ]);
        $sent = $this->createBill([
            'status' => Bill::STATUS_SENT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
        ]);

        $results = $this->service->getPayableBills($this->company->id);
        $ids = $results->pluck('id')->toArray();

        $this->assertNotContains($draft->id, $ids);
        $this->assertContains($sent->id, $ids);
    }

    /** @test */
    public function test_get_payable_bills_excludes_bills_in_active_batches()
    {
        $bill1 = $this->createBill();
        $bill2 = $this->createBill();

        // Put bill1 in an active (draft) batch
        $batch = PaymentBatch::create([
            'company_id' => $this->company->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_DRAFT,
            'total_amount' => $bill1->total,
            'item_count' => 1,
        ]);
        PaymentBatchItem::create([
            'payment_batch_id' => $batch->id,
            'bill_id' => $bill1->id,
            'creditor_name' => $this->supplier->name,
            'amount' => $bill1->total,
            'currency_code' => 'MKD',
            'status' => PaymentBatchItem::STATUS_PENDING,
        ]);

        $results = $this->service->getPayableBills($this->company->id);
        $ids = $results->pluck('id')->toArray();

        $this->assertNotContains($bill1->id, $ids, 'Bill in active batch should be excluded');
        $this->assertContains($bill2->id, $ids);
    }

    /** @test */
    public function test_get_payable_bills_includes_bills_from_cancelled_batches()
    {
        $bill = $this->createBill();

        // Put bill in a cancelled batch — it should still appear as payable
        $batch = PaymentBatch::create([
            'company_id' => $this->company->id,
            'batch_date' => now()->toDateString(),
            'format' => PaymentBatch::FORMAT_PP30,
            'status' => PaymentBatch::STATUS_CANCELLED,
            'total_amount' => $bill->total,
            'item_count' => 1,
        ]);
        PaymentBatchItem::create([
            'payment_batch_id' => $batch->id,
            'bill_id' => $bill->id,
            'creditor_name' => $this->supplier->name,
            'amount' => $bill->total,
            'currency_code' => 'MKD',
            'status' => PaymentBatchItem::STATUS_PENDING,
        ]);

        $results = $this->service->getPayableBills($this->company->id);
        $ids = $results->pluck('id')->toArray();

        $this->assertContains($bill->id, $ids, 'Bill from cancelled batch should be payable');
    }

    /** @test */
    public function test_get_payable_bills_respects_supplier_filter()
    {
        $supplier2 = Supplier::factory()->create(['company_id' => $this->company->id]);

        $bill1 = $this->createBill(['supplier_id' => $this->supplier->id]);
        $bill2 = $this->createBill(['supplier_id' => $supplier2->id]);

        $results = $this->service->getPayableBills($this->company->id, [
            'supplier_id' => $this->supplier->id,
        ]);
        $ids = $results->pluck('id')->toArray();

        $this->assertContains($bill1->id, $ids);
        $this->assertNotContains($bill2->id, $ids);
    }

    /** @test */
    public function test_get_payable_bills_respects_date_and_amount_filters()
    {
        $billEarly = $this->createBill([
            'due_date' => '2026-01-01',
            'total' => 50000,
        ]);
        $billMid = $this->createBill([
            'due_date' => '2026-02-15',
            'total' => 100000,
        ]);
        $billLate = $this->createBill([
            'due_date' => '2026-04-01',
            'total' => 200000,
        ]);

        // Filter: due_before 2026-03-01
        $results = $this->service->getPayableBills($this->company->id, [
            'due_before' => '2026-03-01',
        ]);
        $ids = $results->pluck('id')->toArray();
        $this->assertContains($billEarly->id, $ids);
        $this->assertContains($billMid->id, $ids);
        $this->assertNotContains($billLate->id, $ids);

        // Filter: due_after 2026-02-01
        $results = $this->service->getPayableBills($this->company->id, [
            'due_after' => '2026-02-01',
        ]);
        $ids = $results->pluck('id')->toArray();
        $this->assertNotContains($billEarly->id, $ids);
        $this->assertContains($billMid->id, $ids);
        $this->assertContains($billLate->id, $ids);

        // Filter: min_amount 800.00, max_amount 1500.00
        // (amounts in currency units — bills store in cents)
        $results = $this->service->getPayableBills($this->company->id, [
            'min_amount' => 800,
            'max_amount' => 1500,
        ]);
        $ids = $results->pluck('id')->toArray();
        $this->assertNotContains($billEarly->id, $ids, '500.00 is below min 800');
        $this->assertContains($billMid->id, $ids, '1000.00 is within range');
        $this->assertNotContains($billLate->id, $ids, '2000.00 is above max 1500');
    }

    // ===============================================================
    // 3. createBatch
    // ===============================================================

    /** @test */
    public function test_create_batch_creates_batch_with_items_from_selected_bills()
    {
        $bill1 = $this->createBill(['total' => 100000]);
        $bill2 = $this->createBill(['total' => 200000]);

        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill1->id, $bill2->id],
            'format' => PaymentBatch::FORMAT_PP30,
            'created_by' => $this->user->id,
        ]);
        $batch = $result['batch'];

        $this->assertInstanceOf(PaymentBatch::class, $batch);
        $this->assertEquals(PaymentBatch::STATUS_DRAFT, $batch->status);
        $this->assertEquals(2, $batch->item_count);
        $this->assertEquals(300000, $batch->total_amount);
        $this->assertCount(2, $batch->items);

        // Verify items reference the correct bills
        $batchBillIds = $batch->items->pluck('bill_id')->sort()->values()->toArray();
        $this->assertEquals(
            collect([$bill1->id, $bill2->id])->sort()->values()->toArray(),
            $batchBillIds
        );
    }

    /** @test */
    public function test_create_batch_rejects_bills_already_in_active_batches()
    {
        $bill1 = $this->createBill();
        $bill2 = $this->createBill();

        // Create first batch with bill1
        $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill1->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);

        // Try to create a second batch with both bills — bill1 should be skipped
        $result2 = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill1->id, $bill2->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch2 = $result2['batch'];

        $this->assertEquals(1, $batch2->item_count);
        $this->assertEquals([$bill2->id], $batch2->items->pluck('bill_id')->toArray());
        $this->assertContains($bill1->id, $result2['skipped_in_batch']);
    }

    /** @test */
    public function test_create_batch_throws_when_all_bills_already_in_active_batches()
    {
        $bill = $this->createBill();

        // Create first batch with the bill
        $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);

        // Try to create another batch with the same bill — should throw
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('All selected bills are already in active payment batches.');

        $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
    }

    /** @test */
    public function test_create_batch_auto_approve_sets_approved_status()
    {
        $bill = $this->createBill();

        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
            'created_by' => $this->user->id,
        ], autoApprove: true);
        $batch = $result['batch'];

        $this->assertEquals(PaymentBatch::STATUS_APPROVED, $batch->status);
        $this->assertEquals($this->user->id, $batch->approved_by);
        $this->assertNotNull($batch->approved_at);
    }

    /** @test */
    public function test_create_batch_skips_fully_paid_bills()
    {
        $paidBill = $this->createBill(['total' => 100000]);
        $unpaidBill = $this->createBill(['total' => 200000]);

        // Manually create a payment that covers paidBill fully
        BillPayment::create([
            'bill_id' => $paidBill->id,
            'company_id' => $this->company->id,
            'payment_number' => 'BPAY-TEST-001',
            'payment_date' => now()->toDateString(),
            'amount' => 100000,
        ]);

        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$paidBill->id, $unpaidBill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        $this->assertEquals(1, $batch->item_count);
        $this->assertEquals(200000, $batch->total_amount);
        $this->assertNotEmpty($result['skipped_bills']);
    }

    /** @test */
    public function test_create_batch_calculates_partial_due_amount()
    {
        $bill = $this->createBill(['total' => 100000]);

        // Pay 40000 (400.00) of the 100000 (1000.00) bill
        BillPayment::create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'payment_number' => 'BPAY-TEST-002',
            'payment_date' => now()->toDateString(),
            'amount' => 40000,
        ]);

        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        // The item amount should be 60000 (remaining due), not 100000
        $item = $batch->items->first();
        $this->assertEquals(60000, $item->amount);
        $this->assertEquals(60000, $batch->total_amount);
    }

    // ===============================================================
    // 4. approve
    // ===============================================================

    /** @test */
    public function test_approve_succeeds_from_draft_status()
    {
        $bill = $this->createBill();
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        $this->assertEquals(PaymentBatch::STATUS_DRAFT, $batch->status);

        $approved = $this->service->approve($batch, $this->user->id);

        $this->assertEquals(PaymentBatch::STATUS_APPROVED, $approved->status);
        $this->assertEquals($this->user->id, $approved->approved_by);
        $this->assertNotNull($approved->approved_at);
    }

    /** @test */
    public function test_approve_rejects_from_exported_status()
    {
        $bill = $this->createBill();
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        // Force status to exported
        $batch->update(['status' => PaymentBatch::STATUS_EXPORTED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be approved');

        $this->service->approve($batch, $this->user->id);
    }

    /** @test */
    public function test_approve_rejects_from_confirmed_status()
    {
        $bill = $this->createBill();
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        $batch->update(['status' => PaymentBatch::STATUS_CONFIRMED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be approved');

        $this->service->approve($batch, $this->user->id);
    }

    /** @test */
    public function test_approve_rejects_from_cancelled_status()
    {
        $bill = $this->createBill();
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        $batch->update(['status' => PaymentBatch::STATUS_CANCELLED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be approved');

        $this->service->approve($batch, $this->user->id);
    }

    // ===============================================================
    // 5. export
    // ===============================================================

    /** @test */
    public function test_export_rejects_from_draft_status()
    {
        $bill = $this->createBill();
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        $this->assertEquals(PaymentBatch::STATUS_DRAFT, $batch->status);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be exported');

        $this->service->export($batch);
    }

    /** @test */
    public function test_export_generates_file_and_updates_status()
    {
        $bill = $this->createBill();
        $createResult = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_CSV,
        ], autoApprove: true);
        $batch = $createResult['batch'];

        $this->assertEquals(PaymentBatch::STATUS_APPROVED, $batch->status);

        $result = $this->service->export($batch);

        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('filename', $result);
        $this->assertArrayHasKey('mime', $result);
        $this->assertNotEmpty($result['content']);
        $this->assertStringContainsString('PAYMENT_', $result['filename']);
        $this->assertEquals('text/csv', $result['mime']);

        // Verify batch status updated
        $batch->refresh();
        $this->assertEquals(PaymentBatch::STATUS_EXPORTED, $batch->status);
        $this->assertNotNull($batch->exported_at);
        $this->assertNotNull($batch->exported_file_path);

        // Verify items updated to exported
        foreach ($batch->items as $item) {
            $this->assertEquals(PaymentBatchItem::STATUS_EXPORTED, $item->status);
        }
    }

    // ===============================================================
    // 6. confirm
    // ===============================================================

    /** @test */
    public function test_confirm_creates_bill_payments_and_updates_bill_status()
    {
        $bill = $this->createBill(['total' => 100000]);

        // Create, approve, and export a batch
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_CSV,
        ], autoApprove: true);
        $batch = $result['batch'];
        $this->service->export($batch);
        $batch->refresh();

        $this->assertEquals(PaymentBatch::STATUS_EXPORTED, $batch->status);

        // Confirm the batch
        $confirmed = $this->service->confirm($batch);

        $this->assertEquals(PaymentBatch::STATUS_CONFIRMED, $confirmed->status);

        // Verify a BillPayment was created
        $payment = BillPayment::where('bill_id', $bill->id)->first();
        $this->assertNotNull($payment, 'BillPayment should be created on confirmation');
        $this->assertEquals(100000, $payment->amount);
        $this->assertEquals($this->company->id, $payment->company_id);
        $this->assertStringContainsString($batch->batch_number, $payment->notes);

        // Verify bill paid status updated
        $bill->refresh();
        $this->assertEquals(Bill::PAID_STATUS_PAID, $bill->paid_status);
    }

    /** @test */
    public function test_confirm_caps_payment_at_remaining_due()
    {
        $bill = $this->createBill(['total' => 100000]);

        // Pay 70000 before batch confirmation
        BillPayment::create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'payment_number' => 'BPAY-PRE-001',
            'payment_date' => now()->toDateString(),
            'amount' => 70000,
        ]);

        // Create batch (amount will be 30000 remaining)
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_CSV,
        ], autoApprove: true);
        $batch = $result['batch'];
        $this->service->export($batch);
        $batch->refresh();

        // Now pay another 20000 externally (leaving only 10000 remaining)
        BillPayment::create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'payment_number' => 'BPAY-PRE-002',
            'payment_date' => now()->toDateString(),
            'amount' => 20000,
        ]);

        // Confirm — the batch item amount was 30000 but only 10000 is still due
        $confirmed = $this->service->confirm($batch);

        // The payment should be capped at 10000
        $batchPayment = BillPayment::where('notes', 'LIKE', "%{$batch->batch_number}%")->first();
        $this->assertNotNull($batchPayment);
        $this->assertEquals(10000, $batchPayment->amount);
    }

    /** @test */
    public function test_confirm_skips_already_fully_paid_bills()
    {
        $bill = $this->createBill(['total' => 100000]);

        // Create batch
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_CSV,
        ], autoApprove: true);
        $batch = $result['batch'];
        $this->service->export($batch);
        $batch->refresh();

        // Fully pay the bill externally before confirmation
        BillPayment::create([
            'bill_id' => $bill->id,
            'company_id' => $this->company->id,
            'payment_number' => 'BPAY-FULL-001',
            'payment_date' => now()->toDateString(),
            'amount' => 100000,
        ]);

        $confirmed = $this->service->confirm($batch);

        // No new BillPayment from batch confirmation (the item should be marked failed)
        $batchPayments = BillPayment::where('notes', 'LIKE', "%{$batch->batch_number}%")->count();
        $this->assertEquals(0, $batchPayments);

        // The item should be marked as failed
        $item = $confirmed->items->first();
        $this->assertEquals(PaymentBatchItem::STATUS_FAILED, $item->status);
    }

    /** @test */
    public function test_confirm_rejects_from_draft_status()
    {
        $bill = $this->createBill();
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        $this->assertEquals(PaymentBatch::STATUS_DRAFT, $batch->status);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be confirmed');

        $this->service->confirm($batch);
    }

    // ===============================================================
    // 7. cancel
    // ===============================================================

    /** @test */
    public function test_cancel_succeeds_from_draft_and_approved_statuses()
    {
        // Cancel from draft
        $bill1 = $this->createBill();
        $resultDraft = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill1->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batchDraft = $resultDraft['batch'];
        $cancelled = $this->service->cancel($batchDraft);
        $this->assertEquals(PaymentBatch::STATUS_CANCELLED, $cancelled->status);

        // Cancel from approved
        $bill2 = $this->createBill();
        $resultApproved = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill2->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batchApproved = $resultApproved['batch'];
        $this->service->approve($batchApproved, $this->user->id);
        $batchApproved->refresh();
        $cancelledApproved = $this->service->cancel($batchApproved);
        $this->assertEquals(PaymentBatch::STATUS_CANCELLED, $cancelledApproved->status);
    }

    /** @test */
    public function test_cancel_rejects_from_exported_or_confirmed_statuses()
    {
        $bill = $this->createBill();
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];
        $batch->update(['status' => PaymentBatch::STATUS_EXPORTED]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('cannot be cancelled');

        $this->service->cancel($batch);
    }

    /** @test */
    public function test_cancel_frees_bills_for_reselection()
    {
        $bill = $this->createBill();

        // Create and cancel a batch
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill->id],
            'format' => PaymentBatch::FORMAT_PP30,
        ]);
        $batch = $result['batch'];

        // Bill should NOT be in payable list while batch is active
        $payable = $this->service->getPayableBills($this->company->id);
        $this->assertNotContains($bill->id, $payable->pluck('id')->toArray());

        // Cancel the batch
        $this->service->cancel($batch);

        // Bill should now be payable again
        $payable = $this->service->getPayableBills($this->company->id);
        $this->assertContains($bill->id, $payable->pluck('id')->toArray());
    }

    // ===============================================================
    // 8. getOverdueSummary
    // ===============================================================

    /** @test */
    public function test_get_overdue_summary_correctly_buckets_bills()
    {
        Carbon::setTestNow(Carbon::parse('2026-03-12 10:00:00'));

        $today = Carbon::today();
        $endOfWeek = Carbon::today()->endOfWeek();

        // Overdue bill: due 10 days ago
        $this->createBill([
            'due_date' => $today->copy()->subDays(10)->toDateString(),
            'total' => 100000,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
        ]);

        // Due this week: due at end of this week (if end of week is in the future)
        // We use a date that's between today and end of week
        $dueThisWeekDate = $today->copy()->addDay();
        if ($dueThisWeekDate->gt($endOfWeek)) {
            // If today is the last day of the week, use today itself
            $dueThisWeekDate = $today;
        }
        $this->createBill([
            'due_date' => $dueThisWeekDate->toDateString(),
            'total' => 200000,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
        ]);

        // Due this month: due in 15 days (within the month but after the week)
        $dueThisMonthDate = $today->copy()->addDays(15);
        $endOfMonth = Carbon::today()->endOfMonth();
        if ($dueThisMonthDate->gt($endOfMonth)) {
            $dueThisMonthDate = $endOfMonth;
        }
        // Only create if the date is after end of week (otherwise it counts as "this week")
        if ($dueThisMonthDate->gt($endOfWeek)) {
            $this->createBill([
                'due_date' => $dueThisMonthDate->toDateString(),
                'total' => 300000,
                'paid_status' => Bill::PAID_STATUS_UNPAID,
            ]);
        }

        $summary = $this->service->getOverdueSummary($this->company->id);

        $this->assertArrayHasKey('overdue', $summary);
        $this->assertArrayHasKey('due_this_week', $summary);
        $this->assertArrayHasKey('due_this_month', $summary);

        // Overdue bucket
        $this->assertEquals(1, $summary['overdue']['count']);
        $this->assertEquals(100000, $summary['overdue']['total']);

        // Due this week bucket
        $this->assertGreaterThanOrEqual(1, $summary['due_this_week']['count']);

        Carbon::setTestNow();
    }

    /** @test */
    public function test_get_overdue_summary_excludes_drafts_and_fully_paid()
    {
        Carbon::setTestNow(Carbon::parse('2026-03-12 10:00:00'));

        // Overdue draft — should NOT be counted
        $this->createBill([
            'due_date' => now()->subDays(5)->toDateString(),
            'total' => 100000,
            'status' => Bill::STATUS_DRAFT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
        ]);

        // Overdue fully-paid bill — paid via BillPayment but paid_status not updated
        // The service recalculates due amount from payments, so this should be excluded
        $paidBill = $this->createBill([
            'due_date' => now()->subDays(5)->toDateString(),
            'total' => 50000,
            'paid_status' => Bill::PAID_STATUS_UNPAID, // status says unpaid
        ]);
        BillPayment::create([
            'bill_id' => $paidBill->id,
            'company_id' => $this->company->id,
            'payment_number' => 'BPAY-SUM-001',
            'payment_date' => now()->toDateString(),
            'amount' => 50000, // fully covers the bill
        ]);

        $summary = $this->service->getOverdueSummary($this->company->id);

        // Draft should be excluded (status = DRAFT)
        // Fully-paid bill has dueAmount <= 0, so it's skipped in the loop
        $this->assertEquals(0, $summary['overdue']['count']);
        $this->assertEquals(0, $summary['overdue']['total']);

        Carbon::setTestNow();
    }

    // ===============================================================
    // Full workflow integration test
    // ===============================================================

    /** @test */
    public function test_full_payment_order_lifecycle()
    {
        $bill1 = $this->createBill(['total' => 100000, 'bill_number' => 'BILL-LIFE-001']);
        $bill2 = $this->createBill(['total' => 250000, 'bill_number' => 'BILL-LIFE-002']);

        // Step 1: Create batch
        $result = $this->service->createBatch($this->company->id, [
            'bill_ids' => [$bill1->id, $bill2->id],
            'format' => PaymentBatch::FORMAT_CSV,
            'created_by' => $this->user->id,
        ]);
        $batch = $result['batch'];
        $this->assertEquals(PaymentBatch::STATUS_DRAFT, $batch->status);
        $this->assertEquals(2, $batch->item_count);
        $this->assertEquals(350000, $batch->total_amount);

        // Step 2: Approve
        $batch = $this->service->approve($batch, $this->user->id);
        $this->assertEquals(PaymentBatch::STATUS_APPROVED, $batch->status);

        // Step 3: Export
        $exportResult = $this->service->export($batch);
        $batch->refresh();
        $this->assertEquals(PaymentBatch::STATUS_EXPORTED, $batch->status);
        $this->assertNotEmpty($exportResult['content']);

        // Step 4: Confirm
        $batch = $this->service->confirm($batch);
        $this->assertEquals(PaymentBatch::STATUS_CONFIRMED, $batch->status);

        // Step 5: Verify bill payment records
        $this->assertEquals(1, BillPayment::where('bill_id', $bill1->id)->count());
        $this->assertEquals(1, BillPayment::where('bill_id', $bill2->id)->count());

        // Step 6: Verify bills are marked as paid
        $bill1->refresh();
        $bill2->refresh();
        $this->assertEquals(Bill::PAID_STATUS_PAID, $bill1->paid_status);
        $this->assertEquals(Bill::PAID_STATUS_PAID, $bill2->paid_status);

        // Step 7: Bills should NOT appear in payable list anymore
        // (they are in a confirmed batch AND are paid)
        $payable = $this->service->getPayableBills($this->company->id);
        $this->assertNotContains($bill1->id, $payable->pluck('id')->toArray());
        $this->assertNotContains($bill2->id, $payable->pluck('id')->toArray());
    }
}

// CLAUDE-CHECKPOINT
