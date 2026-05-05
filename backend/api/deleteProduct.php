<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

if (!is_admin_logged_in()) {
    json_response([
        'success' => false,
        'message' => 'Unauthorized.',
    ], 401);
}

try {
    $data = request_data();
    $id = (int) ($data['id'] ?? 0);

    if ($id <= 0) {
        json_response([
            'success' => false,
            'message' => 'Product ID is required.',
        ], 422);
    }

    $existing = fetch_product_by_id($id);
    if (!$existing) {
        json_response([
            'success' => false,
            'message' => 'Product not found.',
        ], 404);
    }

    $stmt = get_db()->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    delete_local_upload($existing['image_path']);

    json_response([
        'success' => true,
        'message' => 'Product deleted successfully.',
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
