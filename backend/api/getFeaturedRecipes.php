<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $limit = (int) (($_GET['limit'] ?? 8));
    $limit = $limit > 0 ? min($limit, 12) : 8;

    $stmt = get_db()->prepare(
        'SELECT * FROM recipes WHERE is_published = 1 AND is_featured = 1 ORDER BY created_at DESC, id DESC LIMIT :limit'
    );
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

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
        'message' => 'Featured recipes loaded successfully.',
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}


