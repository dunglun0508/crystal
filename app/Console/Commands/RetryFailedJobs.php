<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Jobs\CrawlLevel3ProductsJob;
use App\Jobs\CrawlLevel2ProductsJob;
use App\Jobs\CrawlCategoriesJob;
use App\Jobs\CrawlProductDetailsAndVariantsJob;
use App\Jobs\DispatchCrawlJobs;

class RetryFailedJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:retry-failed {--force : Force retry even if max attempts exceeded}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry all failed jobs or recreate them';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $failedJobs = DB::table('failed_jobs')->get();
        
        if ($failedJobs->count() === 0) {
            $this->info('No failed jobs found.');
            return 0;
        }
        
        $this->info("Found {$failedJobs->count()} failed jobs");
        
        $retriedCount = 0;
        $recreatedCount = 0;
        
        foreach ($failedJobs as $job) {
            try {
                $this->call('queue:retry', ['id' => $job->id]);
                $retriedCount++;
                $this->line("Retried job ID: {$job->id}");
            } catch (\Exception $e) {
                $this->warn("Could not retry job ID {$job->id}, attempting to recreate...");
                
                try {
                    $payload = json_decode($job->payload, true);
                    $jobData = $payload['data']['command'];
                    $jobClass = $payload['displayName'] ?? '';
                    
                    // Recreate based on job class
                    if ($this->recreateJob($jobClass, $jobData)) {
                        $recreatedCount++;
                        DB::table('failed_jobs')->where('id', $job->id)->delete();
                        $this->line("Recreated job: {$jobClass}");
                    } else {
                        $this->error("Could not recreate job ID {$job->id} - Unknown job class: {$jobClass}");
                    }
                    
                } catch (\Exception $e2) {
                    $this->error("Failed to recreate job ID {$job->id}: " . $e2->getMessage());
                }
            }
        }
        
        $this->info("Successfully retried {$retriedCount} jobs and recreated {$recreatedCount} jobs");
        
        return 0;
    }

    private function recreateJob($jobClass, $jobData)
    {
        switch ($jobClass) {
            case 'App\Jobs\CrawlCategoriesJob':
                CrawlCategoriesJob::dispatch();
                return true;

            case 'App\Jobs\CrawlLevel3ProductsJob':
                if (preg_match('/categoryCode":"([^"]+)"/', $jobData, $matches1) &&
                    preg_match('/categorySlug":"([^"]+)"/', $jobData, $matches2)) {
                    CrawlLevel3ProductsJob::dispatch($matches1[1], $matches2[1]);
                    return true;
                }
                break;

            case 'App\Jobs\CrawlLevel2ProductsJob':
                if (preg_match('/categoryCode":"([^"]+)"/', $jobData, $matches1) &&
                    preg_match('/categorySlug":"([^"]+)"/', $jobData, $matches2)) {
                    CrawlLevel2ProductsJob::dispatch($matches1[1], $matches2[1]);
                    return true;
                }
                break;

            case 'App\Jobs\CrawlProductDetailsAndVariantsJob':
                if (preg_match('/productSlug":"([^"]+)"/', $jobData, $matches1)) {
                    $productCode = null;
                    if (preg_match('/productCode":"([^"]+)"/', $jobData, $matches2)) {
                        $productCode = $matches2[1];
                    }
                    CrawlProductDetailsAndVariantsJob::dispatch($matches1[1], $productCode);
                    return true;
                }
                break;

            case 'App\Jobs\DispatchCrawlJobs':
                DispatchCrawlJobs::dispatch();
                return true;
        }
        
        return false;
    }
} 