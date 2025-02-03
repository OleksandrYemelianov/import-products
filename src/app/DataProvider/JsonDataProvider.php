<?php
declare(strict_types=1);

namespace App\DataProvider;

/**
 * Reads products from a JSON file.
 */
class JsonDataProvider implements DataProviderInterface
{
    private readonly string $filePath;

    public function __construct(string $filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return array<int, mixed>
     */
    public function getProducts(): array
    {
        if (!file_exists($this->filePath)) {
            return [];
        }

        $jsonContent = file_get_contents($this->filePath);
        $data = json_decode($jsonContent, true);
        return is_array($data) ? $data : [];
    }
}
