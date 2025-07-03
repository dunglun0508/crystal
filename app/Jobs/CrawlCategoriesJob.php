<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Http\Controllers\CrawlController;
use App\Models\Category;
use Illuminate\Support\Facades\Log;

class CrawlCategoriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    public $tries = 3;

    public function __construct()
    {
        $this->onQueue('categories');
    }

    public function handle()
    {
        try {
            $crawlController = new CrawlController();
            $request = new \Illuminate\Http\Request();
            $response = $crawlController->categoriesCrawler($request);
            
            $result = json_decode($response->getContent(), true);
            return $result;
            
        } catch (\Exception $e) {
            Log::error("CrawlCategoriesJob: Failed - " . $e->getMessage());
        }
    }

    public function failed(\Throwable $exception)
    {
        Log::error("CrawlCategoriesJob: Job failed: " . $exception->getMessage());
    }
} 