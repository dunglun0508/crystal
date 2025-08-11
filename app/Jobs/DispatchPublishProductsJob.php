<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\ShopifyService;

class DispatchPublishProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct()
    {
        $this->onQueue('shopify-sync');
    }

    public function handle(ShopifyService $shopifyService): void
    {
        // 1) Lấy publicationId của Online Store
        $publicationId = $this->getOnlineStorePublicationId($shopifyService);

        $cursor = null;
        $count = 0; $skipped = 0; $queued = 0;

        do {
            $page = $this->getProductsPage($shopifyService, $cursor, $publicationId);
            $edges = $page['edges'] ?? [];

            foreach ($edges as $edge) {
                $p = $edge['node'];
                $count++;

                if (!empty($p['publishedOnPublication'])) {
                    $skipped++;
                    \Log::info("[SKIP] {$p['handle']} đã publish");
                    continue;
                }

                PublishProductJob::dispatch($p['id'], $p['handle'], $publicationId)->onQueue('shopify-sync');
                $queued++;
            }

            $pageInfo = $page['pageInfo'] ?? null;
            $cursor = $pageInfo['endCursor'] ?? null;
        } while (!empty($pageInfo['hasNextPage']));

        \Log::info("DispatchPublishProductsJob done. Total: {$count} | Queued: {$queued} | Skipped: {$skipped}");
    }

    private function getOnlineStorePublicationId(ShopifyService $shopifyService): string
    {
        $query = '
        query { publications(first: 20) { edges { node { id name } } } }';
        $res = $shopifyService->makeGraphQLRequest($query, []);
        if (!($res['success'] ?? false)) {
            throw new \Exception('Failed to fetch publications: '.json_encode($res));
        }
        foreach (($res['data']['publications']['edges'] ?? []) as $e) {
            if (($e['node']['name'] ?? '') === 'Online Store') return $e['node']['id'];
        }
        throw new \Exception('Không tìm thấy publication Online Store');
    }

    private function getProductsPage(ShopifyService $shopifyService, ?string $cursor, string $publicationId): array
    {
        $query = '
        query ($cursor: String, $publicationId: ID!) {
          products(first: 100, after: $cursor) {
            pageInfo { hasNextPage endCursor }
            edges {
              node {
                id
                handle
                publishedOnPublication(publicationId: $publicationId)
              }
            }
          }
        }';

        $vars = ['publicationId' => $publicationId];
        if ($cursor) $vars['cursor'] = $cursor;

        $res = $shopifyService->makeGraphQLRequest($query, $vars);
        if (!($res['success'] ?? false)) {
            throw new \Exception('Failed to fetch products: '.json_encode($res));
        }
        return $res['data']['products'] ?? [];
    }
} 