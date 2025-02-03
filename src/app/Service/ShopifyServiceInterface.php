<?php

namespace app\Service;

interface ShopifyServiceInterface
{
    public function getProductBySKU(string $sku): ?array;
    public function createProduct(array $data): array;
    public function updateProduct(int $productId, array $data): void;
    public function getImageId(int $productId, string $fileName): ?int;
    public function uploadImage(int $productId, string $imagePath): ?string;
}