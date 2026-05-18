<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $slug = trim((string) ($_GET['slug'] ?? ''));

    if ($slug === '') {
        json_response([
            'success' => false,
            'message' => 'Slug is required.'
        ], 422);
    }

    $stmt = get_db()->prepare('SELECT * FROM recipes WHERE slug = :slug AND is_published = 1 LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $recipe = $stmt->fetch();

    if (!$recipe) {
        json_response([
            'success' => false,
            'message' => 'Recipe not found.'
        ], 404);
    }

    json_response([
        'success' => true,
        'data' => [
            'id' => (int) $recipe['id'],
            'name' => (string) $recipe['name'],
            'slug' => (string) $recipe['slug'],
            'category' => (string) $recipe['category'],
            'short_description' => (string) $recipe['short_description'],
            'hero_image' => asset_url($recipe['hero_image'] ?? null),
            'thumbnail_image' => asset_url($recipe['thumbnail_image'] ?? null),
            'cook_time_minutes' => (int) ($recipe['cook_time_minutes'] ?? 0),
            'servings' => (int) ($recipe['servings'] ?? 0),
            'difficulty' => (string) ($recipe['difficulty'] ?? 'Easy'),
            'tips' => $recipe['tips'] ?? null,
            'related_products_json' => $recipe['related_products_json'] ?? null,
            'is_featured' => !empty($recipe['is_featured']),
            'is_published' => !empty($recipe['is_published']),
            'created_at' => $recipe['created_at'] ?? null,
        ],
        'message' => 'Recipe loaded successfully.'
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}

