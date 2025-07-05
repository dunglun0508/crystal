<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FailedCrawl;
use Illuminate\Support\Facades\DB;

class FailedCrawlsStatsCommand extends Command
{
    protected $signature = 'crawl:failed-stats {--type= : Filter by type}';
    protected $description = 'Show statistics of failed crawls';

    public function handle()
    {
        $type = $this->option('type');

        $this->info('=== FAILED CRAWLS STATISTICS ===');

        // Tổng số bản ghi failed
        $totalFailed = FailedCrawl::count();
        $this->info("Total failed crawls: {$totalFailed}");

        // Số bản ghi chưa resolve
        $unresolved = FailedCrawl::unresolved()->count();
        $this->info("Unresolved failed crawls: {$unresolved}");

        // Số bản ghi có thể retry
        $retryable = FailedCrawl::unresolved()->retryable()->count();
        $this->info("Retryable failed crawls: {$retryable}");

        $this->newLine();

        // Thống kê theo type
        $this->info('=== BY TYPE ===');
        $statsByType = FailedCrawl::select('type', 
            DB::raw('COUNT(*) as total'),
            DB::raw('COUNT(CASE WHEN resolved_at IS NULL THEN 1 END) as unresolved'),
            DB::raw('COUNT(CASE WHEN attempts >= 5 THEN 1 END) as max_attempts_reached')
        )
        ->groupBy('type')
        ->get();

        $this->table(
            ['Type', 'Total', 'Unresolved', 'Max Attempts'],
            $statsByType->map(function ($stat) {
                return [
                    $stat->type,
                    $stat->total,
                    $stat->unresolved,
                    $stat->max_attempts_reached
                ];
            })
        );

        // Thống kê theo attempts
        $this->newLine();
        $this->info('=== BY ATTEMPTS ===');
        $statsByAttempts = FailedCrawl::select('attempts', 
            DB::raw('COUNT(*) as count')
        )
        ->groupBy('attempts')
        ->orderBy('attempts')
        ->get();

        $this->table(
            ['Attempts', 'Count'],
            $statsByAttempts->map(function ($stat) {
                return [$stat->attempts, $stat->count];
            })
        );

        // Top errors
        $this->newLine();
        $this->info('=== TOP ERRORS ===');
        $topErrors = FailedCrawl::select('error', 
            DB::raw('COUNT(*) as count')
        )
        ->whereNotNull('error')
        ->groupBy('error')
        ->orderByDesc('count')
        ->limit(10)
        ->get();

        $this->table(
            ['Error', 'Count'],
            $topErrors->map(function ($stat) {
                return [
                    substr($stat->error, 0, 50) . (strlen($stat->error) > 50 ? '...' : ''),
                    $stat->count
                ];
            })
        );

        // Recent failures
        $this->newLine();
        $this->info('=== RECENT FAILURES (Last 10) ===');
        $recentFailures = FailedCrawl::unresolved()
            ->orderByDesc('last_attempt_at')
            ->limit(10)
            ->get();

        $this->table(
            ['Type', 'Identifier', 'Attempts', 'Last Attempt', 'Error'],
            $recentFailures->map(function ($failure) {
                return [
                    $failure->type,
                    substr($failure->identifier, 0, 30) . (strlen($failure->identifier) > 30 ? '...' : ''),
                    $failure->attempts,
                    $failure->last_attempt_at ? $failure->last_attempt_at->format('Y-m-d H:i:s') : 'N/A',
                    substr($failure->error ?? 'N/A', 0, 30) . (strlen($failure->error ?? '') > 30 ? '...' : '')
                ];
            })
        );

        return 0;
    }
} 