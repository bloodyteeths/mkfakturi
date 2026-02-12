<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Deadline;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Feature Tests for Deadline Tracking (P8-02)
 *
 * Tests:
 * - Recurring deadline generation with correct dates
 * - Partner access to managed company deadlines
 * - Deadline completion workflow
 * - Overdue detection and status updates
 * - Reminder notification sending
 * - Summary KPI calculations
 * - System deadline deletion protection
 */
class DeadlineTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected User $partnerUser;

    protected Partner $partner;

    protected Company $company;

    protected Company $company2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create partner user
        $this->partnerUser = User::factory()->create([
            'email' => 'partner@test.com',
            'role' => 'partner',
        ]);

        // Create partner
        $this->partner = Partner::factory()->create([
            'user_id' => $this->partnerUser->id,
            'is_active' => true,
            'commission_rate' => 20.0,
        ]);

        // Create companies
        $this->company = Company::factory()->create(['name' => 'Test Company A']);
        $this->company2 = Company::factory()->create(['name' => 'Test Company B']);

        // Link partner to companies
        $this->partner->companies()->attach($this->company->id, [
            'is_active' => true,
            'status' => 'active',
            'assigned_at' => now(),
        ]);

        $this->partner->companies()->attach($this->company2->id, [
            'is_active' => true,
            'status' => 'active',
            'assigned_at' => now(),
        ]);
    }

    /**
     * Test that generate-recurring command creates deadlines with correct dates.
     */
    public function test_generate_recurring_creates_correct_dates(): void
    {
        // Create a recurring template for monthly_25
        Deadline::create([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'title' => 'VAT Return',
            'title_mk' => 'ДДВ пријава',
            'deadline_type' => Deadline::TYPE_VAT,
            'due_date' => Carbon::create(2026, 2, 25),
            'status' => Deadline::STATUS_UPCOMING,
            'reminder_days_before' => [7, 3, 1],
            'is_recurring' => true,
            'recurrence_rule' => 'monthly_25',
        ]);

        // Run the command
        $this->artisan('deadlines:generate-recurring')
            ->expectsOutputToContain('Generating recurring deadline instances')
            ->assertExitCode(0);

        // Verify a deadline was created for next month's 25th
        $nextMonth = Carbon::now()->addMonthNoOverflow();
        $expectedDate = Carbon::create($nextMonth->year, $nextMonth->month, 25)->toDateString();

        $this->assertDatabaseHas('deadlines', [
            'company_id' => $this->company->id,
            'deadline_type' => Deadline::TYPE_VAT,
            'due_date' => $expectedDate,
            'is_recurring' => true,
        ]);
    }

    /**
     * Test that partner can see all managed company deadlines.
     */
    public function test_partner_sees_all_managed_company_deadlines(): void
    {
        // Create deadlines for both companies
        Deadline::create([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'title' => 'VAT Return A',
            'deadline_type' => Deadline::TYPE_VAT,
            'due_date' => Carbon::now()->addDays(10),
            'status' => Deadline::STATUS_UPCOMING,
        ]);

        Deadline::create([
            'company_id' => $this->company2->id,
            'partner_id' => $this->partner->id,
            'title' => 'MPIN Filing B',
            'deadline_type' => Deadline::TYPE_MPIN,
            'due_date' => Carbon::now()->addDays(5),
            'status' => Deadline::STATUS_UPCOMING,
        ]);

        // Create a deadline for an unmanaged company
        $unlinkedCompany = Company::factory()->create(['name' => 'Unlinked Company']);
        Deadline::create([
            'company_id' => $unlinkedCompany->id,
            'title' => 'Should Not See',
            'deadline_type' => Deadline::TYPE_CUSTOM,
            'due_date' => Carbon::now()->addDays(3),
            'status' => Deadline::STATUS_UPCOMING,
        ]);

        $response = $this->actingAs($this->partnerUser)
            ->getJson('/api/v1/partner/deadlines');

        $response->assertStatus(200);
        $data = $response->json('data');

        // Should see 2 deadlines (not the unlinked one)
        $this->assertCount(2, $data);

        $titles = collect($data)->pluck('title')->toArray();
        $this->assertContains('MPIN Filing B', $titles);
        $this->assertContains('VAT Return A', $titles);
        $this->assertNotContains('Should Not See', $titles);
    }

    /**
     * Test that a deadline can be completed.
     */
    public function test_can_complete_deadline(): void
    {
        $deadline = Deadline::create([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'title' => 'VAT Return',
            'deadline_type' => Deadline::TYPE_VAT,
            'due_date' => Carbon::now()->addDays(5),
            'status' => Deadline::STATUS_UPCOMING,
        ]);

        $response = $this->actingAs($this->partnerUser)
            ->postJson("/api/v1/partner/deadlines/{$deadline->id}/complete");

        $response->assertStatus(200);
        $response->assertJsonPath('data.status', 'completed');
        $response->assertJsonPath('message', 'Deadline marked as completed.');

        $deadline->refresh();
        $this->assertEquals(Deadline::STATUS_COMPLETED, $deadline->status);
        $this->assertNotNull($deadline->completed_at);
        $this->assertEquals($this->partnerUser->id, $deadline->completed_by);
    }

    /**
     * Test that overdue detection updates status correctly.
     */
    public function test_overdue_detection_updates_status(): void
    {
        // Create a deadline that was due yesterday (insert directly to avoid boot hook)
        $deadline = new Deadline;
        $deadline->company_id = $this->company->id;
        $deadline->partner_id = $this->partner->id;
        $deadline->title = 'Past Deadline';
        $deadline->deadline_type = Deadline::TYPE_MPIN;
        $deadline->due_date = Carbon::yesterday();
        $deadline->status = Deadline::STATUS_UPCOMING;
        $deadline->saveQuietly();

        // Run the reminders command which also updates overdue statuses
        $this->artisan('deadlines:send-reminders')
            ->assertExitCode(0);

        $deadline->refresh();
        $this->assertEquals(Deadline::STATUS_OVERDUE, $deadline->status);
    }

    /**
     * Test that reminder notification is sent within the reminder window.
     */
    public function test_reminder_notification_sent_within_window(): void
    {
        Notification::fake();

        // Create company owner
        $owner = User::factory()->create(['email' => 'owner@test.com']);
        $this->company->update(['owner_id' => $owner->id]);

        // Create a deadline due in 3 days (within the default [7, 3, 1] window)
        Deadline::create([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'title' => 'Upcoming Deadline',
            'deadline_type' => Deadline::TYPE_VAT,
            'due_date' => Carbon::today()->addDays(3),
            'status' => Deadline::STATUS_UPCOMING,
            'reminder_days_before' => [7, 3, 1],
            'last_reminder_sent_at' => null,
        ]);

        $this->artisan('deadlines:send-reminders')
            ->assertExitCode(0);

        // Owner should receive a notification
        Notification::assertSentTo($owner, DeadlineReminderNotification::class);

        // Partner user should also receive a notification
        Notification::assertSentTo($this->partnerUser, DeadlineReminderNotification::class);
    }

    /**
     * Test that summary returns correct KPIs.
     */
    public function test_summary_returns_correct_kpis(): void
    {
        // Create an overdue deadline
        $overdue = new Deadline;
        $overdue->company_id = $this->company->id;
        $overdue->partner_id = $this->partner->id;
        $overdue->title = 'Overdue';
        $overdue->deadline_type = Deadline::TYPE_VAT;
        $overdue->due_date = Carbon::yesterday();
        $overdue->status = Deadline::STATUS_OVERDUE;
        $overdue->saveQuietly();

        // Create a deadline due this week
        Deadline::create([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'title' => 'Due This Week',
            'deadline_type' => Deadline::TYPE_MPIN,
            'due_date' => Carbon::today()->addDays(2)->min(Carbon::today()->endOfWeek()),
            'status' => Deadline::STATUS_UPCOMING,
        ]);

        // Create a completed deadline this month
        Deadline::create([
            'company_id' => $this->company2->id,
            'partner_id' => $this->partner->id,
            'title' => 'Completed',
            'deadline_type' => Deadline::TYPE_CIT,
            'due_date' => Carbon::today(),
            'status' => Deadline::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
            'completed_by' => $this->partnerUser->id,
        ]);

        $response = $this->actingAs($this->partnerUser)
            ->getJson('/api/v1/partner/deadlines/summary');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals(1, $data['overdue_count']);
        $this->assertGreaterThanOrEqual(1, $data['due_this_week']);
        $this->assertEquals(1, $data['completed_this_month']);
    }

    /**
     * Test that system recurring deadlines cannot be deleted.
     */
    public function test_cannot_delete_system_recurring_deadline(): void
    {
        $deadline = Deadline::create([
            'company_id' => $this->company->id,
            'partner_id' => $this->partner->id,
            'title' => 'VAT Return',
            'title_mk' => 'ДДВ пријава',
            'deadline_type' => Deadline::TYPE_VAT,
            'due_date' => Carbon::now()->addDays(20),
            'status' => Deadline::STATUS_UPCOMING,
            'is_recurring' => true,
            'recurrence_rule' => 'monthly_25',
        ]);

        $response = $this->actingAs($this->partnerUser)
            ->deleteJson("/api/v1/partner/deadlines/{$deadline->id}");

        $response->assertStatus(403);
        $response->assertJsonPath('error', 'System recurring deadlines cannot be deleted.');

        // Verify the deadline still exists
        $this->assertDatabaseHas('deadlines', ['id' => $deadline->id]);
    }
}
// CLAUDE-CHECKPOINT
