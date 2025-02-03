<?php
declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;
use App\DataProvider\JsonDataProvider;
use App\Service\ShopifyProductService;
use App\Importer\ProductImporter;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dataProvider = new JsonDataProvider($_ENV['JSON_FILE_PATH']);

$shopifyService = new ShopifyProductService(
    $_ENV['SHOPIFY_URL'],
    $_ENV['ACCESS_TOKEN']
);

print_r($shopifyService->getProducts());
die;


$importer = new ProductImporter(
    $dataProvider,
    $shopifyService
);

$importer->import($_ENV['IMAGES_FOLDER_PATH']);
