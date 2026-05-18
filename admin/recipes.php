<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_admin_login();

// Delete recipe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'delete')) {
    $id = (int) ($_POST['id'] ?? 0);
    // use backend API pattern-less direct delete to mirror products behavior would violate "reuse conventions".
    // We'll call DB directly like products.php does.

    $stmt = get_db()->prepare('SELECT hero_image, thumbnail_image FROM recipes WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $recipe = $stmt->fetch();

    if ($recipe) {
        $pdo = get_db();
        $pdo->beginTransaction();

        $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id')->execute(['recipe_id' => $id]);
        $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id = :recipe_id')->execute(['recipe_id' => $id]);
        $pdo->prepare('DELETE FROM recipes WHERE id = :id')->execute(['id' => $id]);

        if (!empty($recipe['hero_image'])) delete_local_upload($recipe['hero_image']);
        if (!empty($recipe['thumbnail_image'])) delete_local_upload($recipe['thumbnail_image']);

        $pdo->commit();
        set_flash('success', 'Recipe deleted successfully.');
    } else {
        set_flash('error', 'Recipe not found.');
    }

    redirect('/HIRA/admin/recipes.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? 'all'); // all|published|unpublished
$categoryFilter = trim((string) ($_GET['category'] ?? ''));

// Fetch all recipes for admin list (mirrors filters UI)
$sql = 'SELECT * FROM recipes WHERE 1=1';
$params = [];

if ($q !== '') {
    $sql .= ' AND (name LIKE :needle OR slug LIKE :needle OR category LIKE :needle)';
    $params['needle'] = '%' . $q . '%';
}

if ($statusFilter === 'published') {
    $sql .= ' AND is_published = 1';
} elseif ($statusFilter === 'unpublished') {
    $sql .= ' AND is_published = 0';
}

if ($categoryFilter !== '') {
    $sql .= ' AND category = :category';
    $params['category'] = $categoryFilter;
}

$sql .= ' ORDER BY is_featured DESC, created_at DESC, id DESC';

$stmt = get_db()->prepare($sql);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// categories for filter dropdown
$categories = [];
foreach ($recipes as $r) {
    $cat = trim((string) ($r['category'] ?? ''));
    if ($cat !== '') $categories[] = $cat;
}
$categories = array_values(array_unique($categories));

render_admin_header('Recipes', 'recipes.php');
?>
<section class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;flex-wrap:wrap;">
    <div>
      <p class="brand-kicker" style="color:#5e7c39;">Catalog Management</p>
      <h3>Recipes</h3>
      <div class="hint" style="margin-top:6px;">
        <?php
        $activeFilters = [];
        if ($q !== '') $activeFilters[] = 'Search: ' . $q;
        if ($statusFilter !== 'all') $activeFilters[] = ($statusFilter === 'published' ? 'Published' : 'Unpublished');
        if ($categoryFilter !== '') $activeFilters[] = 'Category: ' . $categoryFilter;
        echo $activeFilters ? 'Filtering: ' . e(implode(' • ', $activeFilters)) : 'Use search and filters to quickly find recipes.';
        ?>
      </div>
    </div>
    <a class="btn" href="/HIRA/admin/recipe-form.php">Add Recipe</a>
  </div>

  <div class="form-card" style="padding:16px; margin-bottom:18px;">
    <form method="get" action="/HIRA/admin/recipes.php">
      <div class="form-grid" style="grid-template-columns: 1.6fr 1fr 1fr; align-items:end;">
        <div class="field">
          <label for="q">Search</label>
          <input id="q" type="text" name="q" value="<?php echo e($q); ?>" placeholder="Recipe name or category">
        </div>
        <div class="field">
          <label for="category">Category</label>
          <select id="category" name="category">
            <option value="" <?php echo $categoryFilter === '' ? 'selected' : ''; ?>>All</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo e($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>><?php echo e($cat); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
            <option value="unpublished" <?php echo $statusFilter === 'unpublished' ? 'selected' : ''; ?>>Unpublished</option>
          </select>
        </div>
      </div>

      <div class="actions" style="margin-top:14px;">
        <button type="submit">Apply</button>
        <a class="btn secondary" href="/HIRA/admin/recipes.php">Reset</a>
      </div>
    </form>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Image</th>
          <th>Name</th>
          <th>Category</th>
          <th>Cook time</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($recipes as $recipe): ?>
          <tr>
            <td>
              <?php if (!empty($recipe['thumbnail_image'])): ?>
                <img class="thumb" src="<?php echo e($recipe['thumbnail_image']); ?>" alt="<?php echo e($recipe['name']); ?>">
              <?php elseif (!empty($recipe['hero_image'])): ?>
                <img class="thumb" src="<?php echo e($recipe['hero_image']); ?>" alt="<?php echo e($recipe['name']); ?>">
              <?php else: ?>
                <span class="hint">No image</span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?php echo e($recipe['name']); ?></strong>
              <div class="hint">Slug: <?php echo e($recipe['slug']); ?></div>
              <div class="hint"><?php echo e(mb_strimwidth((string) ($recipe['short_description'] ?? ''), 0, 90, '...')); ?></div>
            </td>
            <td><?php echo e($recipe['category']); ?></td>
            <td>
              <?php echo (int) ($recipe['cook_time_minutes'] ?? 0); ?> min
            </td>
            <td>
              <?php if (!empty($recipe['is_published'])): ?>
                <span class="status-pill live">Published</span>
              <?php else: ?>
                <span class="status-pill coming">Unpublished</span>
              <?php endif; ?>
            </td>
            <td>
              <div class="actions">
                <a class="btn secondary" href="/HIRA/admin/recipe-form.php?id=<?php echo (int) $recipe['id']; ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Delete this recipe permanently?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo (int) $recipe['id']; ?>">
                  <button type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (empty($recipes)): ?>
          <tr>
            <td colspan="6">No recipes match current filters.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
render_admin_footer();
?>

