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
    $slug = trim((string) ($_POST['slug'] ?? ''));
    $category = trim((string) ($_POST['category'] ?? ''));

    $shortDescription = trim((string) ($_POST['short_description'] ?? ''));

    $cookTimeMinutes = (int) ($_POST['cook_time_minutes'] ?? 0);
    $servings = (int) ($_POST['servings'] ?? 0);
    $difficulty = trim((string) ($_POST['difficulty'] ?? 'Easy'));

    $isPublished = isset($_POST['is_published']) && (string) $_POST['is_published'] === '0' ? 0 : 1;
    $isFeatured = isset($_POST['is_featured']) && (string) $_POST['is_featured'] === '1' ? 1 : 0;

    // images: allow external URL OR upload
    $heroImageUrl = trim((string) ($_POST['hero_image_url'] ?? ''));
    $thumbnailImageUrl = trim((string) ($_POST['thumbnail_image_url'] ?? ''));

    $tipsRaw = trim((string) ($_POST['tips'] ?? ''));
    $tips = null;
    if ($tipsRaw !== '') {
        // tips may be JSON array or newline list; normalize to JSON array compatible with existing getter
        $decodedTips = decode_json_array($tipsRaw);
        if (!empty($decodedTips)) {
            $tips = $decodedTips;
        } else {
            // fallback: treat as one string tips (or newline list)
            $tipsList = normalize_multiline_list($tipsRaw);
            $tips = !empty($tipsList) ? $tipsList : null;
        }
    }

    $pdo = get_db();

    if ($name === '' || $slug === '' || $category === '' || $shortDescription === '') {
        json_response([
            'success' => false,
            'message' => 'Name, slug, category, and short description are required.',
        ], 422);
    }

    // Slug uniqueness enforcement
    $stmtDup = $pdo->prepare('SELECT id FROM recipes WHERE slug = :slug LIMIT 1');
    $stmtDup->execute(['slug' => $slug]);
    $dup = $stmtDup->fetch();
    if ($dup) {
        json_response([
            'success' => false,
            'message' => 'Recipe slug already exists',
        ], 409);
    }

    $heroImagePath = $heroImageUrl !== '' ? $heroImageUrl : null;
    $thumbnailImagePath = $thumbnailImageUrl !== '' ? $thumbnailImageUrl : null;

    $newHero = save_uploaded_image($_FILES['hero_image'] ?? []);
    if ($newHero) {
        $heroImagePath = $newHero;
    }

    $newThumb = save_uploaded_image($_FILES['thumbnail_image'] ?? []);
    if ($newThumb) {
        $thumbnailImagePath = $newThumb;
    }

    $ingredientRows = $_POST['ingredients'] ?? [];
    $stepRows = $_POST['steps'] ?? [];

    // Support JSON-stringified arrays from form submission
    if (is_string($ingredientRows)) {
        $decoded = decode_json_array($ingredientRows);
        $ingredientRows = is_array($decoded) ? $decoded : [];
    }
    if (is_string($stepRows)) {
        $decoded = decode_json_array($stepRows);
        $stepRows = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($ingredientRows)) $ingredientRows = [];
    if (!is_array($stepRows)) $stepRows = [];

    $ingredientTexts = [];
    foreach ($ingredientRows as $row) {
        $txt = is_array($row) ? trim((string) ($row['text'] ?? ($row['ingredient_text'] ?? ''))) : trim((string) $row);
        if ($txt !== '') $ingredientTexts[] = $txt;
    }

    $stepTexts = [];
    foreach ($stepRows as $row) {
        $txt = is_array($row) ? trim((string) ($row['text'] ?? ($row['step_text'] ?? ''))) : trim((string) $row);
        if ($txt !== '') $stepTexts[] = $txt;
    }

    $pdo->beginTransaction();

    // Insert into recipes
    $stmtRecipe = $pdo->prepare(
        'INSERT INTO recipes (name, slug, category, short_description, hero_image, thumbnail_image, cook_time_minutes, servings, difficulty, tips, is_featured, is_published)
         VALUES (:name, :slug, :category, :short_description, :hero_image, :thumbnail_image, :cook_time_minutes, :servings, :difficulty, :tips, :is_featured, :is_published)'
    );

    $stmtRecipe->execute([
        'name' => $name,
        'slug' => $slug,
        'category' => $category,
        'short_description' => $shortDescription,
        'hero_image' => $heroImagePath,
        'thumbnail_image' => $thumbnailImagePath,
        'cook_time_minutes' => $cookTimeMinutes,
        'servings' => $servings,
        'difficulty' => $difficulty !== '' ? $difficulty : 'Easy',
        'tips' => $tips !== null ? encode_json_value($tips) : null,
        'is_featured' => $isFeatured,
        'is_published' => $isPublished,
    ]);

    $recipeId = (int) $pdo->lastInsertId();

    // Insert ingredients
    $stmtIng = $pdo->prepare(
        'INSERT INTO recipe_ingredients (recipe_id, ingredient_order, ingredient_text)
         VALUES (:recipe_id, :ingredient_order, :ingredient_text)'
    );

    $order = 1;
    foreach ($ingredientTexts as $txt) {
        $stmtIng->execute([
            'recipe_id' => $recipeId,
            'ingredient_order' => $order,
            'ingredient_text' => $txt,
        ]);
        $order++;
    }

    // Insert steps
    $stmtSteps = $pdo->prepare(
        'INSERT INTO recipe_steps (recipe_id, step_order, step_text)
         VALUES (:recipe_id, :step_order, :step_text)'
    );

    $stepOrder = 1;
    foreach ($stepTexts as $txt) {
        $stmtSteps->execute([
            'recipe_id' => $recipeId,
            'step_order' => $stepOrder,
            'step_text' => $txt,
        ]);
        $stepOrder++;
    }

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Recipe added successfully.',
        'data' => [
            'id' => $recipeId,
        ],
    ], 201);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
?>
