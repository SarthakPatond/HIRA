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

    $existingStmt = get_db()->prepare('SELECT * FROM recipes WHERE id = :id LIMIT 1');
    $existingStmt->execute(['id' => $id]);
    $existing = $existingStmt->fetch();

    if (!$existing) {
        json_response([
            'success' => false,
            'message' => 'Recipe not found.',
        ], 404);
    }

    $name = trim((string) ($data['name'] ?? ''));
    $slug = trim((string) ($data['slug'] ?? ''));
    $category = trim((string) ($data['category'] ?? ''));

    $shortDescription = trim((string) ($data['short_description'] ?? ''));
    $cookTimeMinutes = (int) ($data['cook_time_minutes'] ?? 0);
    $servings = (int) ($data['servings'] ?? 0);
    $difficulty = trim((string) ($data['difficulty'] ?? 'Easy'));

    $isPublished = isset($data['is_published']) ? ((string) $data['is_published'] === '0' ? 0 : 1) : (int) (!empty($existing['is_published']));
    $isFeatured = isset($data['is_featured']) ? ((string) $data['is_featured'] === '1' ? 1 : 0) : (int) (!empty($existing['is_featured']));

    $heroImageUrl = trim((string) ($data['hero_image_url'] ?? ''));
    $thumbnailImageUrl = trim((string) ($data['thumbnail_image_url'] ?? ''));

    $tipsRaw = trim((string) ($data['tips'] ?? ''));
    $tips = null;
    if ($tipsRaw !== '') {
        $decodedTips = decode_json_array($tipsRaw);
        if (!empty($decodedTips)) {
            $tips = $decodedTips;
        } else {
            $tipsList = normalize_multiline_list($tipsRaw);
            $tips = !empty($tipsList) ? $tipsList : null;
        }
    }

    if ($name === '' || $slug === '' || $category === '' || $shortDescription === '') {
        json_response([
            'success' => false,
            'message' => 'Name, slug, category, and short description are required.',
        ], 422);
    }

    // Slug uniqueness enforcement (allow same slug for the same recipe id)
    $stmtDup = get_db()->prepare('SELECT id FROM recipes WHERE slug = :slug AND id <> :id LIMIT 1');
    $stmtDup->execute([
        'slug' => $slug,
        'id' => $id
    ]);
    $dup = $stmtDup->fetch();
    if ($dup) {
        json_response([
            'success' => false,
            'message' => 'Recipe slug already exists',
        ], 409);
    }

    $heroImagePath = $heroImageUrl !== '' ? $heroImageUrl : ($existing['hero_image'] ?? null);
    $thumbnailImagePath = $thumbnailImageUrl !== '' ? $thumbnailImageUrl : ($existing['thumbnail_image'] ?? null);

    $newHero = save_uploaded_image($_FILES['hero_image'] ?? []);
    if ($newHero) {
        if (!empty($existing['hero_image'])) delete_local_upload($existing['hero_image']);
        $heroImagePath = $newHero;
    } elseif ($heroImageUrl !== '' && ($existing['hero_image'] ?? null) !== $heroImageUrl) {
        if (!empty($existing['hero_image'])) delete_local_upload($existing['hero_image']);
    }

    $newThumb = save_uploaded_image($_FILES['thumbnail_image'] ?? []);
    if ($newThumb) {
        if (!empty($existing['thumbnail_image'])) delete_local_upload($existing['thumbnail_image']);
        $thumbnailImagePath = $newThumb;
    } elseif ($thumbnailImageUrl !== '' && ($existing['thumbnail_image'] ?? null) !== $thumbnailImageUrl) {
        if (!empty($existing['thumbnail_image'])) delete_local_upload($existing['thumbnail_image']);
    }

    $ingredientRows = $data['ingredients'] ?? [];
    $stepRows = $data['steps'] ?? [];

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

    $pdo = get_db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        'UPDATE recipes
         SET name = :name,
             slug = :slug,
             category = :category,
             short_description = :short_description,
             hero_image = :hero_image,
             thumbnail_image = :thumbnail_image,
             cook_time_minutes = :cook_time_minutes,
             servings = :servings,
             difficulty = :difficulty,
             tips = :tips,
             is_featured = :is_featured,
             is_published = :is_published
         WHERE id = :id'
    );

    $stmt->execute([
        'id' => $id,
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

    // Replace ingredients/steps rows
    $stmtDelIng = $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id');
    $stmtDelIng->execute(['recipe_id' => $id]);

    $stmtDelSteps = $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id = :recipe_id');
    $stmtDelSteps->execute(['recipe_id' => $id]);

    $stmtIng = $pdo->prepare(
        'INSERT INTO recipe_ingredients (recipe_id, ingredient_order, ingredient_text)
         VALUES (:recipe_id, :ingredient_order, :ingredient_text)'
    );

    $order = 1;
    foreach ($ingredientTexts as $txt) {
        $stmtIng->execute([
            'recipe_id' => $id,
            'ingredient_order' => $order,
            'ingredient_text' => $txt,
        ]);
        $order++;
    }

    $stmtSteps = $pdo->prepare(
        'INSERT INTO recipe_steps (recipe_id, step_order, step_text)
         VALUES (:recipe_id, :step_order, :step_text)'
    );

    $stepOrder = 1;
    foreach ($stepTexts as $txt) {
        $stmtSteps->execute([
            'recipe_id' => $id,
            'step_order' => $stepOrder,
            'step_text' => $txt,
        ]);
        $stepOrder++;
    }

    $pdo->commit();

    // Return updated recipe summary
    $out = get_db()->prepare('SELECT * FROM recipes WHERE id = :id LIMIT 1');
    $out->execute(['id' => $id]);
    $updated = $out->fetch();

    json_response([
        'success' => true,
        'message' => 'Recipe updated successfully.',
        'data' => [
            'id' => (int) $updated['id'],
        ],
    ]);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_response([
        'success' => false,
        'message' => $exception->getMessage(),
    ], 500);
}
?>
