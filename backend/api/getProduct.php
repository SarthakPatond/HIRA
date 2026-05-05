<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $id = (int) ($_GET['id'] ?? 0);
    if ($id <= 0) {
        json_response([
            'success' => false,
            'message' => 'Product ID is required.',
        ], 422);
    }

    $product = fetch_product_by_id($id);
    if (!$product) {
        json_response([
            'success' => false,
            'message' => 'Product not found.',
        ], 404);
    }

    json_response([
        'success' => true,
        'data' => $product,
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
