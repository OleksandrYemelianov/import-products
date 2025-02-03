<?php
declare(strict_types=1);

namespace App\Importer;

use App\DataProvider\DataProviderInterface;
use App\Service\ShopifyServiceInterface;

/**
 * Orchestrates the import process using a data provider and Shopify service.
 */
class ProductImporter
{
    private readonly DataProviderInterface $dataProvider;
    private readonly ShopifyServiceInterface $shopifyService;

    public function __construct(
        DataProviderInterface $dataProvider,
        ShopifyServiceInterface $shopifyService
    ) {
        $this->dataProvider = $dataProvider;
        $this->shopifyService = $shopifyService;
    }

    /**
     * Imports products from the data source.
     */
    public function import(string $imagesFolderPath): void
    {
        $products = $this->dataProvider->getProducts();
        if (empty($products)) {
            return;
        }

        foreach ($products as $product) {
            $this->processProduct($product, $imagesFolderPath);
        }
    }

    /**
     * Determines if the product exists and updates or creates accordingly.
     */
    private function processProduct(array $productData, string $imagesFolderPath): void
    {
        $existingProduct = $this->shopifyService->getProductBySKU($productData['sku']);

        if ($existingProduct !== null) {
            $this->updateProduct($existingProduct['id'], $productData, $imagesFolderPath);
        } else {
            $this->createProduct($productData, $imagesFolderPath);
        }
    }

    /**
     * Updates an existing product.
     */
    private function updateProduct(int $productId, array $productData, string $imagesFolderPath): void
    {
        $updateData = [
            'product' => [
                'id' => $productId,
                'title' => $productData['name'],
                'body_html' => $productData['description'],
                'variants' => [[
                    'sku' => $productData['sku'],
                    'inventory_quantity' => $productData['stock'] ?? 0,
                    'price' => isset($productData['price']) ? (string) $productData['price'] : '0.00'
                ]]
            ]
        ];

        $imageId = $this->shopifyService->getImageId($productId, $productData['image']);
        if ($imageId !== null) {
            $updateData['product']['images'] = [['id' => $imageId]];
        }

        $this->shopifyService->updateProduct($productId, $updateData);
    }

    /**
     * Creates a new product.
     */
    private function createProduct(array $productData, string $imagesFolderPath): void
    {
        // Create product without an image
        $newProduct = [
            'product' => [
                'title' => $productData['name'],
                'body_html' => $productData['description'],
                'variants' => [[
                    'sku' => $productData['sku'],
                    'inventory_quantity' => $productData['stock'] ?? 0,
                    'option1' => $productData['attributes']['color'] ?? null,
                    'option2' => $productData['attributes']['memory'] ?? null,
                    'price' => isset($productData['price']) ? (string) $productData['price'] : '0.00'
                ]],
                'options' => [
                    [
                        "name" => "Color",
                        "values" => [
                            $productData['attributes']['color'] ?? null
                        ]
                    ],
                    [
                        "name" => "Memory",
                        "values" => [
                            $productData['attributes']['memory'] ?? null
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->shopifyService->createProduct($newProduct);

        if (!isset($response['product']['id'])) {
            throw new \RuntimeException('Failed to create product in Shopify');
        }

        $productId = (int) $response['product']['id'];

        // Upload image if it exists
        $imagePath = rtrim($imagesFolderPath, '/') . '/' . $productData['image'];
        if (file_exists($imagePath)) {
            $this->shopifyService->uploadImage($productId, $imagePath);
        }
    }
}