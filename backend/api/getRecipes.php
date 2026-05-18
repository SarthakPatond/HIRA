<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $category = trim((string) ($_GET['category'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? 'published')); // published | all

    $sql = 'SELECT r.* FROM recipes r WHERE r.is_published = 1';
    $params = [];

    if ($status === 'all') {
        $sql = 'SELECT r.* FROM recipes r WHERE 1=1';
    }

    if ($category !== '') {
        $sql .= ' AND r.category = :category';
        $params['category'] = $category;
    }

    $sql .= ' ORDER BY r.is_featured DESC, r.created_at DESC, r.id DESC';

    $stmt = get_db()->prepare($sql);
    $stmt->execute($params);

    $rows = $stmt->fetchAll();

    $recipes = array_map(static function (array $r): array {
        return [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'slug' => (string) $r['slug'],
            'category' => (string) $r['category'],
            'short_description' => (string) $r['short_description'],
            'hero_image' => asset_url($r['hero_image'] ?? null),
            'thumbnail_image' => asset_url($r['thumbnail_image'] ?? null),
            'cook_time_minutes' => (int) ($r['cook_time_minutes'] ?? 0),
            'servings' => (int) ($r['servings'] ?? 0),
            'difficulty' => (string) ($r['difficulty'] ?? 'Easy'),
            'tips' => $r['tips'] ?? null,
            'related_products_json' => $r['related_products_json'] ?? null,
            'is_featured' => !empty($r['is_featured']),
            'is_published' => !empty($r['is_published']),
            'created_at' => $r['created_at'] ?? null,
        ];
    }, $rows);

    json_response([
        'success' => true,
        'data' => $recipes,
        'message' => 'Recipes loaded successfully.',
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
