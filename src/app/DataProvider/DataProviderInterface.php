<?php
declare(strict_types=1);

namespace App\DataProvider;

/**
 * Provides an interface for any data source.
 */
interface DataProviderInterface
{
    /**
     * Returns an array of products from a data source.
     *
     * @return array<int, mixed>
     */
    public function getProducts(): array;
}