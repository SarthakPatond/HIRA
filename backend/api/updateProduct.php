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
    $id = (int) ($_POST['id'] ?? 0);
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

    $name = trim((string) ($_POST['name'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));
    $description = trim((string) ($_POST['description'] ?? ''));
    $imageUrl = trim((string) ($_POST['image_url'] ?? ''));
    $benefits = normalize_multiline_list($_POST['benefits'] ?? '');
    $packSizes = normalize_multiline_list($_POST['pack_sizes'] ?? '');
    $isComingSoon = isset($_POST['is_coming_soon']) && (string) $_POST['is_coming_soon'] === '1' ? 1 : 0;

    if ($name === '' || $category === '' || $description === '') {
        json_response([
            'success' => false,
            'message' => 'Name, category, and description are required.',
        ], 422);
    }

    $imagePath = $imageUrl !== '' ? $imageUrl : $existing['image_path'];
    $newImagePath = save_uploaded_image($_FILES['image'] ?? []);
    if ($newImagePath) {
        delete_local_upload($existing['image_path']);
        $imagePath = $newImagePath;
    } elseif ($imageUrl !== '' && $existing['image_path'] !== $imageUrl) {
        delete_local_upload($existing['image_path']);
    }

    $pdo = get_db();
    $stmt = $pdo->prepare(
        'UPDATE products
         SET name = :name, category = :category, description = :description, benefits = :benefits,
             pack_sizes = :pack_sizes, image = :image, is_coming_soon = :is_coming_soon
         WHERE id = :id'
    );
    $stmt->execute([
        'id' => $id,
        'name' => $name,
        'category' => $category,
        'description' => $description,
        'benefits' => encode_json_value($benefits),
        'pack_sizes' => encode_json_value($packSizes),
        'image' => $imagePath,
        'is_coming_soon' => $isComingSoon,
    ]);

    json_response([
        'success' => true,
        'message' => 'Product updated successfully.',
        'data' => fetch_product_by_id($id),
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
