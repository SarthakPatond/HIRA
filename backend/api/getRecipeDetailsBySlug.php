<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';
handle_cors();

try {
    $slug = trim((string) ($_GET['slug'] ?? ''));

    if ($slug === '') {
        json_response([
            'success' => false,
            'message' => 'Slug is required.',
        ], 422);
    }

    $stmt = get_db()->prepare('SELECT * FROM recipes WHERE slug = :slug AND is_published = 1 LIMIT 1');
    $stmt->execute(['slug' => $slug]);
    $recipe = $stmt->fetch();

    if (!$recipe) {
        json_response([
            'success' => false,
            'message' => 'Recipe not found.',
        ], 404);
    }

    $recipeId = (int) $recipe['id'];

    $stmtIngredients = get_db()->prepare(
        'SELECT ingredient_order, ingredient_text FROM recipe_ingredients WHERE recipe_id = :recipe_id ORDER BY ingredient_order ASC, id ASC'
    );
    $stmtIngredients->execute(['recipe_id' => $recipeId]);
    $ingredientRows = $stmtIngredients->fetchAll();

    $ingredients = array_map(static function (array $row): string {
        return (string) $row['ingredient_text'];
    }, $ingredientRows);

    $stmtSteps = get_db()->prepare(
        'SELECT step_order, step_text FROM recipe_steps WHERE recipe_id = :recipe_id ORDER BY step_order ASC, id ASC'
    );
    $stmtSteps->execute(['recipe_id' => $recipeId]);
    $stepRows = $stmtSteps->fetchAll();

    $steps = array_map(static function (array $row): string {
        return (string) $row['step_text'];
    }, $stepRows);

    $tips = null;
    $decodedTips = decode_json_array((string) ($recipe['tips'] ?? ''));
    if (!empty($decodedTips)) {
        $tips = $decodedTips;
    } else {
        $tipsText = trim((string) ($recipe['tips'] ?? ''));
        $tips = $tipsText !== '' ? [$tipsText] : null;
    }

    $relatedProducts = [];
    $relatedIds = decode_json_array((string) ($recipe['related_products_json'] ?? ''));
    if (!empty($relatedIds)) {
        $placeholders = implode(',', array_fill(0, count($relatedIds), '?'));
        $sql = "SELECT * FROM products WHERE id IN ($placeholders) ORDER BY created_at DESC, id DESC";
        $stmtRelated = get_db()->prepare($sql);
        $stmtRelated->execute(array_values($relatedIds));
        $relatedProductsRows = $stmtRelated->fetchAll();

        $relatedProducts = array_map(static function (array $p): array {
            return [
                'id' => (int) $p['id'],
                'name' => (string) $p['name'],
                'slug' => slugify((string) $p['name']) . '-' . (int) $p['id'],
                'category' => (string) $p['category'],
                'description' => (string) $p['description'],
                'image' => asset_url($p['image']),
                'is_coming_soon' => !empty($p['is_coming_soon']),
                'created_at' => $p['created_at'] ?? null,
            ];
        }, $relatedProductsRows);
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
            'tips' => $tips,
            'ingredients' => $ingredients,
            'steps' => $steps,
            'related_products' => $relatedProducts,
        ],
        'message' => 'Recipe details loaded successfully.',
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}

