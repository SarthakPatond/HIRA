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

    $stmt = get_db()->prepare('SELECT * FROM recipes WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $recipe = $stmt->fetch();

    if (!$recipe) {
        json_response([
            'success' => false,
            'message' => 'Recipe not found.',
        ], 404);
    }

    $pdo = get_db();
    $pdo->beginTransaction();

    $stmtIng = $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id');
    $stmtIng->execute(['recipe_id' => $id]);

    $stmtSteps = $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id = :recipe_id');
    $stmtSteps->execute(['recipe_id' => $id]);

    $stmtDel = $pdo->prepare('DELETE FROM recipes WHERE id = :id');
    $stmtDel->execute(['id' => $id]);

    // Delete uploaded images if local paths
    if (!empty($recipe['hero_image'])) {
        delete_local_upload($recipe['hero_image']);
    }
    if (!empty($recipe['thumbnail_image'])) {
        delete_local_upload($recipe['thumbnail_image']);
    }

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'Recipe deleted successfully.',
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
