<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_admin_login();

function cms_asset_preview(?string $value): string
{
    return asset_url($value) ?? 'https://via.placeholder.com/320x220?text=Preview';
}

function preview_target(string $name): string
{
    return 'preview-' . preg_replace('/[^a-z0-9]+/i', '-', $name);
}

function resolve_media_input(?string $currentValue, string $urlKey, string $fileKey): string
{
    $urlValue = trim((string) ($_POST[$urlKey] ?? ''));
    $nextValue = $urlValue !== '' ? $urlValue : (string) $currentValue;
    $uploadedValue = save_uploaded_image($_FILES[$fileKey] ?? []);

    if ($uploadedValue) {
        if ($currentValue && $currentValue !== $uploadedValue) {
            delete_local_upload($currentValue);
        }

        return $uploadedValue;
    }

    if ($urlValue !== '' && $currentValue && $urlValue !== $currentValue) {
        delete_local_upload($currentValue);
    }

    return $nextValue;
}

$home = get_page_content('home');
$about = get_page_content('about');
$contact = get_page_content('contact');
$error = '';

// ===== Recipes admin integration (inside CMS Content) =====
$recipesQueryStatus = 'all';
$recipesQueryCategory = '';
$recipes = [];
$recipesCategories = ['Poha', 'Sabudana', 'Snacks'];
$recipeEditingId = (int) ($_GET['recipe_id'] ?? ($_POST['recipe_id'] ?? 0));
$recipeFormError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = (string) ($_POST['section'] ?? '');

    try {
        // Recipes CRUD handled via POST section=recipes
        if ($section === 'recipes') {
            $recipeEditingId = (int) ($_POST['recipe_id'] ?? 0);
            $editing = $recipeEditingId > 0;

            $name = trim((string) ($_POST['r_name'] ?? ''));
            $slug = trim((string) ($_POST['r_slug'] ?? ''));
            $category = trim((string) ($_POST['r_category'] ?? ''));
            $shortDescription = trim((string) ($_POST['r_short_description'] ?? ''));
            $cookTimeMinutes = (int) (($_POST['r_cook_time_minutes'] ?? '') !== '' ? (string) $_POST['r_cook_time_minutes'] : 0);
            $servings = (int) (($_POST['r_servings'] ?? '') !== '' ? (string) $_POST['r_servings'] : 0);
            $difficulty = trim((string) ($_POST['r_difficulty'] ?? 'Easy')) ?: 'Easy';

            $is_published = (int) (($_POST['r_is_published'] ?? '0') === '1');
            $is_featured = (int) (($_POST['r_is_featured'] ?? '0') === '1');

            // related products (optional)
            $related_products_json = null;
            $relatedProducts = $_POST['r_related_products'] ?? [];
            if (is_array($relatedProducts)) {
                $ids = array_values(array_filter(array_map(static fn($v) => (int) $v, $relatedProducts), static fn($x) => $x > 0));
                $related_products_json = !empty($ids) ? encode_json_value($ids) : null;
            }

            // tips (one per line => JSON array)
            $tipsLines = normalize_multiline_list((string) ($_POST['r_tips'] ?? ''));
            $tipsJson = !empty($tipsLines) ? encode_json_value($tipsLines) : null;

            // Images via CMS helper logic (URL first, upload takes priority)
            $currentHero = '';
            $currentThumb = '';
            if ($editing) {
                $stmtCurrent = get_db()->prepare('SELECT hero_image, thumbnail_image FROM recipes WHERE id = :id LIMIT 1');
                $stmtCurrent->execute(['id' => $recipeEditingId]);
                $cur = $stmtCurrent->fetch();
                $currentHero = (string) ($cur['hero_image'] ?? '');
                $currentThumb = (string) ($cur['thumbnail_image'] ?? '');
            }

            $heroUrl = trim((string) ($_POST['r_hero_image_url'] ?? ''));
            $thumbUrl = trim((string) ($_POST['r_thumbnail_image_url'] ?? ''));

            $heroImagePath = $heroUrl !== '' ? $heroUrl : ($editing ? $currentHero : null);
            $thumbnailImagePath = $thumbUrl !== '' ? $thumbUrl : ($editing ? $currentThumb : null);

            $newHero = save_uploaded_image($_FILES['r_hero_image'] ?? []);
            if ($newHero) {
                if (!empty($currentHero) && $currentHero !== $newHero) delete_local_upload($currentHero);
                $heroImagePath = $newHero;
            }

            $newThumb = save_uploaded_image($_FILES['r_thumbnail_image'] ?? []);
            if ($newThumb) {
                if (!empty($currentThumb) && $currentThumb !== $newThumb) delete_local_upload($currentThumb);
                $thumbnailImagePath = $newThumb;
            }

            if ($name === '' || $slug === '' || $category === '' || $shortDescription === '') {
                throw new RuntimeException('Name, slug, category, and short description are required.');
            }

            // slug uniqueness
            if ($editing) {
                $stmtDup = get_db()->prepare('SELECT id FROM recipes WHERE slug = :slug AND id <> :id LIMIT 1');
                $stmtDup->execute(['slug' => $slug, 'id' => $recipeEditingId]);
            } else {
                $stmtDup = get_db()->prepare('SELECT id FROM recipes WHERE slug = :slug LIMIT 1');
                $stmtDup->execute(['slug' => $slug]);
            }
            if ($stmtDup->fetch()) {
                throw new RuntimeException('Recipe slug already exists');
            }

            $pdo = get_db();
            $pdo->beginTransaction();

            if ($editing) {
                $stmtUp = $pdo->prepare(
                    'UPDATE recipes
                     SET name=:name, slug=:slug, category=:category, short_description=:short_description,
                         hero_image=:hero_image, thumbnail_image=:thumbnail_image,
                         cook_time_minutes=:cook_time_minutes, servings=:servings, difficulty=:difficulty,
                         tips=:tips, related_products_json=:related_products_json,
                         is_featured=:is_featured, is_published=:is_published
                     WHERE id=:id'
                );
                $stmtUp->execute([
                    'id' => $recipeEditingId,
                    'name' => $name,
                    'slug' => $slug,
                    'category' => $category,
                    'short_description' => $shortDescription,
                    'hero_image' => $heroImagePath,
                    'thumbnail_image' => $thumbnailImagePath,
                    'cook_time_minutes' => $cookTimeMinutes,
                    'servings' => $servings,
                    'difficulty' => $difficulty,
                    'tips' => $tipsJson,
                    'related_products_json' => $related_products_json,
                    'is_featured' => $is_featured ? 1 : 0,
                    'is_published' => $is_published ? 1 : 0,
                ]);

                $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id = :rid')->execute(['rid' => $recipeEditingId]);
                $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id = :rid')->execute(['rid' => $recipeEditingId]);
            } else {
                $stmtIns = $pdo->prepare(
                    'INSERT INTO recipes
                     (name, slug, category, short_description, hero_image, thumbnail_image,
                      cook_time_minutes, servings, difficulty, tips, related_products_json,
                      is_featured, is_published)
                     VALUES
                     (:name, :slug, :category, :short_description, :hero_image, :thumbnail_image,
                      :cook_time_minutes, :servings, :difficulty, :tips, :related_products_json,
                      :is_featured, :is_published)'
                );
                $stmtIns->execute([
                    'name' => $name,
                    'slug' => $slug,
                    'category' => $category,
                    'short_description' => $shortDescription,
                    'hero_image' => $heroImagePath,
                    'thumbnail_image' => $thumbnailImagePath,
                    'cook_time_minutes' => $cookTimeMinutes,
                    'servings' => $servings,
                    'difficulty' => $difficulty,
                    'tips' => $tipsJson,
                    'related_products_json' => $related_products_json,
                    'is_featured' => $is_featured ? 1 : 0,
                    'is_published' => $is_published ? 1 : 0,
                ]);
                $recipeEditingId = (int) $pdo->lastInsertId();
            }

            $postedIngredients = $_POST['r_ingredients'] ?? [];
            $postedSteps = $_POST['r_steps'] ?? [];

            $ingredients = is_array($postedIngredients) ? array_values($postedIngredients) : [];
            $steps = is_array($postedSteps) ? array_values($postedSteps) : [];

            $stmtIng = $pdo->prepare(
                'INSERT INTO recipe_ingredients (recipe_id, ingredient_order, ingredient_text)
                 VALUES (:recipe_id, :ingredient_order, :ingredient_text)'
            );
            $order = 1;
            foreach ($ingredients as $line) {
                $txt = trim((string) $line);
                if ($txt === '') continue;
                $stmtIng->execute([
                    'recipe_id' => $recipeEditingId,
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
                    'recipe_id' => $recipeEditingId,
                    'step_order' => $stepOrder,
                    'step_text' => $txt,
                ]);
                $stepOrder++;
            }

            $pdo->commit();

            set_flash('success', $editing ? 'Recipe updated successfully.' : 'Recipe added successfully.');
            redirect('/HIRA/admin/cms.php');
        }

        // Bulk actions: delete recipe
        if ($section === 'recipes_delete') {
            $id = (int) ($_POST['recipe_id'] ?? 0);
            if ($id > 0) {
                $pdo = get_db();
                $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id=:id')->execute(['id' => $id]);
                $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id=:id')->execute(['id' => $id]);
                $pdo->prepare('DELETE FROM recipes WHERE id=:id')->execute(['id' => $id]);
            }
            set_flash('success', 'Recipe deleted successfully.');
            redirect('/HIRA/admin/cms.php');
        }

        // toggle publish/featured
        if ($section === 'recipes_toggle') {
            $id = (int) ($_POST['recipe_id'] ?? 0);
            $field = (string) ($_POST['toggle_field'] ?? '');
            $value = (int) ($_POST['toggle_value'] ?? 0);
            if ($id > 0 && in_array($field, ['is_published', 'is_featured'], true)) {
                get_db()->prepare("UPDATE recipes SET {$field}=:v WHERE id=:id")->execute(['v' => $value ? 1 : 0, 'id' => $id]);
                set_flash('success', 'Recipe updated.');
            }
            redirect('/HIRA/admin/cms.php');
        }

        if ($section === 'home') {
            $updatedHome = [
                'hero' => [
                    'eyebrow' => trim((string) ($_POST['home_hero_eyebrow'] ?? '')),
                    'title' => trim((string) ($_POST['home_hero_title'] ?? '')),
                    'subtitle' => trim((string) ($_POST['home_hero_subtitle'] ?? '')),
                    'media_type' => trim((string) ($_POST['home_hero_media_type'] ?? 'image')),
                    'media_url' => resolve_media_input($home['hero']['media_url'] ?? '', 'home_hero_media_url', 'home_hero_media_file'),
                    'cta_label' => trim((string) ($_POST['home_hero_cta_label'] ?? '')),
                    'cta_link' => trim((string) ($_POST['home_hero_cta_link'] ?? '')),
                    'secondary_label' => trim((string) ($_POST['home_hero_secondary_label'] ?? '')),
                    'secondary_link' => trim((string) ($_POST['home_hero_secondary_link'] ?? '')),
                ],
                'story' => [
                    'title' => trim((string) ($_POST['home_story_title'] ?? '')),
                    'intro' => trim((string) ($_POST['home_story_intro'] ?? '')),
                    'image' => resolve_media_input($home['story']['image'] ?? '', 'home_story_image', 'home_story_image_file'),
                    'steps' => [
                        [
                            'title' => trim((string) ($_POST['home_story_step1_title'] ?? '')),
                            'text' => trim((string) ($_POST['home_story_step1_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['home_story_step2_title'] ?? '')),
                            'text' => trim((string) ($_POST['home_story_step2_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['home_story_step3_title'] ?? '')),
                            'text' => trim((string) ($_POST['home_story_step3_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['home_story_step4_title'] ?? '')),
                            'text' => trim((string) ($_POST['home_story_step4_text'] ?? '')),
                        ],
                    ],
                ],
                'heritage' => [
                    'title' => trim((string) ($_POST['heritage_title'] ?? '')),
                    'text' => trim((string) ($_POST['heritage_text'] ?? '')),
                    'highlights' => [
                        [
                            'title' => trim((string) ($_POST['heritage1_title'] ?? '')),
                            'text' => trim((string) ($_POST['heritage1_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['heritage2_title'] ?? '')),
                            'text' => trim((string) ($_POST['heritage2_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['heritage3_title'] ?? '')),
                            'text' => trim((string) ($_POST['heritage3_text'] ?? '')),
                        ],
                    ],
                ],
                'showcase' => [
                    'eyebrow' => trim((string) ($_POST['showcase_eyebrow'] ?? '')),
                    'title' => trim((string) ($_POST['showcase_title'] ?? '')),
                    'text' => trim((string) ($_POST['showcase_text'] ?? '')),
                ],
                'categories' => [
                    [
                        'name' => trim((string) ($_POST['cat1_name'] ?? '')),
                        'description' => trim((string) ($_POST['cat1_desc'] ?? '')),
                        'image' => resolve_media_input($home['categories'][0]['image'] ?? '', 'cat1_image', 'cat1_image_file'),
                    ],
                    [
                        'name' => trim((string) ($_POST['cat2_name'] ?? '')),
                        'description' => trim((string) ($_POST['cat2_desc'] ?? '')),
                        'image' => resolve_media_input($home['categories'][1]['image'] ?? '', 'cat2_image', 'cat2_image_file'),
                    ],
                    [
                        'name' => trim((string) ($_POST['cat3_name'] ?? '')),
                        'description' => trim((string) ($_POST['cat3_desc'] ?? '')),
                        'image' => resolve_media_input($home['categories'][2]['image'] ?? '', 'cat3_image', 'cat3_image_file'),
                    ],
                ],
                'coming_soon' => [
                    'title' => trim((string) ($_POST['coming_title'] ?? '')),
                    'text' => trim((string) ($_POST['coming_text'] ?? '')),
                    'items' => [
                        [
                            'title' => trim((string) ($_POST['coming1_title'] ?? '')),
                            'image' => resolve_media_input($home['coming_soon']['items'][0]['image'] ?? '', 'coming1_image', 'coming1_image_file'),
                        ],
                        [
                            'title' => trim((string) ($_POST['coming2_title'] ?? '')),
                            'image' => resolve_media_input($home['coming_soon']['items'][1]['image'] ?? '', 'coming2_image', 'coming2_image_file'),
                        ],
                        [
                            'title' => trim((string) ($_POST['coming3_title'] ?? '')),
                            'image' => resolve_media_input($home['coming_soon']['items'][2]['image'] ?? '', 'coming3_image', 'coming3_image_file'),
                        ],
                    ],
                ],
                'trust' => [
                    'title' => trim((string) ($_POST['trust_title'] ?? '')),
                    'text' => trim((string) ($_POST['trust_text'] ?? '')),
                    'items' => [
                        [
                            'title' => trim((string) ($_POST['trust1_title'] ?? '')),
                            'text' => trim((string) ($_POST['trust1_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['trust2_title'] ?? '')),
                            'text' => trim((string) ($_POST['trust2_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['trust3_title'] ?? '')),
                            'text' => trim((string) ($_POST['trust3_text'] ?? '')),
                        ],
                        [
                            'title' => trim((string) ($_POST['trust4_title'] ?? '')),
                            'text' => trim((string) ($_POST['trust4_text'] ?? '')),
                        ],
                    ],
                ],
                'cta' => [
                    'title' => trim((string) ($_POST['cta_title'] ?? '')),
                    'text' => trim((string) ($_POST['cta_text'] ?? '')),
                    'button_label' => trim((string) ($_POST['cta_button_label'] ?? '')),
                    'button_link' => trim((string) ($_POST['cta_button_link'] ?? '')),
                ],
            ];

            update_page_content('home', $updatedHome);
            set_flash('success', 'Homepage content updated successfully.');
            redirect('/HIRA/admin/cms.php');
        }

        if ($section === 'about') {
            $updatedAbout = [
                'hero_title' => trim((string) ($_POST['about_hero_title'] ?? '')),
                'hero_text' => trim((string) ($_POST['about_hero_text'] ?? '')),
                'hero_image' => resolve_media_input($about['hero_image'] ?? '', 'about_hero_image', 'about_hero_image_file'),
                'sections' => [
                    ['title' => 'How It Started', 'text' => trim((string) ($_POST['about_how_started'] ?? ''))],
                    ['title' => 'Vision', 'text' => trim((string) ($_POST['about_vision'] ?? ''))],
                    ['title' => 'Mission', 'text' => trim((string) ($_POST['about_mission'] ?? ''))],
                    ['title' => 'Objective', 'text' => trim((string) ($_POST['about_objective'] ?? ''))],
                    ['title' => 'Values', 'text' => trim((string) ($_POST['about_values'] ?? ''))],
                ],
                'timeline' => [
                    [
                        'year' => trim((string) ($_POST['timeline1_year'] ?? '')),
                        'title' => trim((string) ($_POST['timeline1_title'] ?? '')),
                        'text' => trim((string) ($_POST['timeline1_text'] ?? '')),
                    ],
                    [
                        'year' => trim((string) ($_POST['timeline2_year'] ?? '')),
                        'title' => trim((string) ($_POST['timeline2_title'] ?? '')),
                        'text' => trim((string) ($_POST['timeline2_text'] ?? '')),
                    ],
                    [
                        'year' => trim((string) ($_POST['timeline3_year'] ?? '')),
                        'title' => trim((string) ($_POST['timeline3_title'] ?? '')),
                        'text' => trim((string) ($_POST['timeline3_text'] ?? '')),
                    ],
                ],
            ];

            update_page_content('about', $updatedAbout);
            set_flash('success', 'About page content updated successfully.');
            redirect('/HIRA/admin/cms.php');
        }

        if ($section === 'contact') {
            $updatedContact = [
                'title' => trim((string) ($_POST['contact_title'] ?? '')),
                'subtitle' => trim((string) ($_POST['contact_subtitle'] ?? '')),
                'hero_image' => resolve_media_input($contact['hero_image'] ?? '', 'contact_hero_image', 'contact_hero_image_file'),
                'address' => trim((string) ($_POST['contact_address'] ?? '')),
                'email' => trim((string) ($_POST['contact_email'] ?? '')),
                'phone' => trim((string) ($_POST['contact_phone'] ?? '')),
                'map_embed' => trim((string) ($_POST['contact_map_embed'] ?? '')),
            ];

            update_page_content('contact', $updatedContact);
            set_flash('success', 'Contact details updated successfully.');
            redirect('/HIRA/admin/cms.php');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$home = get_page_content('home');
$about = get_page_content('about');
$contact = get_page_content('contact');

render_admin_header('CMS Content', 'cms.php');
?>
<?php if ($error !== ''): ?>
  <div class="flash error"><?php echo e($error); ?></div>
<?php endif; ?>

<div style="margin: 0 0 18px;">
  <p class="brand-kicker" style="color: rgba(249, 115, 22, 0.95); margin: 0; text-transform: uppercase; font-size: 12px; letter-spacing: 0.18em;">
    Dashboard > CMS > <span id="cms-breadcrumb-section">Home</span>
  </p>
</div>

<div style="display:flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap;">
  <button type="button" class="btn secondary" data-cms-tab-btn="home" aria-pressed="true">Home</button>
  <button type="button" class="btn secondary" data-cms-tab-btn="about" aria-pressed="false">About</button>
  <button type="button" class="btn secondary" data-cms-tab-btn="contact" aria-pressed="false">Contact</button>
  <button type="button" class="btn secondary" data-cms-tab-btn="recipes" aria-pressed="false">Recipes</button>
</div>

<section class="grid" id="cms-tab-root">
  <article class="form-card" data-cms-tab-panel="home">
    <div style="margin-bottom:18px;">
      <p class="brand-kicker" style="color:#f97316;">Homepage Control</p>
      <h3>Hero, story, heritage, product section, coming soon, trust, and CTA</h3>
    </div>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="section" value="home">
      <div class="form-grid">
        <div class="field">
          <label>Hero Eyebrow</label>
          <input type="text" name="home_hero_eyebrow" value="<?php echo e($home['hero']['eyebrow'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Hero Title</label>
          <input type="text" name="home_hero_title" value="<?php echo e($home['hero']['title'] ?? ''); ?>">
        </div>
        <div class="field full">
          <label>Hero Subtitle</label>
          <textarea name="home_hero_subtitle"><?php echo e($home['hero']['subtitle'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Hero Media Type</label>
          <select name="home_hero_media_type">
            <option value="image" <?php echo ($home['hero']['media_type'] ?? 'image') === 'image' ? 'selected' : ''; ?>>Image</option>
            <option value="video" <?php echo ($home['hero']['media_type'] ?? '') === 'video' ? 'selected' : ''; ?>>Video URL</option>
          </select>
        </div>
        <div class="field">
          <label>Hero Media URL</label>
          <input type="url" name="home_hero_media_url" data-preview-target="<?php echo e(preview_target('home_hero_media')); ?>" value="<?php echo e($home['hero']['media_url'] ?? ''); ?>">
          <p class="hint">Paste an image URL or a video URL. If you also upload a file below, the upload will be used.</p>
        </div>
        <div class="field">
          <label>Hero Media Upload</label>
          <input class="file-input" type="file" name="home_hero_media_file" data-preview-target="<?php echo e(preview_target('home_hero_media')); ?>" accept="image/*">
          <p class="hint">Upload a hero image directly from your computer.</p>
        </div>
        <div class="field">
          <label>Current Hero Preview</label>
          <div class="image-preview-card">
            <img id="<?php echo e(preview_target('home_hero_media')); ?>" src="<?php echo e(cms_asset_preview($home['hero']['media_url'] ?? '')); ?>" alt="Hero preview">
          </div>
        </div>
        <div class="field">
          <label>Primary CTA Label</label>
          <input type="text" name="home_hero_cta_label" value="<?php echo e($home['hero']['cta_label'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Primary CTA Link</label>
          <input type="text" name="home_hero_cta_link" value="<?php echo e($home['hero']['cta_link'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Secondary CTA Label</label>
          <input type="text" name="home_hero_secondary_label" value="<?php echo e($home['hero']['secondary_label'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Secondary CTA Link</label>
          <input type="text" name="home_hero_secondary_link" value="<?php echo e($home['hero']['secondary_link'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Story Title</label>
          <input type="text" name="home_story_title" value="<?php echo e($home['story']['title'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Story Image URL</label>
          <input type="url" name="home_story_image" data-preview-target="<?php echo e(preview_target('home_story_image')); ?>" value="<?php echo e($home['story']['image'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Story Image Upload</label>
          <input class="file-input" type="file" name="home_story_image_file" data-preview-target="<?php echo e(preview_target('home_story_image')); ?>" accept="image/*">
        </div>
        <div class="field">
          <label>Story Preview</label>
          <div class="image-preview-card">
            <img id="<?php echo e(preview_target('home_story_image')); ?>" src="<?php echo e(cms_asset_preview($home['story']['image'] ?? '')); ?>" alt="Story preview">
          </div>
        </div>
        <div class="field full">
          <label>Story Intro</label>
          <textarea name="home_story_intro"><?php echo e($home['story']['intro'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Products Section Eyebrow</label>
          <input type="text" name="showcase_eyebrow" value="<?php echo e($home['showcase']['eyebrow'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Products Section Title</label>
          <input type="text" name="showcase_title" value="<?php echo e($home['showcase']['title'] ?? ''); ?>">
        </div>
        <div class="field full">
          <label>Products Section Text</label>
          <textarea name="showcase_text"><?php echo e($home['showcase']['text'] ?? ''); ?></textarea>
        </div>
      </div>

      <div class="form-grid">
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="field">
            <label>Story Step <?php echo $i + 1; ?> Title</label>
            <input type="text" name="home_story_step<?php echo $i + 1; ?>_title" value="<?php echo e($home['story']['steps'][$i]['title'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Story Step <?php echo $i + 1; ?> Text</label>
            <textarea name="home_story_step<?php echo $i + 1; ?>_text"><?php echo e($home['story']['steps'][$i]['text'] ?? ''); ?></textarea>
          </div>
        <?php endfor; ?>
      </div>

      <div class="form-grid">
        <div class="field">
          <label>Heritage Title</label>
          <input type="text" name="heritage_title" value="<?php echo e($home['heritage']['title'] ?? ''); ?>">
        </div>
        <div class="field full">
          <label>Heritage Text</label>
          <textarea name="heritage_text"><?php echo e($home['heritage']['text'] ?? ''); ?></textarea>
        </div>
        <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="field">
            <label>Heritage Highlight <?php echo $i + 1; ?> Title</label>
            <input type="text" name="heritage<?php echo $i + 1; ?>_title" value="<?php echo e($home['heritage']['highlights'][$i]['title'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Heritage Highlight <?php echo $i + 1; ?> Text</label>
            <textarea name="heritage<?php echo $i + 1; ?>_text"><?php echo e($home['heritage']['highlights'][$i]['text'] ?? ''); ?></textarea>
          </div>
        <?php endfor; ?>
      </div>

      <div class="cms-media-grid">
        <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="media-editor-card">
            <p class="media-card-title">Category <?php echo $i + 1; ?></p>
            <div class="field">
              <label>Name</label>
              <input type="text" name="cat<?php echo $i + 1; ?>_name" value="<?php echo e($home['categories'][$i]['name'] ?? ''); ?>">
            </div>
            <div class="field">
              <label>Description</label>
              <textarea name="cat<?php echo $i + 1; ?>_desc"><?php echo e($home['categories'][$i]['description'] ?? ''); ?></textarea>
            </div>
            <div class="field">
              <label>Image URL</label>
              <input type="url" name="cat<?php echo $i + 1; ?>_image" data-preview-target="<?php echo e(preview_target('cat' . ($i + 1) . '_image')); ?>" value="<?php echo e($home['categories'][$i]['image'] ?? ''); ?>">
            </div>
            <div class="field">
              <label>Upload Image</label>
              <input class="file-input" type="file" name="cat<?php echo $i + 1; ?>_image_file" data-preview-target="<?php echo e(preview_target('cat' . ($i + 1) . '_image')); ?>" accept="image/*">
            </div>
            <div class="image-preview-card">
              <img id="<?php echo e(preview_target('cat' . ($i + 1) . '_image')); ?>" src="<?php echo e(cms_asset_preview($home['categories'][$i]['image'] ?? '')); ?>" alt="Category preview">
            </div>
          </div>
        <?php endfor; ?>
      </div>

      <div class="form-grid">
        <div class="field">
          <label>Coming Soon Title</label>
          <input type="text" name="coming_title" value="<?php echo e($home['coming_soon']['title'] ?? ''); ?>">
        </div>
        <div class="field full">
          <label>Coming Soon Text</label>
          <textarea name="coming_text"><?php echo e($home['coming_soon']['text'] ?? ''); ?></textarea>
        </div>
      </div>

      <div class="cms-media-grid">
        <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="media-editor-card">
            <p class="media-card-title">Coming Soon Card <?php echo $i + 1; ?></p>
            <div class="field">
              <label>Title</label>
              <input type="text" name="coming<?php echo $i + 1; ?>_title" value="<?php echo e($home['coming_soon']['items'][$i]['title'] ?? ''); ?>">
            </div>
            <div class="field">
              <label>Image URL</label>
              <input type="url" name="coming<?php echo $i + 1; ?>_image" data-preview-target="<?php echo e(preview_target('coming' . ($i + 1) . '_image')); ?>" value="<?php echo e($home['coming_soon']['items'][$i]['image'] ?? ''); ?>">
            </div>
            <div class="field">
              <label>Upload Image</label>
              <input class="file-input" type="file" name="coming<?php echo $i + 1; ?>_image_file" data-preview-target="<?php echo e(preview_target('coming' . ($i + 1) . '_image')); ?>" accept="image/*">
            </div>
            <div class="image-preview-card">
              <img id="<?php echo e(preview_target('coming' . ($i + 1) . '_image')); ?>" src="<?php echo e(cms_asset_preview($home['coming_soon']['items'][$i]['image'] ?? '')); ?>" alt="Coming soon preview">
            </div>
          </div>
        <?php endfor; ?>
      </div>

      <div class="form-grid">
        <div class="field">
          <label>Trust Section Title</label>
          <input type="text" name="trust_title" value="<?php echo e($home['trust']['title'] ?? ''); ?>">
        </div>
        <div class="field full">
          <label>Trust Section Text</label>
          <textarea name="trust_text"><?php echo e($home['trust']['text'] ?? ''); ?></textarea>
        </div>
        <?php for ($i = 0; $i < 4; $i++): ?>
          <div class="field">
            <label>Trust Badge <?php echo $i + 1; ?> Title</label>
            <input type="text" name="trust<?php echo $i + 1; ?>_title" value="<?php echo e($home['trust']['items'][$i]['title'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Trust Badge <?php echo $i + 1; ?> Text</label>
            <input type="text" name="trust<?php echo $i + 1; ?>_text" value="<?php echo e($home['trust']['items'][$i]['text'] ?? ''); ?>">
          </div>
        <?php endfor; ?>
      </div>

      <div class="form-grid">
        <div class="field">
          <label>CTA Title</label>
          <input type="text" name="cta_title" value="<?php echo e($home['cta']['title'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>CTA Button Label</label>
          <input type="text" name="cta_button_label" value="<?php echo e($home['cta']['button_label'] ?? ''); ?>">
        </div>
        <div class="field full">
          <label>CTA Text</label>
          <textarea name="cta_text"><?php echo e($home['cta']['text'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>CTA Button Link</label>
          <input type="text" name="cta_button_link" value="<?php echo e($home['cta']['button_link'] ?? ''); ?>">
        </div>
      </div>
      <button type="submit">Save Homepage</button>
    </form>
  </article>

  <article class="form-card" data-cms-tab-panel="about" style="display:none;">
    <div style="margin-bottom:18px;">
      <p class="brand-kicker" style="color:#166534;">About Page</p>
      <h3>Story Blocks, Hero Image, and Timeline</h3>
    </div>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="section" value="about">
      <div class="form-grid">
        <div class="field">
          <label>Hero Title</label>
          <input type="text" name="about_hero_title" value="<?php echo e($about['hero_title'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Hero Image URL</label>
          <input type="url" name="about_hero_image" data-preview-target="<?php echo e(preview_target('about_hero_image')); ?>" value="<?php echo e($about['hero_image'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Hero Image Upload</label>
          <input class="file-input" type="file" name="about_hero_image_file" data-preview-target="<?php echo e(preview_target('about_hero_image')); ?>" accept="image/*">
        </div>
        <div class="field">
          <label>Hero Preview</label>
          <div class="image-preview-card">
            <img id="<?php echo e(preview_target('about_hero_image')); ?>" src="<?php echo e(cms_asset_preview($about['hero_image'] ?? '')); ?>" alt="About hero preview">
          </div>
        </div>
        <div class="field full">
          <label>Hero Text</label>
          <textarea name="about_hero_text"><?php echo e($about['hero_text'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>How It Started</label>
          <textarea name="about_how_started"><?php echo e($about['sections'][0]['text'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Vision</label>
          <textarea name="about_vision"><?php echo e($about['sections'][1]['text'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Mission</label>
          <textarea name="about_mission"><?php echo e($about['sections'][2]['text'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Objective</label>
          <textarea name="about_objective"><?php echo e($about['sections'][3]['text'] ?? ''); ?></textarea>
        </div>
        <div class="field full">
          <label>Values</label>
          <textarea name="about_values"><?php echo e($about['sections'][4]['text'] ?? ''); ?></textarea>
        </div>
      </div>
      <div class="form-grid">
        <?php for ($i = 0; $i < 3; $i++): ?>
          <div class="field">
            <label>Timeline <?php echo $i + 1; ?> Year</label>
            <input type="text" name="timeline<?php echo $i + 1; ?>_year" value="<?php echo e($about['timeline'][$i]['year'] ?? ''); ?>">
          </div>
          <div class="field">
            <label>Timeline <?php echo $i + 1; ?> Title</label>
            <input type="text" name="timeline<?php echo $i + 1; ?>_title" value="<?php echo e($about['timeline'][$i]['title'] ?? ''); ?>">
          </div>
          <div class="field full">
            <label>Timeline <?php echo $i + 1; ?> Text</label>
            <textarea name="timeline<?php echo $i + 1; ?>_text"><?php echo e($about['timeline'][$i]['text'] ?? ''); ?></textarea>
          </div>
        <?php endfor; ?>
      </div>
      <button type="submit">Save About Page</button>
    </form>
  </article>

  <article class="form-card" data-cms-tab-panel="contact" style="display:none;">
    <div style="margin-bottom:18px;">
      <p class="brand-kicker" style="color:#dc2626;">Contact Page</p>
      <h3>Editable Contact Details and Hero Image</h3>
    </div>
    <form method="post" enctype="multipart/form-data">
      <input type="hidden" name="section" value="contact">
      <div class="form-grid">
        <div class="field">
          <label>Section Title</label>
          <input type="text" name="contact_title" value="<?php echo e($contact['title'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Hero Image URL</label>
          <input type="url" name="contact_hero_image" data-preview-target="<?php echo e(preview_target('contact_hero_image')); ?>" value="<?php echo e($contact['hero_image'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Hero Image Upload</label>
          <input class="file-input" type="file" name="contact_hero_image_file" data-preview-target="<?php echo e(preview_target('contact_hero_image')); ?>" accept="image/*">
        </div>
        <div class="field">
          <label>Hero Preview</label>
          <div class="image-preview-card">
            <img id="<?php echo e(preview_target('contact_hero_image')); ?>" src="<?php echo e(cms_asset_preview($contact['hero_image'] ?? '')); ?>" alt="Contact hero preview">
          </div>
        </div>
        <div class="field full">
          <label>Subtitle</label>
          <textarea name="contact_subtitle"><?php echo e($contact['subtitle'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Address</label>
          <textarea name="contact_address"><?php echo e($contact['address'] ?? ''); ?></textarea>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="text" name="contact_email" value="<?php echo e($contact['email'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Phone</label>
          <input type="text" name="contact_phone" value="<?php echo e($contact['phone'] ?? ''); ?>">
        </div>
        <div class="field full">
          <label>Map Embed URL (optional)</label>
          <input type="url" name="contact_map_embed" value="<?php echo e($contact['map_embed'] ?? ''); ?>">
        </div>
      </div>
      <button type="submit">Save Contact Page</button>
    </form>
  </article>
  <article class="form-card" data-cms-tab-panel="recipes" style="display:none;">
    <div style="margin-bottom:18px;">
      <p class="brand-kicker" style="color:#f97316;">Recipes Management</p>
      <h3>Recipes (CRUD) in CMS</h3>
    </div>

    <?php
      // Load recipe list for UI (admin: all)
      $recipeListError = '';
      try {
          $stmtList = get_db()->prepare('SELECT id, name, slug, category, short_description, hero_image, thumbnail_image, cook_time_minutes, servings, difficulty, tips, is_featured, is_published FROM recipes ORDER BY is_featured DESC, is_published DESC, id DESC');
          $stmtList->execute();
          $recipeListRows = $stmtList->fetchAll();
      } catch (Throwable $e) {
          $recipeListRows = [];
          $recipeListError = $e->getMessage();
      }

      // If editing via query param, load current recipe fields + ingredients/steps
      $editingRecipe = null;
      $editingIngredients = [];
      $editingSteps = [];

      if ($recipeEditingId > 0) {
          $stmtEdit = get_db()->prepare('SELECT * FROM recipes WHERE id = :id LIMIT 1');
          $stmtEdit->execute(['id' => $recipeEditingId]);
          $editingRecipe = $stmtEdit->fetch();

          if ($editingRecipe) {
              $stmtIng = get_db()->prepare('SELECT ingredient_order, ingredient_text FROM recipe_ingredients WHERE recipe_id = :id ORDER BY ingredient_order ASC, id ASC');
              $stmtIng->execute(['id' => $recipeEditingId]);
              $rowsIng = $stmtIng->fetchAll();
              $editingIngredients = array_map(static fn(array $r): string => (string)$r['ingredient_text'], $rowsIng);

              $stmtSteps = get_db()->prepare('SELECT step_order, step_text FROM recipe_steps WHERE recipe_id = :id ORDER BY step_order ASC, id ASC');
              $stmtSteps->execute(['id' => $recipeEditingId]);
              $rowsSteps = $stmtSteps->fetchAll();
              $editingSteps = array_map(static fn(array $r): string => (string)$r['step_text'], $rowsSteps);
          }
      }

      $formVals = [
        'name' => $editingRecipe['name'] ?? '',
        'slug' => $editingRecipe['slug'] ?? '',
        'category' => $editingRecipe['category'] ?? '',
        'short_description' => $editingRecipe['short_description'] ?? '',
        'cook_time_minutes' => isset($editingRecipe['cook_time_minutes']) ? (string)$editingRecipe['cook_time_minutes'] : '',
        'servings' => isset($editingRecipe['servings']) ? (string)$editingRecipe['servings'] : '',
        'difficulty' => $editingRecipe['difficulty'] ?? 'Easy',
        'hero_image_url' => $editingRecipe['hero_image'] ?? '',
        'thumbnail_image_url' => $editingRecipe['thumbnail_image'] ?? '',
        'is_published' => !empty($editingRecipe['is_published']) ? '1' : '0',
        'is_featured' => !empty($editingRecipe['is_featured']) ? '1' : '0',
        'tips' => '',
      ];
      $tipsDecoded = decode_json_array((string)($editingRecipe['tips'] ?? ''));
      if (!empty($tipsDecoded) && is_array($tipsDecoded)) {
        $formVals['tips'] = implode(PHP_EOL, array_map(static fn($x): string => (string)$x, $tipsDecoded));
      } else {
        $tipsText = trim((string)($editingRecipe['tips'] ?? ''));
        $formVals['tips'] = $tipsText;
      }
    ?>

    <?php if ($recipeFormError !== '' ): ?>
      <div class="flash error"><?php echo e($recipeFormError); ?></div>
    <?php endif; ?>
    <?php if ($recipeListError !== '' ): ?>
      <div class="flash error"><?php echo e($recipeListError); ?></div>
    <?php endif; ?>

    <div style="display:grid; gap:18px;">
      <div class="table-card">
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap; margin-bottom:10px;">
          <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; width:100%;">
            <input type="hidden" name="recipe_id" value="<?php echo (int)$recipeEditingId; ?>">
            <div class="field" style="margin:0; flex:1;">
              <label style="display:block;">Search</label>
              <input type="text" name="q" value="<?php echo e((string)($_GET['q'] ?? '')); ?>" placeholder="Search by name/slug/category" style="width:100%;">
            </div>
            <div class="field" style="margin:0; width:220px;">
              <label style="display:block;">Category</label>
              <select name="cat" style="width:100%;">
                <option value="">All</option>
                <?php foreach ($recipesCategories as $c): ?>
                  <option value="<?php echo e($c); ?>" <?php echo (($_GET['cat'] ?? '') === $c) ? 'selected' : ''; ?>><?php echo e($c); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="actions" style="align-self:flex-end;">
              <button type="submit">Filter</button>
            </div>
          </form>
        </div>

        <table class="admin-table" style="width:100%; border-collapse:collapse;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Slug</th>
              <th>Category</th>
              <th>Publish</th>
              <th>Featured</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $q = trim((string)($_GET['q'] ?? ''));
              $catFilter = trim((string)($_GET['cat'] ?? ''));
              $rows = $recipeListRows ?? [];
              $filtered = array_filter($rows, static function ($r) use ($q, $catFilter) {
                  if ($catFilter !== '' && (string)$r['category'] !== $catFilter) return false;
                  if ($q === '') return true;
                  $hay = mb_strtolower((string)$r['name'] . ' ' . (string)$r['slug'] . ' ' . (string)$r['category']);
                  return mb_strpos($hay, mb_strtolower($q)) !== false;
              });

              foreach ($filtered as $r):
            ?>
              <tr>
                <td><?php echo (int)$r['id']; ?></td>
                <td><?php echo e((string)$r['name']); ?></td>
                <td><?php echo e((string)$r['slug']); ?></td>
                <td><?php echo e((string)$r['category']); ?></td>
                <td><?php echo (!empty($r['is_published'])) ? 'Yes' : 'No'; ?></td>
                <td><?php echo (!empty($r['is_featured'])) ? 'Yes' : 'No'; ?></td>
                <td>
                  <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <a class="btn secondary" href="/HIRA/admin/cms.php?section=recipes&recipe_id=<?php echo (int)$r['id']; ?>">Edit</a>
                    <form method="post" style="margin:0;">
                      <input type="hidden" name="section" value="recipes_delete">
                      <input type="hidden" name="recipe_id" value="<?php echo (int)$r['id']; ?>">
                      <button type="submit" class="btn danger" onclick="return confirm('Delete this recipe?');">Delete</button>
                    </form>

                    <form method="post" style="margin:0;">
                      <input type="hidden" name="section" value="recipes_toggle">
                      <input type="hidden" name="recipe_id" value="<?php echo (int)$r['id']; ?>">
                      <input type="hidden" name="toggle_field" value="is_published">
                      <input type="hidden" name="toggle_value" value="<?php echo !empty($r['is_published']) ? 0 : 1; ?>">
                      <button type="submit" class="btn secondary"><?php echo !empty($r['is_published']) ? 'Unpublish' : 'Publish'; ?></button>
                    </form>

                    <form method="post" style="margin:0;">
                      <input type="hidden" name="section" value="recipes_toggle">
                      <input type="hidden" name="recipe_id" value="<?php echo (int)$r['id']; ?>">
                      <input type="hidden" name="toggle_field" value="is_featured">
                      <input type="hidden" name="toggle_value" value="<?php echo !empty($r['is_featured']) ? 0 : 1; ?>">
                      <button type="submit" class="btn secondary"><?php echo !empty($r['is_featured']) ? 'Unfeature' : 'Feature'; ?></button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>

            <?php if (empty($filtered)): ?>
              <tr><td colspan="7" style="text-align:center; padding:14px;">No recipes found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="form-card">
        <div style="margin-bottom:10px; display:flex; gap:12px; flex-wrap:wrap; align-items:center;">
          <div>
            <p class="brand-kicker" style="color:#f56c1b;"><?php echo $recipeEditingId > 0 ? 'Edit Recipe' : 'Add Recipe'; ?></p>
            <h3><?php echo $recipeEditingId > 0 ? 'Update recipe' : 'Create recipe'; ?></h3>
          </div>
          <?php if ($recipeEditingId > 0): ?>
            <a class="btn secondary" href="/HIRA/admin/cms.php?section=recipes">+ Add New</a>
          <?php endif; ?>
        </div>

        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="section" value="recipes">
          <?php if ($recipeEditingId > 0): ?>
            <input type="hidden" name="recipe_id" value="<?php echo (int)$recipeEditingId; ?>">
          <?php endif; ?>

          <div class="form-grid">
            <div class="field">
              <label>Recipe Name</label>
              <input type="text" name="r_name" value="<?php echo e($formVals['name']); ?>" required>
            </div>

            <div class="field">
              <label>Slug</label>
              <input type="text" name="r_slug" value="<?php echo e($formVals['slug']); ?>" placeholder="kanda-poha" required>
            </div>

            <div class="field">
              <label>Category</label>
              <input type="text" name="r_category" value="<?php echo e($formVals['category']); ?>" placeholder="Poha, Sabudana, Snacks" required>
            </div>

            <div class="field full">
              <label>Short Description</label>
              <textarea name="r_short_description" required><?php echo e($formVals['short_description']); ?></textarea>
            </div>

            <div class="field">
              <label>Cook Time (minutes)</label>
              <input type="number" min="0" name="r_cook_time_minutes" value="<?php echo e($formVals['cook_time_minutes']); ?>">
            </div>

            <div class="field">
              <label>Servings</label>
              <input type="number" min="0" name="r_servings" value="<?php echo e($formVals['servings']); ?>">
            </div>

            <div class="field">
              <label>Difficulty</label>
              <input type="text" name="r_difficulty" value="<?php echo e($formVals['difficulty']); ?>" placeholder="Easy, Medium, Hard">
            </div>

            <div class="field full">
              <label>Hero Image URL</label>
              <input type="url" name="r_hero_image_url" value="<?php echo e($formVals['hero_image_url']); ?>" placeholder="https://example.com/hero.jpg">
            </div>

            <div class="field full">
              <label>Thumbnail Image URL</label>
              <input type="url" name="r_thumbnail_image_url" value="<?php echo e($formVals['thumbnail_image_url']); ?>" placeholder="https://example.com/thumb.jpg">
            </div>

            <div class="field full">
              <label>Hero Image Upload</label>
              <input class="file-input" type="file" name="r_hero_image" accept="image/*">
            </div>

            <div class="field full">
              <label>Thumbnail Image Upload</label>
              <input class="file-input" type="file" name="r_thumbnail_image" accept="image/*">
            </div>

            <div class="field">
              <label>Publish</label>
              <select name="r_is_published">
                <option value="1" <?php echo $formVals['is_published'] === '1' ? 'selected' : ''; ?>>Published</option>
                <option value="0" <?php echo $formVals['is_published'] === '0' ? 'selected' : ''; ?>>Unpublished</option>
              </select>
            </div>

            <div class="field">
              <label>Featured</label>
              <select name="r_is_featured">
                <option value="1" <?php echo $formVals['is_featured'] === '1' ? 'selected' : ''; ?>>Featured</option>
                <option value="0" <?php echo $formVals['is_featured'] === '0' ? 'selected' : ''; ?>>Not Featured</option>
              </select>
            </div>

            <div class="field full">
              <label>Tips (one per line)</label>
              <textarea name="r_tips" rows="4"><?php echo e((string)$formVals['tips']); ?></textarea>
            </div>

            <div class="field full">
              <label>Related Products (optional - product IDs, comma separated)</label>
              <input type="text" name="r_related_products" placeholder="e.g. 3,7,9" value="<?php echo e((string)($_GET['rel'] ?? '')); ?>">
            </div>

            <div style="margin-top:18px;">
              <div class="hint" style="margin-bottom:10px;">Ingredients (add/remove rows)</div>
              <div id="r-ingredients-container" class="dynamic-list">
                <?php
                  $r_in = $editingRecipe ? $editingIngredients : $editingIngredients;
                  $rIngredientsToRender = !empty($editingIngredients) ? $editingIngredients : [''];
                  foreach ($rIngredientsToRender as $ing):
                ?>
                  <div class="dynamic-row">
                    <input type="text" name="r_ingredients[]" value="<?php echo e((string)$ing); ?>" placeholder="Ingredient" required>
                    <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="actions" style="margin-top:10px;">
                <button type="button" onclick="addIngredientRow('r-ingredients-container')">Add Ingredient</button>
              </div>
            </div>

            <div style="margin-top:18px;">
              <div class="hint" style="margin-bottom:10px;">Steps (add/remove rows)</div>
              <div id="r-steps-container" class="dynamic-list">
                <?php
                  $rStepsToRender = !empty($editingSteps) ? $editingSteps : [''];
                  foreach ($rStepsToRender as $st):
                ?>
                  <div class="dynamic-row">
                    <textarea name="r_steps[]" rows="3" placeholder="Step" required><?php echo e((string)$st); ?></textarea>
                    <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="actions" style="margin-top:10px;">
                <button type="button" onclick="addStepRow('r-steps-container')">Add Step</button>
              </div>
            </div>

            <div class="actions" style="margin-top:18px;">
              <button type="submit"><?php echo $recipeEditingId > 0 ? 'Update Recipe' : 'Save Recipe'; ?></button>
              <a class="btn secondary" href="/HIRA/admin/cms.php">Cancel</a>
            </div> 

            

<?php
render_admin_footer();
?>
<script>
(() => {
  const root = document.getElementById('cms-tab-root');
  if (!root) return;

  const tabEls = root.querySelectorAll('[data-cms-tab], [data-cms-tab-btn]');
  const panelEls = root.querySelectorAll('[data-cms-tab-panel]');

  const getTabName = (el) => {
    return (el.getAttribute('data-cms-tab') || el.getAttribute('data-cms-tab-btn') || '').trim();
  };

  const clearActive = () => {
    tabEls.forEach(t => {
      // keep existing styles; only toggle common active patterns if they exist
      t.classList.remove('active');
      t.setAttribute('aria-pressed', 'false');
    });
    panelEls.forEach(p => {
      p.style.display = 'none';
    });
  };

  const showPanelFor = (name) => {
    const target = root.querySelector('[data-cms-tab-panel="' + CSS.escape(name) + '"]');
    if (!target) return;

    clearActive();
    // activate the matching tab
    tabEls.forEach(t => {
      if (getTabName(t) === name) {
        t.classList.add('active');
        t.setAttribute('aria-pressed', 'true');
      }
    });
    target.style.display = '';
  };

  // Click wiring
  tabEls.forEach(tab => {
    tab.addEventListener('click', (e) => {
      e.preventDefault();
      const name = getTabName(tab);
      if (!name) return;
      showPanelFor(name);
    });
  });
})();
</script>
