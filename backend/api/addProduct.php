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

    $imagePath = $imageUrl !== '' ? $imageUrl : null;
    $uploadedImage = save_uploaded_image($_FILES['image'] ?? []);
    if ($uploadedImage) {
        $imagePath = $uploadedImage;
    }

    $pdo = get_db();
    $stmt = $pdo->prepare(
        'INSERT INTO products (name, category, description, benefits, pack_sizes, image, is_coming_soon)
         VALUES (:name, :category, :description, :benefits, :pack_sizes, :image, :is_coming_soon)'
    );
    $stmt->execute([
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
        'message' => 'Product added successfully.',
        'data' => fetch_product_by_id((int) get_db()->lastInsertId()),
    ], 201);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
