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

        // Loại bỏ images, variants, tags, collections và collectionId khỏi productData
        $cleanProductData = array_diff_key($productData, array_flip(['images', 'variants', 'tags', 'collections', 'collectionId']));
        
        $result = $this->makeGraphQLRequest($mutation, ['input' => $cleanProductData]);
        
        // Nếu tạo product thành công và có images/variants, thêm chúng sau
        if ($result['success'] && isset($result['data']['productCreate']['product']['id'])) {
            $productId = $result['data']['productCreate']['product']['id'];
            \Log::info("Product created successfully with ID: {$productId}");
            
            // Thêm images nếu có
            if (isset($productData['images']) && !empty($productData['images'])) {
                \Log::info("Adding " . count($productData['images']) . " images to product");
                $this->addProductImages($productId, $productData['images']);
            } else {
                \Log::info("No images to add");
            }
            
            // Thêm variants nếu có
            if (isset($productData['variants']) && !empty($productData['variants'])) {
                \Log::info("Adding " . count($productData['variants']) . " variants to product");
                $this->addProductVariants($productId, $productData['variants']);
            } else {
                \Log::info("No variants to add");
            }

            // Thêm tags nếu có
            if (isset($productData['tags']) && !empty($productData['tags'])) {
                \Log::info("Adding tags to product: " . $productData['tags']);
                $this->addProductTags($productId, $productData['tags']);
            } else {
                \Log::info("No tags to add");
            }

            // Thêm product vào collection nếu có
            if (isset($productData['collectionId']) && !empty($productData['collectionId'])) {
                \Log::info("Adding product to collection: " . $productData['collectionId']);
                $this->addProductToCollection($productId, $productData['collectionId']);
            } else {
                \Log::info("No collection to add");
            }
        } else {
            \Log::error("Failed to create product: " . json_encode($result));
        }
        
        return $result;
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

        // Loại bỏ images, variants, tags, collections và collectionId khỏi productData
        $cleanProductData = array_diff_key($productData, array_flip(['images', 'variants', 'tags', 'collections', 'collectionId']));
        $cleanProductData['id'] = $productId;
        
        $result = $this->makeGraphQLRequest($mutation, ['input' => $cleanProductData]);
        
        // Nếu cập nhật product thành công và có images/variants, thêm chúng sau
        if ($result['success']) {
            // Thêm images nếu có
            if (isset($productData['images']) && !empty($productData['images'])) {
                $this->addProductImages($productId, $productData['images']);
            }
            
            // Thêm variants nếu có
            if (isset($productData['variants']) && !empty($productData['variants'])) {
                $this->addProductVariants($productId, $productData['variants']);
            }

            // Thêm tags nếu có
            if (isset($productData['tags']) && !empty($productData['tags'])) {
                $this->addProductTags($productId, $productData['tags']);
            }

            // Thêm product vào collection nếu có
            if (isset($productData['collectionId']) && !empty($productData['collectionId'])) {
                $this->addProductToCollection($productId, $productData['collectionId']);
            }
        }
        
        return $result;
    }

    /**
     * Thêm images cho product
     */
    private function addProductImages($productId, $images)
    {
        foreach ($images as $image) {
            $url = $image['src'] ?? null;
            if (!$url) {
                \Log::warning('Bỏ qua image do thiếu src');
                continue;
            }

            // Sử dụng Staged Uploads flow của Shopify
            $originalSource = $this->uploadImageViaStagedUploads($url, $image['altText'] ?? '');
            if (!$originalSource) {
                \Log::error("Không thể upload ảnh qua Staged Uploads: {$url}");
                continue;
            }

            $mutation = '
            mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
                productCreateMedia(productId: $productId, media: $media) {
                    media {
                        id
                        mediaContentType
                        status
                        alt
                    }
                    mediaUserErrors {
                        field
                        message
                    }
                }
            }';

            $mediaInput = [
                'mediaContentType' => 'IMAGE',
                'originalSource' => $originalSource,
            ];
            if (!empty($image['altText'])) {
                $mediaInput['alt'] = $image['altText'];
            }

            \Log::info("Gửi ảnh đã upload lên Shopify: " . substr($url, -20) . "...");

            $result = $this->makeGraphQLRequest($mutation, [
                'media' => [$mediaInput],
                'productId' => $productId
            ]);

            if (!($result['success'] ?? false)) {
                \Log::error('Lỗi thêm image cho product: ' . json_encode($result));
                continue;
            }

            $payload = $result['data']['productCreateMedia'] ?? [];
            if (!empty($payload['mediaUserErrors'])) {
                \Log::error('Shopify mediaUserErrors: ' . json_encode($payload['mediaUserErrors']));
                continue;
            }

            \Log::info('Đã gửi ảnh thành công: ' . json_encode($payload['media'] ?? []));
        }
    }

    /**
     * Upload ảnh qua Shopify Staged Uploads
     */
    private function uploadImageViaStagedUploads(string $imageUrl, string $altText = ''): ?string
    {
        try {
            // Bước 1: Đọc ảnh từ filesystem hoặc URL
            $imageData = $this->getImageData($imageUrl);
            if (!$imageData) {
                return null;
            }

            $filename = basename($imageUrl);
            $fileSize = strlen($imageData['data']);
            $mimeType = $imageData['mimeType'];

            // Bước 2: Xin S3 fields từ Shopify
            $stagedUploadMutation = '
            mutation stagedUploadsCreate($input: [StagedUploadInput!]!) {
                stagedUploadsCreate(input: $input) {
                    stagedTargets {
                        url
                        resourceUrl
                        parameters { name value }
                    }
                    userErrors { field message }
                }
            }';

            $input = [[
                'resource' => 'IMAGE',
                'filename' => $filename,
                'mimeType' => $mimeType,
                'fileSize' => (string) $fileSize, // Convert to string for UnsignedInt64
                'httpMethod' => 'POST'
            ]];

            $result = $this->makeGraphQLRequest($stagedUploadMutation, ['input' => $input]);
            
            if (!($result['success'] ?? false)) {
                \Log::error('Lỗi xin staged upload fields: ' . json_encode($result));
                return null;
            }

            $stagedTargets = $result['data']['stagedUploadsCreate']['stagedTargets'] ?? [];
            if (empty($stagedTargets)) {
                \Log::error('Không nhận được staged targets');
                return null;
            }

            $target = $stagedTargets[0];
            $uploadUrl = $target['url'];
            $originalSource = $target['resourceUrl'];
            $parameters = $target['parameters'];

            // Bước 3: Upload ảnh lên S3
            $formData = [];
            foreach ($parameters as $param) {
                $formData[$param['name']] = $param['value'];
            }
            $formData['file'] = $imageData['data'];

            $uploadResult = $this->uploadToS3($uploadUrl, $formData, $filename, $mimeType);
            if (!$uploadResult) {
                \Log::error('Lỗi upload lên S3');
                return null;
            }

            \Log::info("Upload ảnh lên S3 thành công: {$filename}");
            return $originalSource;

        } catch (\Throwable $e) {
            \Log::error('Lỗi staged upload: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Đọc dữ liệu ảnh từ filesystem hoặc URL
     */
    private function getImageData(string $url): ?array
    {
        try {
            // Nếu là URL local, đọc trực tiếp từ filesystem
            if (strpos($url, 'http://192.168.1.167') === 0 || strpos($url, 'http://crystallocal.com') === 0) {
                return $this->getImageDataFromFilesystem($url);
            }

            // Nếu là URL external, dùng HTTP request
            $response = Http::withHeaders(['User-Agent' => 'ShopifySyncBot/1.0'])
                ->timeout(30)
                ->withoutVerifying()
                ->get($url);

            if (!$response->ok()) {
                \Log::error("HTTP error khi đọc ảnh: {$response->status()} - {$url}");
                return null;
            }

            $contentType = $response->header('Content-Type');
            if (strpos($contentType, 'image/') !== 0) {
                \Log::error("Content-Type không phải ảnh: {$contentType} - {$url}");
                return null;
            }

            return [
                'data' => $response->body(),
                'mimeType' => $contentType
            ];

        } catch (\Throwable $e) {
            \Log::error('Lỗi đọc ảnh: ' . $e->getMessage() . " - {$url}");
            return null;
        }
    }

    /**
     * Đọc ảnh trực tiếp từ filesystem
     */
    private function getImageDataFromFilesystem(string $url): ?array
    {
        try {
            // Chuyển URL thành path local
            $path = str_replace(['http://192.168.1.167/', 'http://crystallocal.com/'], '', $url);
            $fullPath = public_path($path);
            
            if (!file_exists($fullPath)) {
                \Log::error("File ảnh không tồn tại: {$fullPath}");
                return null;
            }

            $imageData = file_get_contents($fullPath);
            if (!$imageData) {
                \Log::error("Không thể đọc file ảnh: {$fullPath}");
                return null;
            }

            // Xác định MIME type từ extension
            $extension = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp'
            ];
            
            $mimeType = $mimeMap[$extension] ?? 'image/jpeg';
            
            \Log::info("Đọc ảnh từ filesystem thành công: {$fullPath} ({$mimeType})");
            
            return [
                'data' => $imageData,
                'mimeType' => $mimeType
            ];

        } catch (\Throwable $e) {
            \Log::error('Lỗi đọc ảnh từ filesystem: ' . $e->getMessage() . " - {$url}");
            return null;
        }
    }

    /**
     * Upload file lên S3 qua Shopify staged uploads
     */
    private function uploadToS3(string $uploadUrl, array $formData, string $filename, string $mimeType): bool
    {
        try {
            // Tạo multipart form data
            $boundary = '----WebKitFormBoundary' . uniqid();
            $postData = '';

            // Thêm form fields
            foreach ($formData as $name => $value) {
                if ($name === 'file') continue;
                $postData .= "--{$boundary}\r\n";
                $postData .= "Content-Disposition: form-data; name=\"{$name}\"\r\n\r\n";
                $postData .= "{$value}\r\n";
            }

            // Thêm file
            $postData .= "--{$boundary}\r\n";
            $postData .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$filename}\"\r\n";
            $postData .= "Content-Type: {$mimeType}\r\n\r\n";
            $postData .= $formData['file'];
            $postData .= "\r\n--{$boundary}--\r\n";

            // Upload lên S3
            $response = Http::withHeaders([
                'Content-Type' => "multipart/form-data; boundary={$boundary}",
                'User-Agent' => 'ShopifySyncBot/1.0'
            ])->withBody($postData, "multipart/form-data; boundary={$boundary}")
              ->post($uploadUrl);

            // HTTP 201 = Created (thành công), HTTP 200 = OK
            if ($response->status() !== 201 && $response->status() !== 200) {
                \Log::error("S3 upload failed: {$response->status()} - {$response->body()}");
                return false;
            }

            \Log::info("S3 upload thành công: {$response->status()} - {$response->body()}");

            return true;

        } catch (\Throwable $e) {
            \Log::error('Lỗi upload S3: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy extension từ Content-Type
     */
    private function getExtensionFromContentType(string $contentType): string
    {
        $map = [
            'image/jpeg' => 'jpeg',
            'image/jpg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp'
        ];
        
        return $map[$contentType] ?? 'jpeg';
    }

    /**
     * Thêm variants cho product
     */
    private function addProductVariants($productId, $variants)
    {
        foreach ($variants as $variant) {
            $mutation = '
            mutation productVariantCreate($input: ProductVariantInput!) {
                productVariantCreate(input: $input) {
                    productVariant {
                        id
                        title
                        sku
                        price
                        inventoryItem {
                            id
                        }
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }';

            $variantInput = [
                'productId' => $productId,
                'price' => $variant['price'],
                'sku' => $variant['sku'] ?? '',
                'inventoryQuantity' => $variant['inventoryQuantity'] ?? 0,
                'weight' => $variant['weight'] ?? 0,
                'weightUnit' => $variant['weightUnit'] ?? 'KILOGRAMS'
            ];

            // Thêm title nếu có
            if (isset($variant['title'])) {
                $variantInput['title'] = $variant['title'];
            }

            $result = $this->makeGraphQLRequest($mutation, ['input' => $variantInput]);
            
            // Nếu tạo variant thành công và có inventory, cập nhật inventory
            if ($result['success'] && isset($result['data']['productVariantCreate']['productVariant']['inventoryItem']['id'])) {
                $inventoryItemId = $result['data']['productVariantCreate']['productVariant']['inventoryItem']['id'];
                $quantity = $variant['inventoryQuantity'] ?? 0;
                
                if ($quantity > 0) {
                    $this->updateVariantInventory($inventoryItemId, $quantity);
                }
            }
        }
    }

    /**
     * Thêm tags cho product
     */
    private function addProductTags($productId, $tags)
    {
        if (empty($tags)) {
            return;
        }

        $mutation = '
        mutation productUpdate($input: ProductInput!) {
            productUpdate(input: $input) {
                product {
                    id
                    tags
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        $this->makeGraphQLRequest($mutation, [
            'input' => [
                'id' => $productId,
                'tags' => $tags
            ]
        ]);
    }

    /**
     * Thêm product vào collection
     */
    private function addProductToCollection($productId, $collectionId)
    {
        if (empty($collectionId)) {
            return;
        }

        $mutation = '
        mutation collectionAddProducts($id: ID!, $productIds: [ID!]!) {
            collectionAddProducts(id: $id, productIds: $productIds) {
                collection {
                    id
                    title
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        $result = $this->makeGraphQLRequest($mutation, [
            'id' => $collectionId,
            'productIds' => [$productId]
        ]);
        
        // Log kết quả để debug
        if (!$result['success']) {
            \Log::error('Lỗi thêm product vào collection: ' . json_encode($result));
        }
    }

    /**
     * Cập nhật inventory cho variant
     */
    private function updateVariantInventory($variantId, $quantity)
    {
        $mutation = '
        mutation inventorySetQuantity($input: InventorySetQuantityInput!) {
            inventorySetQuantity(input: $input) {
                inventoryLevel {
                    id
                    available
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        $result = $this->makeGraphQLRequest($mutation, [
            'input' => [
                'inventoryItemId' => $variantId,
                'quantity' => $quantity,
                'locationId' => env('SHOPIFY_LOCATION_ID', 'gid://shopify/Location/1')
            ]
        ]);
        
        // Log kết quả để debug
        if (!$result['success']) {
            \Log::error('Lỗi cập nhật inventory: ' . json_encode($result));
        }
    }

    /**
     * Thực hiện GraphQL request
     */
    protected function makeGraphQLRequest($query, $variables = [])
    {
        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->shopifyToken,
                'Content-Type' => 'application/json',
                'User-Agent' => 'ShopifySyncBot/1.0 (Laravel)'
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