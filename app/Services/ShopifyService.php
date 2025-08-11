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
    private $throttleStatus = [
        'currentlyAvailable' => 1000,
        'maximumAvailable' => 1000,
        'restoreRate' => 50
    ];
    private $lastRequestTime = 0;

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
     * Tạo product mới theo quy trình chuẩn Shopify
     */
    public function createProduct($productData)
    {
        // Tách các thành phần không thuộc ProductInput
        $variants = $productData['variants'] ?? [];
        $images = $productData['images'] ?? [];
        $tags = $productData['tags'] ?? '';
        $collectionId = $productData['collectionId'] ?? null;
        
        // Loại bỏ các field không hợp lệ khỏi ProductInput
        unset($productData['variants'], $productData['images'], $productData['tags'], $productData['collectionId']);

        // Bước 1: Tạo product "rỗng" (chỉ có 1 default variant)
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

        $result = $this->makeGraphQLRequest($mutation, ['input' => $productData]);
        
        if (!($result['success'] ?? false)) {
            \Log::error("Failed to create product: " . json_encode($result));
            return false;
        }

        $product = $result['data']['productCreate']['product'] ?? null;
        if (!$product) {
            \Log::error("Product creation failed: " . json_encode($result));
            return false;
        }

        $productId = $product['id'];
        \Log::info("Product created successfully with ID: {$productId}");

        // Bước 2: Tạo variants với price và inventory (nếu có variants)
        if (!empty($variants)) {
            $this->createProductVariantsWithInventory($productId, $variants);
        }

        // Trả về product ID và các thành phần cần thêm sau
        return [
            'id' => $productId,
            'variants' => $variants,
            'images' => $images,
            'tags' => $tags,
            'collectionId' => $collectionId
        ];
    }

    /**
     * Lấy Location ID đầu tiên từ Shopify
     */
    public function getFirstLocationId()
    {
        $query = '
        query {
            locations(first: 1) {
                edges {
                    node {
                        id
                    }
                }
            }
        }';

        $result = $this->makeGraphQLRequest($query);

        if ($result && ($result['success'] ?? false)) {
            $locations = $result['data']['locations']['edges'] ?? [];
            if (!empty($locations)) {
                return $locations[0]['node']['id'];
            }
        }

        // Fallback về location mặc định nếu không lấy được
        return 'gid://shopify/Location/1';
    }

    /**
     * Tạo variants với price và inventory theo quy trình chuẩn
     */
    private function createProductVariantsWithInventory($productId, $variants)
    {
        // Tự động lấy Location ID đầu tiên
        $locationId = $this->getFirstLocationId();
        \Log::info("Using location ID: {$locationId}");
        
        // Chuẩn bị variants data cho bulk create
        $variantsInput = [];
        foreach ($variants as $variant) {
            $variantInput = [
                'price' => $variant['price'] ?? '0.00',
                'optionValues' => [
                    [
                        'optionName' => 'Title',
                        'name' => (string)($variant['title'] ?? 'Default Title')
                    ]
                ],
                'inventoryItem' => [
                    'tracked' => true,
                    'sku' => $variant['sku'] ?? ''
                ]
            ];

            // Thêm inventory quantities nếu có - theo schema mới Shopify 2025-01
            if (isset($variant['inventoryQuantity']) && $variant['inventoryQuantity'] > 0) {
                $variantInput['inventoryQuantities'] = [
                    [
                        'locationId' => $locationId,
                        'availableQuantity' => $variant['inventoryQuantity']
                    ]
                ];
            }

            $variantsInput[] = $variantInput;
        }

        \Log::info("Creating variants with data: " . json_encode($variantsInput));

        $mutation = '
        mutation productVariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
            productVariantsBulkCreate(
                productId: $productId,
                strategy: REMOVE_STANDALONE_VARIANT,
                variants: $variants
            ) {
                product {
                    id
                    variants(first: 10) {
                        edges {
                            node {
                                id
                                title
                                sku
                                price
                                inventoryItem {
                                    id
                                    tracked
                                }
                            }
                        }
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }';

        $result = $this->makeGraphQLRequest($mutation, [
            'productId' => $productId,
            'variants' => $variantsInput
        ]);

        if ($result['success'] ?? false) {
            $productData = $result['data']['productVariantsBulkCreate']['product'] ?? null;
            if ($productData && isset($productData['variants']['edges'])) {
                foreach ($productData['variants']['edges'] as $variantEdge) {
                    $variant = $variantEdge['node'];
                    \Log::info("Variant created successfully: " . json_encode([
                        'id' => $variant['id'],
                        'title' => $variant['title'],
                        'sku' => $variant['sku'],
                        'price' => $variant['price'],
                        'tracked' => $variant['inventoryItem']['tracked']
                    ]));
                }
            }
        } else {
            \Log::error("Failed to create variants: " . json_encode($result));
        }
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

        // Chỉ cập nhật thông tin cơ bản, không thêm lại images/variants
        $cleanProductData = array_diff_key($productData, array_flip(['images', 'variants', 'tags', 'collections', 'collectionId']));
        $cleanProductData['id'] = $productId;
        
        $result = $this->makeGraphQLRequest($mutation, ['input' => $cleanProductData]);
        
        // Chỉ thêm tags và collection assignment nếu cần
        if ($result['success']) {
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
    public function addProductImages($productId, $images)
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
        foreach ($variants as $index => $variant) {
            $variantNumber = $index + 1;
            \Log::info("Tạo variant {$variantNumber}: " . json_encode([
                'price' => $variant['price'] ?? 'N/A',
                'sku' => $variant['sku'] ?? 'N/A',
                'title' => $variant['title'] ?? 'N/A',
                'inventoryQuantity' => $variant['inventoryQuantity'] ?? 0
            ]));

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
                        inventoryQuantity
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
                'weightUnit' => $variant['weightUnit'] ?? 'KILOGRAMS',
                'inventoryPolicy' => 'DENY' // Bật inventory tracking
            ];

            // Thêm title nếu có
            if (isset($variant['title'])) {
                $variantInput['title'] = $variant['title'];
            }

            $result = $this->makeGraphQLRequest($mutation, ['input' => $variantInput]);
            
            if (!($result['success'] ?? false)) {
                \Log::error("Lỗi tạo variant: " . json_encode($result));
                continue;
            }

            $variantData = $result['data']['productVariantCreate']['productVariant'] ?? null;
            if ($variantData) {
                \Log::info("Variant tạo thành công: " . json_encode([
                    'id' => $variantData['id'],
                    'title' => $variantData['title'],
                    'sku' => $variantData['sku'],
                    'price' => $variantData['price'],
                    'inventoryQuantity' => $variantData['inventoryQuantity']
                ]));

                // Cập nhật inventory nếu có inventoryItem ID
                if (isset($variantData['inventoryItem']['id'])) {
                    $inventoryItemId = $variantData['inventoryItem']['id'];
                    $quantity = $variant['inventoryQuantity'] ?? 0;
                    
                    if ($quantity > 0) {
                        \Log::info("Cập nhật inventory cho variant: {$quantity}");
                        $this->updateVariantInventory($inventoryItemId, $quantity);
                    }
                }
            }
        }
    }

    /**
     * Thêm tags cho product
     */
    public function addProductTags($productId, $tags)
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
    public function addProductToCollection($productId, $collectionId)
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
     * Thêm ảnh cho collection
     */
    public function addCollectionImage($collectionId, $imageUrl, $altText = '')
    {
        try {
            \Log::info("Starting collection image upload for collection ID: {$collectionId}");
            \Log::info("Image URL: {$imageUrl}");
            
            // Sử dụng Staged Uploads flow của Shopify
            $originalSource = $this->uploadImageViaStagedUploads($imageUrl, $altText);
            if (!$originalSource) {
                \Log::error("Failed to upload image via Staged Uploads: {$imageUrl}");
                return false;
            }
            
            \Log::info("Image uploaded via Staged Uploads successfully. Original source: " . substr($originalSource, 0, 50) . "...");

            // Sử dụng collectionUpdate với ImageInput.src
            $mutation = '
            mutation collectionUpdate($input: CollectionInput!) {
                collectionUpdate(input: $input) {
                    collection { 
                        id 
                        title 
                        image { 
                            altText 
                            url 
                        } 
                    }
                    userErrors { 
                        field 
                        message 
                    }
                }
            }';

            $input = [
                'id' => $collectionId,
                'image' => [
                    'src' => $originalSource,   // URL staged
                    'altText' => $altText ?: 'Collection image',
                ],
            ];

            \Log::info("Sending collection image update to Shopify with input: " . json_encode($input));

            $result = $this->makeGraphQLRequest($mutation, ['input' => $input]);

            if (!($result['success'] ?? false)) {
                \Log::error('GraphQL request failed for collection image: ' . json_encode($result));
                return false;
            }

            $payload = $result['data']['collectionUpdate'] ?? [];
            if (!empty($payload['userErrors'])) {
                \Log::error('Shopify collection userErrors: ' . json_encode($payload['userErrors']));
                return false;
            }

            $collection = $payload['collection'] ?? [];
            $image = $collection['image'] ?? null;
            
            if ($image) {
                \Log::info('Collection image uploaded successfully: ' . json_encode($image));
            } else {
                \Log::warning('Collection updated but no image data returned');
            }
            
            return true;

        } catch (\Throwable $e) {
            \Log::error('Exception in addCollectionImage: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return false;
        }
    }



    /**
     * Cập nhật inventory cho variant
     */
    public function updateVariantInventory($variantId, $quantity)
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
     * Xóa collection
     */
    public function deleteCollection($collectionId)
    {
        $mutation = '
        mutation collectionDelete($input: CollectionDeleteInput!) {
            collectionDelete(input: $input) {
                deletedCollectionId
                userErrors {
                    field
                    message
                }
            }
        }';

        return $this->makeGraphQLRequest($mutation, ['input' => ['id' => $collectionId]]);
    }





    /**
     * Đếm tổng số products trên Shopify
     */
    public function countProducts()
    {
        try {
            \Log::info("🔢 Counting products on Shopify...");
            
            $query = '
            query {
                products(first: 1) {
                    pageInfo {
                        hasNextPage
                        endCursor
                    }
                }
            }';

            $result = $this->makeGraphQLRequest($query);
            
            if (!($result['success'] ?? false)) {
                \Log::error("Failed to count products: " . json_encode($result));
                return false;
            }

            // Shopify không cung cấp total count trực tiếp, nên ta sẽ đếm bằng cách fetch tất cả
            $totalCount = 0;
            $hasNextPage = true;
            $cursor = null;
            $pageCount = 0;

            while ($hasNextPage) {
                $pageCount++;
                
                $query = '
                query($first: Int!, $after: String) {
                    products(first: $first, after: $after) {
                        edges {
                            node {
                                id
                            }
                        }
                        pageInfo {
                            hasNextPage
                            endCursor
                        }
                    }
                }';

                $variables = ['first' => 250];
                if ($cursor) {
                    $variables['after'] = $cursor;
                }

                $result = $this->makeGraphQLRequest($query, $variables);
                
                if (!($result['success'] ?? false)) {
                    \Log::error("Failed to count products page {$pageCount}: " . json_encode($result));
                    return false;
                }

                $products = $result['data']['products']['edges'] ?? [];
                $totalCount += count($products);
                
                $pageInfo = $result['data']['products']['pageInfo'] ?? [];
                $hasNextPage = $pageInfo['hasNextPage'] ?? false;
                $cursor = $pageInfo['endCursor'] ?? null;
                
                \Log::info("Counted page {$pageCount}: " . count($products) . " products (Total: {$totalCount})");
            }

            \Log::info("📈 Total products count: {$totalCount}");
            return $totalCount;

        } catch (\Throwable $e) {
            \Log::error("Error counting products: " . $e->getMessage());
            return false;
        }
    }

    public function deleteAllProducts()
    {
        try {
            \Log::info("Starting fetch all products for deletion...");
            
            // Lấy danh sách tất cả products
            $allProducts = [];
            $hasNextPage = true;
            $cursor = null;
            $pageCount = 0;

            while ($hasNextPage) {
                $pageCount++;
                \Log::info("Fetching page {$pageCount}...");
                
                $query = '
                query($first: Int!, $after: String) {
                    products(first: $first, after: $after) {
                        edges {
                            node {
                                id
                                title
                                handle
                            }
                        }
                        pageInfo {
                            hasNextPage
                            endCursor
                        }
                    }
                }';

                $variables = ['first' => 250];
                if ($cursor) {
                    $variables['after'] = $cursor;
                }

                $result = $this->makeGraphQLRequest($query, $variables);
                
                if (!($result['success'] ?? false)) {
                    \Log::error("Failed to fetch products page {$pageCount}: " . json_encode($result));
                    return false;
                }

                $products = $result['data']['products']['edges'] ?? [];
                \Log::info("Found " . count($products) . " products on page {$pageCount}");
                
                foreach ($products as $edge) {
                    $allProducts[] = $edge['node'];
                }

                $pageInfo = $result['data']['products']['pageInfo'] ?? [];
                $hasNextPage = $pageInfo['hasNextPage'] ?? false;
                $cursor = $pageInfo['endCursor'] ?? null;
            }

            \Log::info("Total products found: " . count($allProducts));
            
            if (count($allProducts) === 0) {
                \Log::info("No products to delete");
                return [];
            }

            return $allProducts;

        } catch (\Throwable $e) {
            \Log::error("Error in deleteAllProducts: " . $e->getMessage());
            return false;
        }
    }







    /**
     * Xóa một product theo ID
     */
    public function deleteProduct($productId)
    {
        $mutation = '
        mutation productDelete($input: ProductDeleteInput!) {
            productDelete(input: $input) {
                deletedProductId
                userErrors {
                    field
                    message
                }
            }
        }';

        $result = $this->makeGraphQLRequest($mutation, [
            'input' => [
                'id' => $productId
            ]
        ]);

        if (!($result['success'] ?? false)) {
            \Log::error("Error deleting product: " . json_encode($result));
            return false;
        }

        $userErrors = $result['data']['productDelete']['userErrors'] ?? [];
        if (!empty($userErrors)) {
            $errorMessage = $userErrors[0]['message'] ?? '';
            
            // Nếu product không tồn tại, coi như thành công (đã được xóa)
            if (strpos($errorMessage, 'Product does not exist') !== false || 
                strpos($errorMessage, 'does not exist') !== false) {
                \Log::info("Product already deleted or does not exist");
                return true;
            }
            
            \Log::error("User errors when deleting product: " . json_encode($userErrors));
            return false;
        }

        return true;
    }

    /**
     * Thực hiện GraphQL request với smart cost limiting
     */
    public function makeGraphQLRequest($query, $variables = [])
    {
        try {
            // Smart cost limiting dựa trên throttleStatus
            $this->enforceSmartCostLimit();
            
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
                
                // Cập nhật throttle status từ response
                $this->updateThrottleStatus($data);
                
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

    /**
     * Smart cost limiting dựa trên throttleStatus
     */
    private function enforceSmartCostLimit()
    {
        $now = time();
        $timeDiff = $now - $this->lastRequestTime;
        
        // Tính cost đã được restore
        $restoredCost = $timeDiff * $this->throttleStatus['restoreRate'];
        $this->throttleStatus['currentlyAvailable'] = min(
            $this->throttleStatus['maximumAvailable'],
            $this->throttleStatus['currentlyAvailable'] + $restoredCost
        );
        
        // Nếu cost sắp cạn (dưới 100), đợi
        if ($this->throttleStatus['currentlyAvailable'] < 100) {
            $neededCost = 100 - $this->throttleStatus['currentlyAvailable'];
            $sleepTime = ceil($neededCost / $this->throttleStatus['restoreRate']);
            
            \Log::info("Cost low ({$this->throttleStatus['currentlyAvailable']}), sleeping for {$sleepTime}s");
            sleep($sleepTime);
            
            // Cập nhật sau khi sleep
            $this->throttleStatus['currentlyAvailable'] = min(
                $this->throttleStatus['maximumAvailable'],
                $this->throttleStatus['currentlyAvailable'] + ($sleepTime * $this->throttleStatus['restoreRate'])
            );
        }
        
        $this->lastRequestTime = $now;
    }

    /**
     * Cập nhật throttle status từ response
     */
    private function updateThrottleStatus($data)
    {
        if (isset($data['extensions']['cost']['throttleStatus'])) {
            $throttle = $data['extensions']['cost']['throttleStatus'];
            
            $this->throttleStatus['currentlyAvailable'] = $throttle['currentlyAvailable'] ?? $this->throttleStatus['currentlyAvailable'];
            $this->throttleStatus['maximumAvailable'] = $throttle['maximumAvailable'] ?? $this->throttleStatus['maximumAvailable'];
            $this->throttleStatus['restoreRate'] = $throttle['restoreRate'] ?? $this->throttleStatus['restoreRate'];
            
            $requestedCost = $data['extensions']['cost']['requestedQueryCost'] ?? 0;
            $this->throttleStatus['currentlyAvailable'] -= $requestedCost;
            
            \Log::debug("Cost used: {$requestedCost}, Available: {$this->throttleStatus['currentlyAvailable']}, Restore rate: {$this->throttleStatus['restoreRate']}/s");
        }
    }
}