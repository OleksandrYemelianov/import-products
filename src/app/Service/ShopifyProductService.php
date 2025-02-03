<?php
declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;

/**
 * Handles communication with the Shopify REST API.
 */
class ShopifyProductService implements ShopifyServiceInterface
{
    private readonly string $shopifyUrl;
    private readonly string $accessToken;
    private readonly Client $client;
    private readonly string $apiVersion;
    private readonly string $apiUrl;

    public function __construct(string $shopifyUrl, string $accessToken)
    {
        $this->shopifyUrl = rtrim($shopifyUrl, '/');
        $this->accessToken = $accessToken;
        $this->apiVersion = '2025-01';
        $this->apiUrl = sprintf('/admin/api/%s', $this->apiVersion);

        $this->client = new Client([
            'base_uri' => $this->shopifyUrl,
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $this->accessToken
            ]
        ]);
    }

    /**
     * Finds a product by SKU.
     *
     * @return array<mixed>|null
     */
    public function getProductBySKU(string $sku): ?array
    {
        $endpoint = $this->apiUrl . '/products.json';
        $response = $this->client->get($endpoint, [
            'query' => [
                'fields' => 'id,variants',
                'limit' => 250
            ]
        ]);

        $data = json_decode((string) $response->getBody(), true);
        $products = $data['products'] ?? [];

        foreach ($products as $product) {
            foreach ($product['variants'] as $variant) {
                if (($variant['sku'] ?? '') === $sku) {
                    return $product;
                }
            }
        }

        return null;
    }

    public function getProducts(): array
    {
        $endpoint = $this->apiUrl . '/products.json';
        $response = $this->client->get($endpoint);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Updates a product in Shopify.
     */
    public function updateProduct(int $productId, array $data): void
    {
        $endpoint = sprintf($this->apiUrl . '/products/%d.json', $productId);
        $this->client->put($endpoint, [
            'json' => $data
        ]);
    }

    /**
     * Creates a product in Shopify.
     */
    public function createProduct(array $data): array
    {
        $endpoint = $this->apiUrl . '/products.json';
        $response = $this->client->post($endpoint, [
            'json' => $data
        ]);

        return json_decode((string)$response->getBody(), true);
    }

    /**
     * Uploads an image to Shopify.
     */
    public function uploadImage(int $productId, string $imagePath): ?string
    {
        if (!file_exists($imagePath)) {
            return null;
        }

        $endpoint = sprintf('%s/products/%d/images.json', $this->apiUrl, $productId);
        $response = $this->client->post($endpoint, [
            'multipart' => [
                [
                    'name' => 'image[attachment]',
                    'contents' => base64_encode((string) file_get_contents($imagePath))
                ]
            ],
            //'debug' => true,
        ]);
        $image = json_decode((string) $response->getBody(), true);
        return $image['image']['src'] ?? null;
    }

    /**
     * Retrieves an image ID by file name.
     */
    public function getImageId(int $productId, string $fileName): ?int
    {
        $endpoint = sprintf($this->apiUrl . '/products/%d/images.json', $productId);
        $response = $this->client->get($endpoint);
        $data = json_decode((string) $response->getBody(), true);
        $images = $data['images'] ?? [];

        foreach ($images as $image) {
            if (basename($image['src']) === $fileName) {
                return $image['id'];
            }
        }
        return null;
    }
}