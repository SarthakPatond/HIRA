<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $includeComingSoon = ($_GET['include_coming_soon'] ?? '1') === '1';
    $category = isset($_GET['category']) ? trim((string) $_GET['category']) : null;
    $products = fetch_products($includeComingSoon, $category ?: null);

    json_response([
        'success' => true,
        'data' => $products,
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
