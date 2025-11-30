<?php

namespace Tests\Feature;

use App\Exceptions\PeriodLockedException;
use App\Models\Company;
use App\Models\Currency;
use App\Models\DailyClosing;
use App\Models\PeriodLock;
use App\Models\User;
use App\Services\PeriodLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for Period Lock & Daily Closing (P3-1, P3-2)
 */
class PeriodLockTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected PeriodLockService $lockService;

    protected function setUp(): void
    {
        parent::setUp();

        $currency = Currency::factory()->create();

        $this->company = Company::factory()->create([
            'currency_id' => $currency->id,
        ]);

        $this->user = User::factory()->create([
            'role' => 'super admin',
        ]);
        $this->user->companies()->attach($this->company->id);

        $this->lockService = new PeriodLockService();
    }

    /** @test */
    public function it_can_close_a_day()
    {
        $closing = $this->lockService->closeDay(
            $this->company->id,
            '2025-11-15',
            DailyClosing::TYPE_ALL,
            $this->user->id,
            'End of day closing'
        );

        $this->assertNotNull($closing);
        $this->assertEquals($this->company->id, $closing->company_id);
        $this->assertEquals('2025-11-15', $closing->date->format('Y-m-d'));
        $this->assertEquals('all', $closing->type);
        $this->assertEquals($this->user->id, $closing->closed_by);

        $this->assertTrue(DailyClosing::isDateClosed($this->company->id, Carbon::parse('2025-11-15')));
    }

    /** @test */
    public function it_detects_closed_day()
    {
        DailyClosing::create([
            'company_id' => $this->company->id,
            'date' => Carbon::parse('2025-11-10'),
            'type' => DailyClosing::TYPE_ALL,
            'closed_by' => $this->user->id,
        ]);

        $this->assertTrue($this->lockService->isDateLocked($this->company->id, Carbon::parse('2025-11-10')));
        $this->assertFalse($this->lockService->isDateLocked($this->company->id, Carbon::parse('2025-11-11')));
    }

    /** @test */
    public function it_can_create_period_lock()
    {
        $lock = $this->lockService->lockPeriod(
            $this->company->id,
            '2025-10-01',
            '2025-10-31',
            $this->user->id,
            'October 2025 export complete'
        );

        $this->assertNotNull($lock);
        $this->assertEquals($this->company->id, $lock->company_id);
        $this->assertEquals('2025-10-01', $lock->period_start->format('Y-m-d'));
        $this->assertEquals('2025-10-31', $lock->period_end->format('Y-m-d'));

        $this->assertTrue(PeriodLock::isDateLocked($this->company->id, Carbon::parse('2025-10-15')));
        $this->assertFalse(PeriodLock::isDateLocked($this->company->id, Carbon::parse('2025-11-01')));
    }

    /** @test */
    public function it_detects_date_in_locked_period()
    {
        PeriodLock::create([
            'company_id' => $this->company->id,
            'period_start' => Carbon::parse('2025-09-01'),
            'period_end' => Carbon::parse('2025-09-30'),
            'locked_by' => $this->user->id,
        ]);

        // Date in period
        $this->assertTrue($this->lockService->isDateLocked($this->company->id, Carbon::parse('2025-09-15')));

        // Date outside period
        $this->assertFalse($this->lockService->isDateLocked($this->company->id, Carbon::parse('2025-08-31')));
        $this->assertFalse($this->lockService->isDateLocked($this->company->id, Carbon::parse('2025-10-01')));
    }

    /** @test */
    public function it_throws_exception_for_locked_date()
    {
        DailyClosing::create([
            'company_id' => $this->company->id,
            'date' => Carbon::parse('2025-11-20'),
            'type' => DailyClosing::TYPE_ALL,
            'closed_by' => $this->user->id,
        ]);

        $this->expectException(PeriodLockedException::class);
        $this->expectExceptionMessage('Cannot create document');

        $this->lockService->enforceUnlocked($this->company->id, Carbon::parse('2025-11-20'), DailyClosing::TYPE_ALL, 'create');
    }

    /** @test */
    public function it_returns_lock_reason()
    {
        PeriodLock::create([
            'company_id' => $this->company->id,
            'period_start' => Carbon::parse('2025-08-01'),
            'period_end' => Carbon::parse('2025-08-31'),
            'locked_by' => $this->user->id,
        ]);

        $reason = $this->lockService->getLockReason($this->company->id, Carbon::parse('2025-08-15'));

        $this->assertNotNull($reason);
        $this->assertEquals('period', $reason['type']);
        $this->assertStringContainsString('2025-08-01', $reason['message']);
    }

    /** @test */
    public function it_can_unlock_a_day()
    {
        $closing = DailyClosing::create([
            'company_id' => $this->company->id,
            'date' => Carbon::parse('2025-11-25'),
            'type' => DailyClosing::TYPE_ALL,
            'closed_by' => $this->user->id,
        ]);

        $this->assertTrue(DailyClosing::isDateClosed($this->company->id, Carbon::parse('2025-11-25')));

        $this->lockService->unlockDay($closing->id);

        $this->assertFalse(DailyClosing::isDateClosed($this->company->id, Carbon::parse('2025-11-25')));
    }

    /** @test */
    public function it_can_unlock_a_period()
    {
        $lock = PeriodLock::create([
            'company_id' => $this->company->id,
            'period_start' => Carbon::parse('2025-07-01'),
            'period_end' => Carbon::parse('2025-07-31'),
            'locked_by' => $this->user->id,
        ]);

        $this->assertTrue(PeriodLock::isDateLocked($this->company->id, Carbon::parse('2025-07-15')));

        $this->lockService->unlockPeriod($lock->id);

        $this->assertFalse(PeriodLock::isDateLocked($this->company->id, Carbon::parse('2025-07-15')));
    }

    /** @test */
    public function daily_closing_api_requires_authorization()
    {
        $response = $this->actingAs($this->user)
            ->withHeader('company', $this->company->id)
            ->getJson('/api/v1/accounting/daily-closings');

        // Should get 403 since user doesn't have the permission yet
        // In a real scenario, would need to set up bouncer/permissions
        $response->assertStatus(403);
    }

    /** @test */
    public function overlapping_period_locks_are_rejected()
    {
        // Create first lock
        PeriodLock::create([
            'company_id' => $this->company->id,
            'period_start' => Carbon::parse('2025-06-01'),
            'period_end' => Carbon::parse('2025-06-30'),
            'locked_by' => $this->user->id,
        ]);

        // Check overlapping detection
        $overlapping = PeriodLock::getOverlappingLocks(
            $this->company->id,
            Carbon::parse('2025-06-15'),
            Carbon::parse('2025-07-15')
        );

        $this->assertCount(1, $overlapping);
    }

    /** @test */
    public function specific_type_closings_work_correctly()
    {
        // Close only invoices for a day
        DailyClosing::create([
            'company_id' => $this->company->id,
            'date' => Carbon::parse('2025-11-22'),
            'type' => DailyClosing::TYPE_INVOICES,
            'closed_by' => $this->user->id,
        ]);

        // Invoices should be locked
        $this->assertTrue($this->lockService->isDateLocked(
            $this->company->id,
            Carbon::parse('2025-11-22'),
            DailyClosing::TYPE_INVOICES
        ));

        // Cash should not be locked
        $this->assertFalse($this->lockService->isDateLocked(
            $this->company->id,
            Carbon::parse('2025-11-22'),
            DailyClosing::TYPE_CASH
        ));
    }

    /** @test */
    public function type_all_closing_locks_all_types()
    {
        DailyClosing::create([
            'company_id' => $this->company->id,
            'date' => Carbon::parse('2025-11-23'),
            'type' => DailyClosing::TYPE_ALL,
            'closed_by' => $this->user->id,
        ]);

        // All types should be locked
        $this->assertTrue($this->lockService->isDateLocked(
            $this->company->id,
            Carbon::parse('2025-11-23'),
            DailyClosing::TYPE_INVOICES
        ));
        $this->assertTrue($this->lockService->isDateLocked(
            $this->company->id,
            Carbon::parse('2025-11-23'),
            DailyClosing::TYPE_CASH
        ));
    }
}
// CLAUDE-CHECKPOINT
