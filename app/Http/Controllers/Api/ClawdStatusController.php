<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Clawd Status Controller
 *
 * Returns system health, metrics, and recent events for the Clawd AI assistant.
 * Token-authenticated via VerifyClawdToken middleware (X-Monitor-Token header).
 */
class ClawdStatusController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $health = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'queues' => $this->checkQueues(),
            'storage' => $this->checkStorage(),
        ];

        $degraded = in_array(false, $health, true);

        return response()->json([
            'app' => 'facturino',
            'status' => $degraded ? 'degraded' : 'healthy',
            'timestamp' => now()->toIso8601String(),
            'health' => $health,
            'metrics' => $this->getMetrics(),
            'recent_events' => $this->getRecentEvents(),
        ]);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkRedis(): bool
    {
        try {
            if (config('cache.default') !== 'redis') {
                return true;
            }
            Cache::store('redis')->put('clawd_health', 'ok', 10);

            return Cache::store('redis')->get('clawd_health') === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkQueues(): bool
    {
        try {
            $failedRecent = DB::table('failed_jobs')
                ->where('failed_at', '>=', Carbon::now()->subHour())
                ->count();

            return $failedRecent < 100;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkStorage(): bool
    {
        try {
            $testFile = 'clawd_health_'.time().'.txt';
            \Storage::put($testFile, 'ok');
            if (\Storage::exists($testFile)) {
                \Storage::delete($testFile);
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function getMetrics(): array
    {
        $now = Carbon::now();

        return [
            'new_users_24h' => DB::table('users')
                ->where('created_at', '>=', $now->copy()->subDay())
                ->count(),
            'active_companies' => DB::table('companies')->count(),
            'failed_jobs_24h' => DB::table('failed_jobs')
                ->where('failed_at', '>=', $now->copy()->subDay())
                ->count(),
            'pending_jobs' => DB::table('jobs')->count(),
            'new_tickets_24h' => DB::table('tickets')
                ->where('created_at', '>=', $now->copy()->subDay())
                ->count(),
        ];
    }

    private function getRecentEvents(): array
    {
        $events = [];
        $cutoff = Carbon::now()->subDay();

        // New user registrations (last 24h)
        $newUsers = DB::table('users')
            ->where('created_at', '>=', $cutoff)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get(['email', 'name', 'created_at']);

        foreach ($newUsers as $user) {
            $events[] = [
                'type' => 'new_user',
                'email' => $user->email,
                'name' => $user->name,
                'at' => Carbon::parse($user->created_at)->toIso8601String(),
            ];
        }

        // Failed jobs (last 24h)
        $failedJobs = DB::table('failed_jobs')
            ->where('failed_at', '>=', $cutoff)
            ->orderByDesc('failed_at')
            ->limit(10)
            ->get(['uuid', 'queue', 'failed_at', 'exception']);

        foreach ($failedJobs as $job) {
            $events[] = [
                'type' => 'queue_failed',
                'uuid' => $job->uuid,
                'queue' => $job->queue,
                'exception' => \Str::limit($job->exception, 200),
                'at' => Carbon::parse($job->failed_at)->toIso8601String(),
            ];
        }

        // Support tickets (last 24h)
        $tickets = DB::table('tickets')
            ->join('users', 'tickets.user_id', '=', 'users.id')
            ->where('tickets.created_at', '>=', $cutoff)
            ->orderByDesc('tickets.created_at')
            ->limit(10)
            ->get(['users.email', 'tickets.title', 'tickets.created_at']);

        foreach ($tickets as $ticket) {
            $events[] = [
                'type' => 'support_ticket',
                'email' => $ticket->email,
                'subject' => $ticket->title,
                'at' => Carbon::parse($ticket->created_at)->toIso8601String(),
            ];
        }

        // Sort all events by time descending
        usort($events, fn ($a, $b) => strcmp($b['at'], $a['at']));

        return array_slice($events, 0, 20);
    }
}
