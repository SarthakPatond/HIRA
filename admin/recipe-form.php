<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_admin_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;

// When editing, load recipe + ingredients/steps
$recipe = null;
$ingredients = [];
$steps = [];

if ($editing) {
    $stmt = get_db()->prepare('SELECT * FROM recipes WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $recipe = $stmt->fetch();

    if (!$recipe) {
        set_flash('error', 'Recipe not found.');
        redirect('/HIRA/admin/recipes.php');
    }

    $stmtIng = get_db()->prepare(
        'SELECT ingredient_order, ingredient_text FROM recipe_ingredients WHERE recipe_id = :recipe_id ORDER BY ingredient_order ASC, id ASC'
    );
    $stmtIng->execute(['recipe_id' => $id]);
    $ingredientRows = $stmtIng->fetchAll();
    $ingredients = array_map(static function (array $r): string {
        return (string) $r['ingredient_text'];
    }, $ingredientRows);

    $stmtSteps = get_db()->prepare(
        'SELECT step_order, step_text FROM recipe_steps WHERE recipe_id = :recipe_id ORDER BY step_order ASC, id ASC'
    );
    $stmtSteps->execute(['recipe_id' => $id]);
    $stepRows = $stmtSteps->fetchAll();
    $steps = array_map(static function (array $r): string {
        return (string) $r['step_text'];
    }, $stepRows);
}

function recipe_to_multiline_json_like(?string $json, array $fallback = []): string {
    $val = $json ?? '';
    if ($val === '') return implode(PHP_EOL, $fallback);

    // We store tips as JSON array (or nullable). For form editing we only want newline text.
    $decoded = decode_json_array($val);
    if (!empty($decoded) && is_array($decoded)) {
        return implode(PHP_EOL, array_map(static fn($x) => (string)$x, $decoded));
    }

    return implode(PHP_EOL, $fallback);
}

$formData = [
    'name' => $recipe['name'] ?? '',
    'slug' => $recipe['slug'] ?? '',
    'category' => $recipe['category'] ?? '',
    'short_description' => $recipe['short_description'] ?? '',
    'cook_time_minutes' => isset($recipe['cook_time_minutes']) ? (string) $recipe['cook_time_minutes'] : '',
    'servings' => isset($recipe['servings']) ? (string) $recipe['servings'] : '',
    'difficulty' => $recipe['difficulty'] ?? 'Easy',
    'hero_image_url' => isset($recipe['hero_image']) && is_string($recipe['hero_image']) ? (string) $recipe['hero_image'] : '',
    'thumbnail_image_url' => isset($recipe['thumbnail_image']) && is_string($recipe['thumbnail_image']) ? (string) $recipe['thumbnail_image'] : '',
    'is_published' => !empty($recipe['is_published']) ? '1' : '0',
    'is_featured' => !empty($recipe['is_featured']) ? '1' : '0',

    // tips form as newline list
    'tips' => isset($recipe['tips']) ? recipe_to_multiline_json_like((string) $recipe['tips'], []) : '',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'short_description' => trim((string) ($_POST['short_description'] ?? '')),
        'cook_time_minutes' => trim((string) ($_POST['cook_time_minutes'] ?? '0')),
        'servings' => trim((string) ($_POST['servings'] ?? '0')),
        'difficulty' => trim((string) ($_POST['difficulty'] ?? 'Easy')),
        'hero_image_url' => trim((string) ($_POST['hero_image_url'] ?? '')),
        'thumbnail_image_url' => trim((string) ($_POST['thumbnail_image_url'] ?? '')),
        'is_published' => (string) ($_POST['is_published'] ?? '0'),
        'is_featured' => (string) ($_POST['is_featured'] ?? '0'),
        'tips' => (string) ($_POST['tips'] ?? ''),
    ];

    $postedIngredients = $_POST['ingredients'] ?? [];
    $postedSteps = $_POST['steps'] ?? [];
    $ingredients = is_array($postedIngredients) ? array_values($postedIngredients) : [];
    $steps = is_array($postedSteps) ? array_values($postedSteps) : [];

    try {
        if ($formData['name'] === '' || $formData['slug'] === '' || $formData['category'] === '' || $formData['short_description'] === '') {
            throw new RuntimeException('Name, slug, category, and short description are required.');
        }

        // slug uniqueness check exactly like DB unique constraint
        if ($editing) {
            $stmtDup = get_db()->prepare('SELECT id FROM recipes WHERE slug = :slug AND id <> :id LIMIT 1');
            $stmtDup->execute(['slug' => $formData['slug'], 'id' => $id]);
        } else {
            $stmtDup = get_db()->prepare('SELECT id FROM recipes WHERE slug = :slug LIMIT 1');
            $stmtDup->execute(['slug' => $formData['slug']]);
        }
        $dup = $stmtDup->fetch();
        if ($dup) {
            throw new RuntimeException('Recipe slug already exists');
        }

        // Tips: store as JSON array if multiple lines; fallback to null
        $tipsLines = normalize_multiline_list($formData['tips'] ?? '');
        $tipsJson = !empty($tipsLines) ? encode_json_value($tipsLines) : null;

        // Images handling (upload takes priority; otherwise external URL)
        $heroImagePath = $formData['hero_image_url'] !== '' ? $formData['hero_image_url'] : ($recipe['hero_image'] ?? null);
        $thumbnailImagePath = $formData['thumbnail_image_url'] !== '' ? $formData['thumbnail_image_url'] : ($recipe['thumbnail_image'] ?? null);

        $newHero = save_uploaded_image($_FILES['hero_image'] ?? []);
        if ($newHero) {
            if (!empty($recipe['hero_image']) && $heroImagePath !== $newHero) {
                delete_local_upload($recipe['hero_image']);
            }
            $heroImagePath = $newHero;
        } elseif (($recipe['hero_image'] ?? null) && $heroImagePath !== ($recipe['hero_image'] ?? null)) {
            delete_local_upload($recipe['hero_image']);
        }

        $newThumb = save_uploaded_image($_FILES['thumbnail_image'] ?? []);
        if ($newThumb) {
            if (!empty($recipe['thumbnail_image']) && $thumbnailImagePath !== $newThumb) {
                delete_local_upload($recipe['thumbnail_image']);
            }
            $thumbnailImagePath = $newThumb;
        } elseif (($recipe['thumbnail_image'] ?? null) && $thumbnailImagePath !== ($recipe['thumbnail_image'] ?? null)) {
            delete_local_upload($recipe['thumbnail_image']);
        }

        $cookTime = (int) ($formData['cook_time_minutes'] !== '' ? $formData['cook_time_minutes'] : 0);
        $servings = (int) ($formData['servings'] !== '' ? $formData['servings'] : 0);

        $pdo = get_db();
        $pdo->beginTransaction();

        if ($editing) {
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
                'name' => $formData['name'],
                'slug' => $formData['slug'],
                'category' => $formData['category'],
                'short_description' => $formData['short_description'],
                'hero_image' => $heroImagePath,
                'thumbnail_image' => $thumbnailImagePath,
                'cook_time_minutes' => $cookTime,
                'servings' => $servings,
                'difficulty' => $formData['difficulty'] !== '' ? $formData['difficulty'] : 'Easy',
                'tips' => $tipsJson,
                'is_featured' => (int) ($formData['is_featured'] === '1'),
                'is_published' => (int) ($formData['is_published'] === '1'),
            ]);

            $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :recipe_id')->execute(['recipe_id' => $id]);
            $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id = :recipe_id')->execute(['recipe_id' => $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO recipes (name, slug, category, short_description, hero_image, thumbnail_image, cook_time_minutes, servings, difficulty, tips, is_featured, is_published)
                 VALUES (:name, :slug, :category, :short_description, :hero_image, :thumbnail_image, :cook_time_minutes, :servings, :difficulty, :tips, :is_featured, :is_published)'
            );

            $stmt->execute([
                'name' => $formData['name'],
                'slug' => $formData['slug'],
                'category' => $formData['category'],
                'short_description' => $formData['short_description'],
                'hero_image' => $heroImagePath,
                'thumbnail_image' => $thumbnailImagePath,
                'cook_time_minutes' => $cookTime,
                'servings' => $servings,
                'difficulty' => $formData['difficulty'] !== '' ? $formData['difficulty'] : 'Easy',
                'tips' => $tipsJson,
                'is_featured' => (int) ($formData['is_featured'] === '1'),
                'is_published' => (int) ($formData['is_published'] === '1'),
            ]);

            $id = (int) $pdo->lastInsertId();
        }

        // Ingredients + steps insert (dynamic arrays)
        $stmtIng = $pdo->prepare(
            'INSERT INTO recipe_ingredients (recipe_id, ingredient_order, ingredient_text)
             VALUES (:recipe_id, :ingredient_order, :ingredient_text)'
        );

        $order = 1;
        foreach ($ingredients as $line) {
            $txt = trim((string) $line);
            if ($txt === '') continue;
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
        foreach ($steps as $line) {
            $txt = trim((string) $line);
            if ($txt === '') continue;
            $stmtSteps->execute([
                'recipe_id' => $id,
                'step_order' => $stepOrder,
                'step_text' => $txt,
            ]);
            $stepOrder++;
        }

        $pdo->commit();

        set_flash('success', $editing ? 'Recipe updated successfully.' : 'Recipe added successfully.');
        redirect('/HIRA/admin/recipes.php');
    } catch (Throwable $exception) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $exception->getMessage();
    }
}

render_admin_header($editing ? 'Edit Recipe' : 'Add Recipe', 'recipes.php');
?>
<section class="form-card">
  <div style="margin-bottom:18px;">
    <p class="brand-kicker" style="color:#f56c1b;">Recipe Management</p>
    <h3><?php echo $editing ? 'Edit Recipe' : 'Create Recipe'; ?></h3>
  </div>

  <?php if ($error !== ''): ?>
    <div class="flash error"><?php echo e($error); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php endif; ?>

    <div class="form-grid">
      <div class="field">
        <label for="name">Recipe Name</label>
        <input id="name" type="text" name="name" value="<?php echo e($formData['name']); ?>" required>
      </div>

      <div class="field">
        <label for="slug">Slug</label>
        <input id="slug" type="text" name="slug" value="<?php echo e($formData['slug']); ?>" placeholder="kanda-poha" required>
      </div>

      <div class="field">
        <label for="category">Category</label>
        <input id="category" type="text" name="category" value="<?php echo e($formData['category']); ?>" placeholder="Poha, Sabudana, Snacks" required>
      </div>

      <div class="field full">
        <label for="short_description">Short Description</label>
        <textarea id="short_description" name="short_description" required><?php echo e($formData['short_description']); ?></textarea>
      </div>

      <div class="field">
        <label for="cook_time_minutes">Cook Time (minutes)</label>
        <input id="cook_time_minutes" type="number" min="0" name="cook_time_minutes" value="<?php echo e($formData['cook_time_minutes']); ?>">
      </div>

      <div class="field">
        <label for="servings">Servings</label>
        <input id="servings" type="number" min="0" name="servings" value="<?php echo e($formData['servings']); ?>">
      </div>

      <div class="field">
        <label for="difficulty">Difficulty</label>
        <input id="difficulty" type="text" name="difficulty" value="<?php echo e($formData['difficulty']); ?>" placeholder="Easy, Medium, Hard">
      </div>

      <div class="field full">
        <label for="hero_image_url">Hero Image URL</label>
        <input id="hero_image_url" type="text" name="hero_image_url" value="<?php echo e($formData['hero_image_url']); ?>" placeholder="https://example.com/hero.jpg or /uploads/file.png">
        <p class="hint">Paste an external URL or keep an uploaded local path. Upload takes priority.</p>
      </div>

      <div class="field full">
        <label for="thumbnail_image_url">Thumbnail Image URL</label>
        <input id="thumbnail_image_url" type="text" name="thumbnail_image_url" value="<?php echo e($formData['thumbnail_image_url']); ?>" placeholder="https://example.com/thumb.jpg or /uploads/file.png">
      </div>

      <div class="field full">
        <label for="hero_image">Hero Image Upload</label>
        <input class="file-input" id="hero_image" type="file" name="hero_image" accept="image/*">
      </div>

      <div class="field full">
        <label for="thumbnail_image">Thumbnail Image Upload</label>
        <input class="file-input" id="thumbnail_image" type="file" name="thumbnail_image" accept="image/*">
      </div>

      <div class="field">
        <label for="is_published">Publish</label>
        <select id="is_published" name="is_published">
          <option value="1" <?php echo $formData['is_published'] === '1' ? 'selected' : ''; ?>>Published</option>
          <option value="0" <?php echo $formData['is_published'] === '0' ? 'selected' : ''; ?>>Unpublished</option>
        </select>
      </div>

      <div class="field">
        <label for="is_featured">Featured</label>
        <select id="is_featured" name="is_featured">
          <option value="1" <?php echo $formData['is_featured'] === '1' ? 'selected' : ''; ?>>Featured</option>
          <option value="0" <?php echo $formData['is_featured'] === '0' ? 'selected' : ''; ?>>Not Featured</option>
        </select>
      </div>

      <div class="field full">
        <label for="tips">Tips (one per line)</label>
        <textarea id="tips" name="tips" rows="4" placeholder="Tip 1&#10;Tip 2"><?php echo e($formData['tips']); ?></textarea>
      </div>
    </div>

    <div style="margin-top:18px;">
      <div class="hint" style="margin-bottom:10px;">Ingredients (add/remove rows)</div>

      <div id="ingredients-container" class="dynamic-list">
        <?php if (!empty($ingredients)): ?>
          <?php foreach ($ingredients as $idx => $ing): ?>
            <div class="dynamic-row">
              <input type="text" name="ingredients[]" value="<?php echo e($ing); ?>" placeholder="Ingredient" required>
              <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="dynamic-row">
            <input type="text" name="ingredients[]" value="" placeholder="Ingredient" required>
            <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
          </div>
        <?php endif; ?>
      </div>

      <div class="actions" style="margin-top:10px;">
        <button type="button" onclick="addIngredientRow()">Add Ingredient</button>
      </div>
    </div>

    <div style="margin-top:18px;">
      <div class="hint" style="margin-bottom:10px;">Steps (add/remove rows)</div>

      <div id="steps-container" class="dynamic-list">
        <?php if (!empty($steps)): ?>
          <?php foreach ($steps as $idx => $step): ?>
            <div class="dynamic-row">
              <textarea name="steps[]" rows="3" placeholder="Step" required><?php echo e($step); ?></textarea>
              <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="dynamic-row">
            <textarea name="steps[]" rows="3" placeholder="Step" required></textarea>
            <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
          </div>
        <?php endif; ?>
      </div>

      <div class="actions" style="margin-top:10px;">
        <button type="button" onclick="addStepRow()">Add Step</button>
      </div>
    </div>

    <div class="preview-box" style="margin-top:18px;">
      <img id="hero-preview" src="<?php echo e($formData['hero_image_url'] !== '' ? $formData['hero_image_url'] : ($recipe['hero_image'] ?? 'https://via.placeholder.com/160x160?text=Preview'))); ?>" alt="Hero Preview">
      <div>
        <strong>Hero Image Preview</strong>
        <p class="hint">A live preview appears here before saving so the admin can confirm the selected image.</p>
      </div>
    </div>

    <div class="preview-box" style="margin-top:18px;">
      <img id="thumb-preview" src="<?php echo e($formData['thumbnail_image_url'] !== '' ? $formData['thumbnail_image_url'] : ($recipe['thumbnail_image'] ?? 'https://via.placeholder.com/160x160?text=Preview'))); ?>" alt="Thumbnail Preview">
      <div>
        <strong>Thumbnail Image Preview</strong>
        <p class="hint">Upload takes priority; URL is used if no upload selected.</p>
      </div>
    </div>

    <div class="actions" style="margin-top:18px;">
      <button type="submit"><?php echo $editing ? 'Update Recipe' : 'Save Recipe'; ?></button>
      <a class="btn secondary" href="/HIRA/admin/recipes.php">Cancel</a>
    </div>
  </form>
</section>

<style>
  .dynamic-list { display:flex; flex-direction:column; gap:10px; }
  .dynamic-row { display:flex; gap:10px; align-items:flex-start; }
  .dynamic-row input, .dynamic-row textarea { width:100%; }
  .dynamic-row .danger { background:#ff3b30; color:white; padding:8px 12px; border-radius:10px; border:none; cursor:pointer; }
  .dynamic-row .danger:hover { filter:brightness(0.95); }
</style>

<script>
  function removeDynamicRow(btn) {
    const row = btn.closest('.dynamic-row');
    if (row) row.remove();
  }

  function addIngredientRow() {
    const container = document.getElementById('ingredients-container');
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `
      <input type="text" name="ingredients[]" value="" placeholder="Ingredient" required>
      <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
    `;
    container.appendChild(row);
  }

  function addStepRow() {
    const container = document.getElementById('steps-container');
    const row = document.createElement('div');
    row.className = 'dynamic-row';
    row.innerHTML = `
      <textarea name="steps[]" rows="3" placeholder="Step" required></textarea>
      <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
    `;
    container.appendChild(row);
  }

  const heroInput = document.getElementById('hero_image');
  const heroUrlInput = document.getElementById('hero_image_url');
  const heroPreview = document.getElementById('hero-preview');

  if (heroInput && heroPreview) {
    heroInput.addEventListener('change', function (event) {
      const file = event.target.files && event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (loadEvent) {
        heroPreview.src = loadEvent.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  if (heroUrlInput && heroPreview) {
    heroUrlInput.addEventListener('input', function (event) {
      const value = event.target.value.trim();
      if (value !== '') heroPreview.src = value;
    });
  }

  const thumbInput = document.getElementById('thumbnail_image');
  const thumbUrlInput = document.getElementById('thumbnail_image_url');
  const thumbPreview = document.getElementById('thumb-preview');

  if (thumbInput && thumbPreview) {
    thumbInput.addEventListener('change', function (event) {
      const file = event.target.files && event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (loadEvent) {
        thumbPreview.src = loadEvent.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  if (thumbUrlInput && thumbPreview) {
    thumbUrlInput.addEventListener('input', function (event) {
      const value = event.target.value.trim();
      if (value !== '') thumbPreview.src = value;
    });
  }
</script>

<?php
render_admin_footer();
?>
