<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ShopifyService;

class PublishProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 10, 20];

    private string $productId;
    private string $handle;
    private string $publicationId;

    public function __construct(string $productId, string $handle, string $publicationId)
    {
        $this->onQueue('shopify-sync');
        $this->productId = $productId;
        $this->handle = $handle;
        $this->publicationId = $publicationId;
    }

    public function handle(ShopifyService $shopifyService): void
    {
        $mutation = '
        mutation ($id: ID!, $publicationId: ID!) {
            publishablePublish(id: $id, input: { publicationId: $publicationId }) {
                userErrors { field message }
            }
        }';

        $result = $shopifyService->makeGraphQLRequest($mutation, [
            'id' => $this->productId,
            'publicationId' => $this->publicationId,
        ]);

        if (!($result['success'] ?? false)) {
            \Log::error('Publish product failed: ' . json_encode($result) . " | handle={$this->handle}");
            return;
        }

        $errs = $result['data']['publishablePublish']['userErrors'] ?? [];
        if (!empty($errs)) {
            \Log::error('Publish userErrors: ' . json_encode($errs) . " | handle={$this->handle}");
            return;
        }

        \Log::info("[OK] Published product: {$this->handle}");
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("PublishProductJob failed for {$this->handle}: " . $exception->getMessage());
    }
} 