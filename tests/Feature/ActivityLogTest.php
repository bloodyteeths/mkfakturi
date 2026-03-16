<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'admin']);
        $this->company = Company::factory()->create(['owner_id' => $this->owner->id]);

        DB::table('user_company')->insert([
            'user_id' => $this->owner->id,
            'company_id' => $this->company->id,
        ]);
    }

    // ─── HasAuditing Trait Tests ──────────────────────────────────

    /** @test */
    public function customer_model_has_auditing_trait()
    {
        $this->assertTrue(
            in_array(\App\Traits\HasAuditing::class, class_uses_recursive(Customer::class)),
            'Customer model should use HasAuditing trait'
        );
    }

    /** @test */
    public function invoice_model_has_auditing_trait()
    {
        $this->assertTrue(
            in_array(\App\Traits\HasAuditing::class, class_uses_recursive(Invoice::class)),
            'Invoice model should use HasAuditing trait'
        );
    }

    /** @test */
    public function item_model_has_auditing_trait()
    {
        $this->assertTrue(
            in_array(\App\Traits\HasAuditing::class, class_uses_recursive(Item::class)),
            'Item model should use HasAuditing trait'
        );
    }

    /** @test */
    public function user_model_has_auditing_trait()
    {
        $this->assertTrue(
            in_array(\App\Traits\HasAuditing::class, class_uses_recursive(User::class)),
            'User model should use HasAuditing trait'
        );
    }

    /** @test */
    public function user_model_excludes_sensitive_fields()
    {
        $user = new User();
        $excludeFields = $user->getAuditExclude();

        $this->assertContains('password', $excludeFields);
        $this->assertContains('remember_token', $excludeFields);
        $this->assertContains('api_token', $excludeFields);
    }

    /** @test */
    public function company_model_has_auditing_trait()
    {
        $this->assertTrue(
            in_array(\App\Traits\HasAuditing::class, class_uses_recursive(Company::class)),
            'Company model should use HasAuditing trait'
        );
    }

    /** @test */
    public function creating_customer_creates_audit_log()
    {
        $this->actingAs($this->owner);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Test Customer',
            'type' => 'individual',
            'currency_id' => 1,
        ]);

        $log = AuditLog::where('auditable_type', Customer::class)
            ->where('auditable_id', $customer->id)
            ->where('event', 'created')
            ->first();

        $this->assertNotNull($log, 'Audit log should be created when customer is created');
        $this->assertEquals($this->company->id, $log->company_id);
        $this->assertEquals($this->owner->id, $log->user_id);
        $this->assertEquals($this->owner->name, $log->user_name);
    }

    /** @test */
    public function updating_customer_creates_audit_log_with_changes()
    {
        $this->actingAs($this->owner);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Original Name',
            'type' => 'individual',
            'currency_id' => 1,
        ]);

        $customer->update(['name' => 'Updated Name']);

        $log = AuditLog::where('auditable_type', Customer::class)
            ->where('auditable_id', $customer->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($log, 'Audit log should be created when customer is updated');
        $this->assertContains('name', $log->changed_fields);
    }

    /** @test */
    public function deleting_customer_creates_audit_log()
    {
        $this->actingAs($this->owner);

        $customer = Customer::create([
            'company_id' => $this->company->id,
            'name' => 'Delete Me',
            'type' => 'individual',
            'currency_id' => 1,
        ]);

        $customerId = $customer->id;
        $customer->delete();

        $log = AuditLog::where('auditable_type', Customer::class)
            ->where('auditable_id', $customerId)
            ->where('event', 'deleted')
            ->first();

        $this->assertNotNull($log, 'Audit log should be created when customer is deleted');
    }

    // ─── AuditLogPolicy Tests ──────────────────────────────────

    /** @test */
    public function owner_can_view_audit_logs()
    {
        $this->actingAs($this->owner);

        $response = $this->getJson('/api/v1/audit-logs', [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function partner_can_view_audit_logs_for_managed_company()
    {
        $partnerUser = User::factory()->create(['role' => 'partner']);

        $partnerId = DB::table('partners')->insertGetId([
            'user_id' => $partnerUser->id,
            'name' => 'Test Partner',
            'email' => 'partner-' . $partnerUser->id . '@test.com',
            'commission_rate' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('partner_company_links')->insert([
            'partner_id' => $partnerId,
            'company_id' => $this->company->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($partnerUser);

        $response = $this->getJson('/api/v1/audit-logs', [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function partner_cannot_view_audit_logs_for_unmanaged_company()
    {
        $partnerUser = User::factory()->create(['role' => 'partner']);

        DB::table('partners')->insert([
            'user_id' => $partnerUser->id,
            'name' => 'Test Partner',
            'email' => 'partner-' . $partnerUser->id . '@test.com',
            'commission_rate' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($partnerUser);

        $response = $this->getJson('/api/v1/audit-logs', [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(403);
    }

    // ─── AuditLogController Filter Tests ──────────────────────────────────

    /** @test */
    public function audit_logs_can_be_filtered_by_event_type()
    {
        $this->actingAs($this->owner);

        // Create some audit logs
        AuditLog::create([
            'company_id' => $this->company->id,
            'auditable_type' => Customer::class,
            'auditable_id' => 1,
            'event' => 'created',
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'old_values' => [],
            'new_values' => ['name' => 'Test'],
            'changed_fields' => ['name'],
        ]);

        AuditLog::create([
            'company_id' => $this->company->id,
            'auditable_type' => Customer::class,
            'auditable_id' => 1,
            'event' => 'updated',
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'old_values' => ['name' => 'Test'],
            'new_values' => ['name' => 'Updated'],
            'changed_fields' => ['name'],
        ]);

        $response = $this->getJson('/api/v1/audit-logs?event=created', [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $log) {
            $this->assertEquals('created', $log['event']);
        }
    }

    /** @test */
    public function audit_logs_can_be_filtered_by_entity_type()
    {
        $this->actingAs($this->owner);

        AuditLog::create([
            'company_id' => $this->company->id,
            'auditable_type' => Customer::class,
            'auditable_id' => 1,
            'event' => 'created',
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'old_values' => [],
            'new_values' => ['name' => 'Customer'],
            'changed_fields' => ['name'],
        ]);

        AuditLog::create([
            'company_id' => $this->company->id,
            'auditable_type' => Item::class,
            'auditable_id' => 1,
            'event' => 'created',
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'old_values' => [],
            'new_values' => ['name' => 'Item'],
            'changed_fields' => ['name'],
        ]);

        $response = $this->getJson('/api/v1/audit-logs?auditable_type=' . urlencode(Customer::class), [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $log) {
            $this->assertEquals(Customer::class, $log['auditable_type']);
        }
    }

    /** @test */
    public function audit_logs_can_be_filtered_by_user()
    {
        $this->actingAs($this->owner);

        $user2 = User::factory()->create();

        AuditLog::create([
            'company_id' => $this->company->id,
            'auditable_type' => Customer::class,
            'auditable_id' => 1,
            'event' => 'created',
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'old_values' => [],
            'new_values' => [],
            'changed_fields' => [],
        ]);

        AuditLog::create([
            'company_id' => $this->company->id,
            'auditable_type' => Customer::class,
            'auditable_id' => 2,
            'event' => 'created',
            'user_id' => $user2->id,
            'user_name' => $user2->name,
            'old_values' => [],
            'new_values' => [],
            'changed_fields' => [],
        ]);

        $response = $this->getJson('/api/v1/audit-logs?user_id=' . $this->owner->id, [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $log) {
            $this->assertEquals($this->owner->id, $log['user_id']);
        }
    }

    /** @test */
    public function audit_log_api_returns_paginated_results()
    {
        $this->actingAs($this->owner);

        // Create 25 audit logs to exceed default per_page of 20
        for ($i = 0; $i < 25; $i++) {
            AuditLog::create([
                'company_id' => $this->company->id,
                'auditable_type' => Customer::class,
                'auditable_id' => $i + 1,
                'event' => 'created',
                'user_id' => $this->owner->id,
                'user_name' => $this->owner->name,
                'old_values' => [],
                'new_values' => [],
                'changed_fields' => [],
            ]);
        }

        $response = $this->getJson('/api/v1/audit-logs?per_page=10', [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(200);
        $this->assertCount(10, $response->json('data'));
        $this->assertEquals(25, $response->json('meta.total'));
    }

    /** @test */
    public function audit_logs_are_scoped_to_company()
    {
        $this->actingAs($this->owner);

        $company2 = Company::factory()->create(['owner_id' => $this->owner->id]);

        AuditLog::create([
            'company_id' => $this->company->id,
            'auditable_type' => Customer::class,
            'auditable_id' => 1,
            'event' => 'created',
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'old_values' => [],
            'new_values' => [],
            'changed_fields' => [],
        ]);

        AuditLog::create([
            'company_id' => $company2->id,
            'auditable_type' => Customer::class,
            'auditable_id' => 2,
            'event' => 'created',
            'user_id' => $this->owner->id,
            'user_name' => $this->owner->name,
            'old_values' => [],
            'new_values' => [],
            'changed_fields' => [],
        ]);

        $response = $this->getJson('/api/v1/audit-logs', [
            'company' => $this->company->id,
        ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        foreach ($data as $log) {
            $this->assertEquals($this->company->id, $log['company_id']);
        }
    }
}
