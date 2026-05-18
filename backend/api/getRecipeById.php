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
            'message' => 'Recipe ID is required.',
        ], 422);
    }

    $stmt = get_db()->prepare('SELECT * FROM recipes WHERE id = :id AND (is_published = 1 OR 1=1) LIMIT 1');
    $stmt->execute(['id' => $id]);
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

    json_response([
        'success' => true,
        'message' => 'Recipe loaded successfully.',
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
            'featured' => !empty($recipe['is_featured']),
            'is_published' => !empty($recipe['is_published']),
            'ingredients' => $ingredients,
            'steps' => $steps,
            'related_products_json' => $recipe['related_products_json'] ?? null,
        ],
    ]);
} catch (Throwable $exception) {
    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
?>
