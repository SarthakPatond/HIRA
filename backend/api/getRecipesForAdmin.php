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
    $q = trim((string) ($_GET['q'] ?? ''));
    $status = trim((string) ($_GET['status'] ?? 'all')); // live | unpublished | all
    $category = trim((string) ($_GET['category'] ?? ''));

    $sql = 'SELECT * FROM recipes WHERE 1=1';
    $params = [];

    if ($q !== '') {
        $sql .= ' AND (name LIKE :needle OR slug LIKE :needle OR category LIKE :needle)';
        $params['needle'] = '%' . $q . '%';
    }

    if ($status === 'live') {
        $sql .= ' AND is_published = 1';
    } elseif ($status === 'unpublished') {
        $sql .= ' AND is_published = 0';
    }

    if ($category !== '') {
        $sql .= ' AND category = :category';
        $params['category'] = $category;
    }

    $sql .= ' ORDER BY is_featured DESC, created_at DESC, id DESC';

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
            'tips' => decode_json_array((string) ($r['tips'] ?? '')) ?? null,
            'featured' => !empty($r['is_featured']),
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
