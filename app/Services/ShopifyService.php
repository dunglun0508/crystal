<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    private $shopifyDomain;
    private $shopifyToken;
    private $shopifyApiVersion;
    protected $graphqlEndpoint;

    public function __construct()
    {
        $this->shopifyDomain = env('SHOPIFY_DOMAIN');
        $this->shopifyToken = env('SHOPIFY_ACCESS_TOKEN');
        $this->shopifyApiVersion = env('SHOPIFY_API_VERSION');
        $this->graphqlEndpoint = "https://{$this->shopifyDomain}/admin/api/{$this->shopifyApiVersion}/graphql.json";
    }

    /**
     * Kiểm tra kết nối đến Shopify
     */
    public function checkConnection()
    {
        $query = '
        query {
            shop {
                name
                email
                myshopifyDomain
            }
        }';

        return $this->makeGraphQLRequest($query);
    }

    /**
     * Tìm collection theo handle
     */
    public function findCollectionByHandle($handle)
    {
        $query = '
        query getCollection($handle: String!) {
            collectionByHandle(handle: $handle) {
                id
                title
                handle
            }
        }';

        return $this->makeGraphQLRequest($query, ['handle' => $handle]);
    }

    /**
     * Tìm product theo handle
     */
    public function findProductByHandle($handle)
    {
        $query = '
        query getProduct($handle: String!) {
            productByHandle(handle: $handle) {
                id
                title
                handle
            }
        }';

        return $this->makeGraphQLRequest($query, ['handle' => $handle]);
    }

    /**
     * Tạo collection mới
     */
    public function createCollection($collectionData)
    {
        $mutation = '
        mutation collectionCreate($input: CollectionInput!) {
            collectionCreate(input: $input) {
                collection {
                    id
                    title
                    handle
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        return $this->makeGraphQLRequest($mutation, ['input' => $collectionData]);
    }

    /**
     * Cập nhật collection
     */
    public function updateCollection($collectionId, $collectionData)
    {
        $mutation = '
        mutation collectionUpdate($input: CollectionInput!) {
            collectionUpdate(input: $input) {
                collection {
                    id
                    title
                    handle
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        $collectionData['id'] = $collectionId;
        return $this->makeGraphQLRequest($mutation, ['input' => $collectionData]);
    }

    /**
     * Tạo product mới
     */
    public function createProduct($productData)
    {
        $mutation = '
        mutation productCreate($input: ProductInput!) {
            productCreate(input: $input) {
                product {
                    id
                    title
                    handle
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        return $this->makeGraphQLRequest($mutation, ['input' => $productData]);
    }

    /**
     * Cập nhật product
     */
    public function updateProduct($productId, $productData)
    {
        $mutation = '
        mutation productUpdate($input: ProductInput!) {
            productUpdate(input: $input) {
                product {
                    id
                    title
                    handle
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        $productData['id'] = $productId;
        return $this->makeGraphQLRequest($mutation, ['input' => $productData]);
    }

    /**
     * Thực hiện GraphQL request
     */
    protected function makeGraphQLRequest($query, $variables = [])
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->shopifyToken,
                'Content-Type' => 'application/json'
            ])->post($this->graphqlEndpoint, array_filter([
                'query' => $query,
                'variables' => empty($variables) ? null : $variables
            ]));

            if ($response->successful()) {
                $data = $response->json();
                
                // Kiểm tra lỗi từ Shopify
                if (isset($data['errors'])) {
                    return [
                        'success' => false,
                        'error' => $data['errors'][0]['message']
                    ];
                }

                // Kiểm tra userErrors
                if (isset($data['data'])) {
                    foreach ($data['data'] as $key => $value) {
                        if (isset($value['userErrors']) && !empty($value['userErrors'])) {
                            return [
                                'success' => false,
                                'error' => $value['userErrors'][0]['message']
                            ];
                        }
                    }
                }

                return [
                    'success' => true,
                    'data' => $data['data']
                ];
            }

            return [
                'success' => false,
                'error' => 'Lỗi kết nối Shopify API: ' . $response->status()
            ];

        } catch (\Exception $e) {
            Log::error('Shopify GraphQL request error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Lỗi kết nối: ' . $e->getMessage()
            ];
        }
    }
}