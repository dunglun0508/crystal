<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckQueueCommand extends Command
{
    protected $signature = 'queue:check {queue? : Specific queue name}';
    protected $description = 'Check jobs in queue';

    public function handle()
    {
        $queue = $this->argument('queue');

        if ($queue) {
            $this->checkSpecificQueue($queue);
        } else {
            $this->checkAllQueues();
        }
    }

    private function checkAllQueues()
    {
        $this->info('=== Queue Status ===');
        
        $queues = ['all', 'categories', 'products', 'details'];
        
        foreach ($queues as $queueName) {
            $count = DB::table('jobs')->where('queue', $queueName)->count();
            $status = $count > 0 ? '🟢' : '🔴';
            $this->line("{$status} {$queueName}: {$count} jobs");
        }
        
        $totalJobs = DB::table('jobs')->count();
        $failedJobs = DB::table('failed_jobs')->count();
        
        $this->newLine();
        $this->info("Total jobs: {$totalJobs}");
        $this->info("Failed jobs: {$failedJobs}");
    }

    private function checkSpecificQueue($queueName)
    {
        $jobs = DB::table('jobs')->where('queue', $queueName)->get();
        
        if ($jobs->isEmpty()) {
            $this->info("No jobs in queue: {$queueName}");
            return;
        }
        
        $this->info("=== Jobs in queue: {$queueName} ===");
        
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $created = \Carbon\Carbon::parse($job->created_at)->diffForHumans();
            
            $this->line("• {$jobClass} (created {$created})");
        }
        
        $this->info("Total: {$jobs->count()} jobs");
    }
} 