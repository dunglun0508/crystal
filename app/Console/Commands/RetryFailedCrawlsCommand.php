<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FailedCrawl;
use App\Jobs\CrawlLevel2ProductsJob;
use App\Jobs\CrawlLevel3ProductsJob;
use App\Jobs\CrawlProductDetailsAndVariantsJob;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class RetryFailedCrawlsCommand extends Command
{
    protected $signature = 'crawl:retry-failed {type? : Type of crawl to retry (all, category_level2, category_level3, product_detail)} {--limit=50 : Maximum number of failed crawls to retry}';
    protected $description = 'Retry failed crawl jobs';

    public function handle()
    {
        $type = $this->argument('type') ?? 'all';
        $limit = (int) $this->option('limit');

        $this->info("Starting retry of failed crawls for type: {$type}");

        try {
            $query = FailedCrawl::unresolved()->retryable();

            if ($type !== 'all') {
                $query->ofType($type);
            }

            $failedCrawls = $query->limit($limit)->get();

            if ($failedCrawls->isEmpty()) {
                $this->info('No failed crawls to retry.');
                return 0;
            }

            $this->info("Found {$failedCrawls->count()} failed crawls to retry.");

            $retriedCount = 0;
            $successCount = 0;

            foreach ($failedCrawls as $failedCrawl) {
                try {
                    $this->info("Retrying {$failedCrawl->type}: {$failedCrawl->identifier}");
                    
                    switch ($failedCrawl->type) {
                        case 'category_level2':
                            $this->retryLevel2Category($failedCrawl);
                            break;
                        case 'category_level3':
                            $this->retryLevel3Category($failedCrawl);
                            break;
                        case 'product_detail':
                            $this->retryProductDetail($failedCrawl);
                            break;
                        default:
                            $this->warn("Unknown type: {$failedCrawl->type}");
                            continue 2;
                    }

                    $failedCrawl->markAsResolved();
                    $successCount++;
                    $this->info("✓ Successfully retried {$failedCrawl->type}: {$failedCrawl->identifier}");
                    
                } catch (\Exception $e) {
                    $failedCrawl->incrementAttempts();
                    $failedCrawl->update(['error' => $e->getMessage()]);
                    $this->error("✗ Failed to retry {$failedCrawl->type}: {$failedCrawl->identifier} - {$e->getMessage()}");
                }

                $retriedCount++;
                
                // Add delay between retries
                if ($retriedCount < $failedCrawls->count()) {
                    sleep(rand(2, 5));
                }
            }

            $this->info("Retry completed: {$retriedCount} attempted, {$successCount} successful");

        } catch (\Exception $e) {
            $this->error("Error during retry: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function retryLevel2Category(FailedCrawl $failedCrawl)
    {
        $category = Category::where('code', $failedCrawl->identifier)->first();
        if (!$category) {
            throw new \Exception("Category not found: {$failedCrawl->identifier}");
        }

        $job = new CrawlLevel2ProductsJob($category->code, $category->slug);
        $job->handle();
    }

    private function retryLevel3Category(FailedCrawl $failedCrawl)
    {
        $category = Category::where('code', $failedCrawl->identifier)->first();
        if (!$category) {
            throw new \Exception("Category not found: {$failedCrawl->identifier}");
        }

        $job = new CrawlLevel3ProductsJob($category->code, $category->slug);
        $job->handle();
    }

    private function retryProductDetail(FailedCrawl $failedCrawl)
    {
        $job = new CrawlProductDetailsAndVariantsJob($failedCrawl->identifier);
        $job->handle();
    }
} 