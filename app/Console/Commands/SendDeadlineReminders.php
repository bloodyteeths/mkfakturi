<?php

namespace App\Console\Commands;

use App\Models\Deadline;
use App\Models\Partner;
use App\Models\User;
use App\Notifications\DeadlineReminderNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Send Deadline Reminders Command (P8-02)
 *
 * Sends reminder notifications for upcoming deadlines and updates
 * overdue statuses. Runs daily at 09:00 via scheduler.
 *
 * Logic:
 * 1. Update overdue status for past-due non-completed deadlines
 * 2. Update due_today status for deadlines due today
 * 3. Send reminders for deadlines within the reminder window
 */
class SendDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deadlines:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminder notifications for upcoming deadlines';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing deadline reminders...');

        if (! Schema::hasTable('deadlines')) {
            $this->warn('Deadlines table does not exist yet. Skipping.');

            return 0;
        }

        // Step 1: Update overdue statuses
        $overdueCount = $this->updateOverdueStatuses();
        $this->info("Updated {$overdueCount} deadline(s) to overdue status.");

        // Step 2: Update due_today statuses
        $dueTodayCount = $this->updateDueTodayStatuses();
        $this->info("Updated {$dueTodayCount} deadline(s) to due_today status.");

        // Step 3: Send reminders
        $remindersSent = $this->sendReminders();
        $this->info("Sent {$remindersSent} reminder notification(s).");

        Log::info('SendDeadlineReminders completed', [
            'overdue_updated' => $overdueCount,
            'due_today_updated' => $dueTodayCount,
            'reminders_sent' => $remindersSent,
        ]);

        return 0;
    }

    /**
     * Update overdue status for past-due non-completed deadlines.
     */
    protected function updateOverdueStatuses(): int
    {
        return Deadline::where('status', '!=', Deadline::STATUS_COMPLETED)
            ->where('status', '!=', Deadline::STATUS_OVERDUE)
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => Deadline::STATUS_OVERDUE]);
    }

    /**
     * Update due_today status for deadlines due today.
     */
    protected function updateDueTodayStatuses(): int
    {
        return Deadline::where('status', '!=', Deadline::STATUS_COMPLETED)
            ->where('due_date', Carbon::today())
            ->where('status', '!=', Deadline::STATUS_DUE_TODAY)
            ->update(['status' => Deadline::STATUS_DUE_TODAY]);
    }

    /**
     * Send reminders for deadlines in the reminder window.
     */
    protected function sendReminders(): int
    {
        $sent = 0;

        // Get all non-completed deadlines due within the max reminder window (7 days)
        $deadlines = Deadline::where('status', '!=', Deadline::STATUS_COMPLETED)
            ->where('due_date', '>=', Carbon::today())
            ->where('due_date', '<=', Carbon::today()->addDays(7))
            ->with(['company', 'partner'])
            ->get();

        foreach ($deadlines as $deadline) {
            try {
                if (! $deadline->needsReminder()) {
                    continue;
                }

                $notified = $this->notifyStakeholders($deadline);

                if ($notified > 0) {
                    $deadline->update(['last_reminder_sent_at' => Carbon::now()]);
                    $sent++;
                }
            } catch (\Exception $e) {
                $this->error("Failed to send reminder for deadline {$deadline->id}: {$e->getMessage()}");
                Log::error('Deadline reminder failed', [
                    'deadline_id' => $deadline->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    /**
     * Notify company users and managing partner about a deadline.
     *
     * @return int Number of notifications sent
     */
    protected function notifyStakeholders(Deadline $deadline): int
    {
        $notified = 0;

        // Notify company owner
        if ($deadline->company && $deadline->company->owner_id) {
            $owner = User::find($deadline->company->owner_id);

            if ($owner) {
                $owner->notify(new DeadlineReminderNotification($deadline));
                $notified++;
            }
        }

        // Notify managing partner
        if ($deadline->partner_id) {
            $partner = Partner::with('user')->find($deadline->partner_id);

            if ($partner && $partner->user) {
                $partner->user->notify(new DeadlineReminderNotification($deadline));
                $notified++;
            }
        }

        return $notified;
    }
}
