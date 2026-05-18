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

function normalize_related_product_ids(mixed $value): array
{
    if (is_array($value)) {
        $rawValues = $value;
    } else {
        $rawValues = preg_split('/[\s,]+/', trim((string) $value)) ?: [];
    }

    $ids = array_map(static fn(mixed $item): int => (int) $item, $rawValues);
    $ids = array_filter($ids, static fn(int $id): bool => $id > 0);

    return array_values(array_unique($ids));
}

function recipe_preview_url(string $slug): string
{
    $frontendBase = trim((string) getenv('HIRA_FRONTEND_URL'));

    if ($frontendBase === '') {
        $frontendBase = 'http://localhost:5173';
    }

    return rtrim($frontendBase, '/') . '/recipes/' . rawurlencode($slug);
}

$home = get_page_content('home');
$about = get_page_content('about');
$contact = get_page_content('contact');
$error = '';
$allowedCmsTabs = ['home', 'about', 'contact', 'recipes'];
$activeCmsTab = (string) ($_GET['section'] ?? 'home');

if (!in_array($activeCmsTab, $allowedCmsTabs, true)) {
    $activeCmsTab = 'home';
}

// ===== Recipes admin integration (inside CMS Content) =====
$recipesQueryStatus = 'all';
$recipesQueryCategory = '';
$recipes = [];
$recipesCategories = ['Poha', 'Sabudana', 'Snacks'];
$recipeEditingId = (int) ($_GET['recipe_id'] ?? ($_POST['recipe_id'] ?? 0));
$recipeFormError = '';
$lastPostedSection = '';

if ($recipeEditingId > 0) {
    $activeCmsTab = 'recipes';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = (string) ($_POST['section'] ?? '');
    $lastPostedSection = $section;

    if (in_array($section, $allowedCmsTabs, true)) {
        $activeCmsTab = $section;
    }

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
            $relatedProductIds = normalize_related_product_ids($_POST['r_related_products'] ?? []);
            $related_products_json = !empty($relatedProductIds) ? encode_json_value($relatedProductIds) : null;

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
            redirect('/HIRA/admin/cms.php?section=recipes');
        }

        // Bulk actions: delete recipe
        if ($section === 'recipes_delete') {
            $id = (int) ($_POST['recipe_id'] ?? 0);
            if ($id > 0) {
                $pdo = get_db();
                $stmtRecipe = $pdo->prepare('SELECT hero_image, thumbnail_image FROM recipes WHERE id = :id LIMIT 1');
                $stmtRecipe->execute(['id' => $id]);
                $recipeToDelete = $stmtRecipe->fetch();

                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM recipe_ingredients WHERE recipe_id=:id')->execute(['id' => $id]);
                $pdo->prepare('DELETE FROM recipe_steps WHERE recipe_id=:id')->execute(['id' => $id]);
                $pdo->prepare('DELETE FROM recipes WHERE id=:id')->execute(['id' => $id]);
                $pdo->commit();

                if ($recipeToDelete) {
                    delete_local_upload($recipeToDelete['hero_image'] ?? null);
                    delete_local_upload($recipeToDelete['thumbnail_image'] ?? null);
                }
            }
            set_flash('success', 'Recipe deleted successfully.');
            redirect('/HIRA/admin/cms.php?section=recipes');
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
            redirect('/HIRA/admin/cms.php?section=recipes');
        }

        if ($section === 'recipes_duplicate') {
            $id = (int) ($_POST['recipe_id'] ?? 0);

            if ($id <= 0) {
                throw new RuntimeException('Recipe not found.');
            }

            $pdo = get_db();
            $stmtRecipe = $pdo->prepare('SELECT * FROM recipes WHERE id = :id LIMIT 1');
            $stmtRecipe->execute(['id' => $id]);
            $sourceRecipe = $stmtRecipe->fetch();

            if (!$sourceRecipe) {
                throw new RuntimeException('Recipe not found.');
            }

            $stmtIngredients = $pdo->prepare(
                'SELECT ingredient_text FROM recipe_ingredients WHERE recipe_id = :recipe_id ORDER BY ingredient_order ASC, id ASC'
            );
            $stmtIngredients->execute(['recipe_id' => $id]);
            $sourceIngredients = $stmtIngredients->fetchAll();

            $stmtSteps = $pdo->prepare(
                'SELECT step_text FROM recipe_steps WHERE recipe_id = :recipe_id ORDER BY step_order ASC, id ASC'
            );
            $stmtSteps->execute(['recipe_id' => $id]);
            $sourceSteps = $stmtSteps->fetchAll();

            $baseName = trim((string) ($sourceRecipe['name'] ?? 'Recipe')) . ' Copy';
            $baseSlug = trim((string) ($sourceRecipe['slug'] ?? 'recipe')) . '-copy';
            $newSlug = $baseSlug;
            $suffix = 2;

            while (true) {
                $stmtDup = $pdo->prepare('SELECT id FROM recipes WHERE slug = :slug LIMIT 1');
                $stmtDup->execute(['slug' => $newSlug]);
                if (!$stmtDup->fetch()) {
                    break;
                }

                $newSlug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $pdo->beginTransaction();

            $stmtInsert = $pdo->prepare(
                'INSERT INTO recipes
                 (name, slug, category, short_description, hero_image, thumbnail_image,
                  cook_time_minutes, servings, difficulty, tips, related_products_json,
                  is_featured, is_published)
                 VALUES
                 (:name, :slug, :category, :short_description, :hero_image, :thumbnail_image,
                  :cook_time_minutes, :servings, :difficulty, :tips, :related_products_json,
                  :is_featured, :is_published)'
            );
            $stmtInsert->execute([
                'name' => $baseName,
                'slug' => $newSlug,
                'category' => (string) ($sourceRecipe['category'] ?? ''),
                'short_description' => (string) ($sourceRecipe['short_description'] ?? ''),
                'hero_image' => $sourceRecipe['hero_image'] ?? null,
                'thumbnail_image' => $sourceRecipe['thumbnail_image'] ?? null,
                'cook_time_minutes' => (int) ($sourceRecipe['cook_time_minutes'] ?? 0),
                'servings' => (int) ($sourceRecipe['servings'] ?? 0),
                'difficulty' => (string) ($sourceRecipe['difficulty'] ?? 'Easy'),
                'tips' => $sourceRecipe['tips'] ?? null,
                'related_products_json' => $sourceRecipe['related_products_json'] ?? null,
                'is_featured' => 0,
                'is_published' => 0,
            ]);

            $newRecipeId = (int) $pdo->lastInsertId();

            $stmtInsertIngredient = $pdo->prepare(
                'INSERT INTO recipe_ingredients (recipe_id, ingredient_order, ingredient_text)
                 VALUES (:recipe_id, :ingredient_order, :ingredient_text)'
            );
            $ingredientOrder = 1;
            foreach ($sourceIngredients as $ingredientRow) {
                $stmtInsertIngredient->execute([
                    'recipe_id' => $newRecipeId,
                    'ingredient_order' => $ingredientOrder,
                    'ingredient_text' => (string) ($ingredientRow['ingredient_text'] ?? ''),
                ]);
                $ingredientOrder++;
            }

            $stmtInsertStep = $pdo->prepare(
                'INSERT INTO recipe_steps (recipe_id, step_order, step_text)
                 VALUES (:recipe_id, :step_order, :step_text)'
            );
            $stepOrder = 1;
            foreach ($sourceSteps as $stepRow) {
                $stmtInsertStep->execute([
                    'recipe_id' => $newRecipeId,
                    'step_order' => $stepOrder,
                    'step_text' => (string) ($stepRow['step_text'] ?? ''),
                ]);
                $stepOrder++;
            }

            $pdo->commit();

            set_flash('success', 'Recipe duplicated as a draft copy.');
            redirect('/HIRA/admin/cms.php?section=recipes&recipe_id=' . $newRecipeId);
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
        if (str_starts_with($section, 'recipes')) {
            $recipeFormError = $exception->getMessage();
            $activeCmsTab = 'recipes';
        } else {
            $error = $exception->getMessage();
        }
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

<div style="display:flex; gap: 10px; margin-bottom: 18px; flex-wrap: wrap;" data-cms-tab-btn-row>
  <button type="button" class="btn secondary" data-cms-tab="home" data-cms-tab-btn="home" aria-pressed="false">Home</button>
  <button type="button" class="btn secondary" data-cms-tab="about" data-cms-tab-btn="about" aria-pressed="false">About</button>
  <button type="button" class="btn secondary" data-cms-tab="contact" data-cms-tab-btn="contact" aria-pressed="false">Contact</button>
  <button type="button" class="btn secondary" data-cms-tab="recipes" data-cms-tab-btn="recipes" aria-pressed="false">Recipes</button>
</div>

<section class="grid" id="cms-tab-root" data-cms-tab-root data-initial-cms-tab="<?php echo e($activeCmsTab); ?>">
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
      <h3>Client-friendly Recipes workflow inside CMS Content</h3>
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

      $productOptions = fetch_products(true);
      $recipesCategories = array_values(array_unique(array_merge(
          $recipesCategories,
          array_values(array_filter(array_map(static fn(array $row): string => trim((string) ($row['category'] ?? '')), $recipeListRows)))
      )));
      sort($recipesCategories);

      // If editing via query param, load current recipe fields + ingredients/steps
      $editingRecipe = null;
      $editingIngredients = [];
      $editingSteps = [];
      $selectedRelatedProductIds = [];

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

              $selectedRelatedProductIds = decode_json_array((string) ($editingRecipe['related_products_json'] ?? ''));
              $selectedRelatedProductIds = array_values(array_filter(array_map(static fn($value): int => (int) $value, $selectedRelatedProductIds), static fn(int $id): bool => $id > 0));
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
        'related_products' => $selectedRelatedProductIds,
      ];
      $tipsDecoded = decode_json_array((string)($editingRecipe['tips'] ?? ''));
      if (!empty($tipsDecoded) && is_array($tipsDecoded)) {
        $formVals['tips'] = implode(PHP_EOL, array_map(static fn($x): string => (string)$x, $tipsDecoded));
      } else {
        $tipsText = trim((string)($editingRecipe['tips'] ?? ''));
        $formVals['tips'] = $tipsText;
      }

      if ($lastPostedSection === 'recipes' && $recipeFormError !== '') {
        $formVals = [
          'name' => trim((string) ($_POST['r_name'] ?? '')),
          'slug' => trim((string) ($_POST['r_slug'] ?? '')),
          'category' => trim((string) ($_POST['r_category'] ?? '')),
          'short_description' => trim((string) ($_POST['r_short_description'] ?? '')),
          'cook_time_minutes' => trim((string) ($_POST['r_cook_time_minutes'] ?? '')),
          'servings' => trim((string) ($_POST['r_servings'] ?? '')),
          'difficulty' => trim((string) ($_POST['r_difficulty'] ?? 'Easy')),
          'hero_image_url' => trim((string) ($_POST['r_hero_image_url'] ?? '')),
          'thumbnail_image_url' => trim((string) ($_POST['r_thumbnail_image_url'] ?? '')),
          'is_published' => (string) ($_POST['r_is_published'] ?? '0'),
          'is_featured' => (string) ($_POST['r_is_featured'] ?? '0'),
          'tips' => (string) ($_POST['r_tips'] ?? ''),
          'related_products' => normalize_related_product_ids($_POST['r_related_products'] ?? []),
        ];
        $editingIngredients = is_array($_POST['r_ingredients'] ?? null) ? array_values($_POST['r_ingredients']) : [];
        $editingSteps = is_array($_POST['r_steps'] ?? null) ? array_values($_POST['r_steps']) : [];
      }

      $q = trim((string) ($_GET['q'] ?? ''));
      $catFilter = trim((string) ($_GET['cat'] ?? ''));
      $statusFilter = trim((string) ($_GET['status'] ?? 'all'));
      $featuredFilter = trim((string) ($_GET['featured'] ?? 'all'));
      if (!in_array($statusFilter, ['all', 'published', 'draft'], true)) {
          $statusFilter = 'all';
      }
      if (!in_array($featuredFilter, ['all', 'featured', 'standard'], true)) {
          $featuredFilter = 'all';
      }

      $rows = $recipeListRows ?? [];
      $recipeStats = [
          'total' => count($rows),
          'published' => count(array_filter($rows, static fn(array $row): bool => !empty($row['is_published']))),
          'featured' => count(array_filter($rows, static fn(array $row): bool => !empty($row['is_featured']))),
          'draft' => count(array_filter($rows, static fn(array $row): bool => empty($row['is_published']))),
      ];

      $filtered = array_values(array_filter($rows, static function (array $row) use ($q, $catFilter, $statusFilter, $featuredFilter): bool {
          if ($catFilter !== '' && (string) ($row['category'] ?? '') !== $catFilter) {
              return false;
          }

          if ($statusFilter === 'published' && empty($row['is_published'])) {
              return false;
          }

          if ($statusFilter === 'draft' && !empty($row['is_published'])) {
              return false;
          }

          if ($featuredFilter === 'featured' && empty($row['is_featured'])) {
              return false;
          }

          if ($featuredFilter === 'standard' && !empty($row['is_featured'])) {
              return false;
          }

          if ($q === '') {
              return true;
          }

          $haystack = mb_strtolower(
              trim((string) ($row['name'] ?? '') . ' ' . (string) ($row['slug'] ?? '') . ' ' . (string) ($row['category'] ?? ''))
          );

          return mb_strpos($haystack, mb_strtolower($q)) !== false;
      }));

      $heroPreviewValue = $formVals['hero_image_url'] !== '' ? $formVals['hero_image_url'] : (string) ($editingRecipe['hero_image'] ?? '');
      $thumbnailPreviewValue = $formVals['thumbnail_image_url'] !== '' ? $formVals['thumbnail_image_url'] : (string) ($editingRecipe['thumbnail_image'] ?? '');
      $recipeIngredientsToRender = !empty($editingIngredients) ? $editingIngredients : [''];
      $recipeStepsToRender = !empty($editingSteps) ? $editingSteps : [''];
      $previewLink = $formVals['slug'] !== '' ? recipe_preview_url($formVals['slug']) : '';
    ?>

    <?php if ($recipeFormError !== '' ): ?>
      <div class="flash error"><?php echo e($recipeFormError); ?></div>
    <?php endif; ?>
    <?php if ($recipeListError !== '' ): ?>
      <div class="flash error"><?php echo e($recipeListError); ?></div>
    <?php endif; ?>

    <div style="display:grid; gap:18px;">
      <div class="recipe-summary-grid">
        <article class="recipe-stat-card">
          <span>Total Recipes</span>
          <strong><?php echo (int) $recipeStats['total']; ?></strong>
          <small>All recipes in CMS</small>
        </article>
        <article class="recipe-stat-card">
          <span>Published</span>
          <strong><?php echo (int) $recipeStats['published']; ?></strong>
          <small>Visible on frontend</small>
        </article>
        <article class="recipe-stat-card">
          <span>Featured</span>
          <strong><?php echo (int) $recipeStats['featured']; ?></strong>
          <small>Highlighted across recipes</small>
        </article>
        <article class="recipe-stat-card">
          <span>Draft</span>
          <strong><?php echo (int) $recipeStats['draft']; ?></strong>
          <small>Not yet live</small>
        </article>
      </div>

      <div class="table-card">
        <div class="recipe-toolbar">
          <form method="get" class="recipe-toolbar-form">
            <input type="hidden" name="section" value="recipes">
            <input type="hidden" name="recipe_id" value="<?php echo (int) $recipeEditingId; ?>">
            <div class="field" style="margin:0;">
              <label>Search</label>
              <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search by name, slug, or category">
            </div>
            <div class="field" style="margin:0;">
              <label>Category</label>
              <select name="cat">
                <option value="">All categories</option>
                <?php foreach ($recipesCategories as $categoryOption): ?>
                  <option value="<?php echo e($categoryOption); ?>" <?php echo $catFilter === $categoryOption ? 'selected' : ''; ?>><?php echo e($categoryOption); ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="field" style="margin:0;">
              <label>Featured</label>
              <select name="featured">
                <option value="all" <?php echo $featuredFilter === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="featured" <?php echo $featuredFilter === 'featured' ? 'selected' : ''; ?>>Featured only</option>
                <option value="standard" <?php echo $featuredFilter === 'standard' ? 'selected' : ''; ?>>Non-featured</option>
              </select>
            </div>
            <div class="field" style="margin:0;">
              <label>Status</label>
              <select name="status">
                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="published" <?php echo $statusFilter === 'published' ? 'selected' : ''; ?>>Published</option>
                <option value="draft" <?php echo $statusFilter === 'draft' ? 'selected' : ''; ?>>Draft</option>
              </select>
            </div>
            <div class="actions recipe-toolbar-actions">
              <button type="submit">Apply Filters</button>
              <a class="btn secondary" href="/HIRA/admin/cms.php?section=recipes">Reset</a>
            </div>
          </form>
        </div>

        <div class="recipe-results-meta">
          <div>
            <strong><?php echo (int) count($filtered); ?></strong> recipe<?php echo count($filtered) === 1 ? '' : 's'; ?> match current filters
          </div>
          <div class="hint">
            Preview opens the frontend recipe page for published recipes. Duplicate creates a draft copy.
          </div>
        </div>

        <div class="recipe-table-wrap">
          <table class="admin-table recipe-admin-table">
            <thead>
              <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Time</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($filtered as $recipeRow): ?>
                <?php
                  $recipeId = (int) ($recipeRow['id'] ?? 0);
                  $recipeName = (string) ($recipeRow['name'] ?? '');
                  $recipeSlug = (string) ($recipeRow['slug'] ?? '');
                  $recipeCategory = (string) ($recipeRow['category'] ?? '');
                  $recipeTime = (int) ($recipeRow['cook_time_minutes'] ?? 0);
                  $recipeServings = (int) ($recipeRow['servings'] ?? 0);
                  $recipeImage = $recipeRow['thumbnail_image'] ?: ($recipeRow['hero_image'] ?? null);
                  $recipeIsPublished = !empty($recipeRow['is_published']);
                  $recipeIsFeatured = !empty($recipeRow['is_featured']);
                ?>
                <tr>
                  <td>
                    <img
                      class="recipe-table-image"
                      src="<?php echo e(cms_asset_preview(is_string($recipeImage) ? $recipeImage : null)); ?>"
                      alt="<?php echo e($recipeName); ?>"
                    >
                  </td>
                  <td>
                    <div class="recipe-name-cell">
                      <strong><?php echo e($recipeName); ?></strong>
                      <span><?php echo e($recipeSlug); ?></span>
                      <small><?php echo e(mb_strimwidth((string) ($recipeRow['short_description'] ?? ''), 0, 110, '...')); ?></small>
                    </div>
                  </td>
                  <td><?php echo e($recipeCategory); ?></td>
                  <td>
                    <div class="recipe-inline-metrics">
                      <strong><?php echo $recipeTime > 0 ? (int) $recipeTime . ' min' : 'TBD'; ?></strong>
                      <small><?php echo $recipeServings > 0 ? 'Serves ' . (int) $recipeServings : 'Servings TBD'; ?></small>
                    </div>
                  </td>
                  <td>
                    <div class="recipe-cell-stack">
                      <span class="recipe-pill <?php echo $recipeIsPublished ? 'live' : 'draft'; ?>">
                        <?php echo $recipeIsPublished ? 'Published' : 'Draft'; ?>
                      </span>
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="section" value="recipes_toggle">
                        <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
                        <input type="hidden" name="toggle_field" value="is_published">
                        <input type="hidden" name="toggle_value" value="<?php echo $recipeIsPublished ? 0 : 1; ?>">
                        <button type="submit" class="btn secondary"><?php echo $recipeIsPublished ? 'Move to Draft' : 'Publish'; ?></button>
                      </form>
                    </div>
                  </td>
                  <td>
                    <div class="recipe-cell-stack">
                      <span class="recipe-pill <?php echo $recipeIsFeatured ? 'featured' : 'standard'; ?>">
                        <?php echo $recipeIsFeatured ? 'Featured' : 'Standard'; ?>
                      </span>
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="section" value="recipes_toggle">
                        <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
                        <input type="hidden" name="toggle_field" value="is_featured">
                        <input type="hidden" name="toggle_value" value="<?php echo $recipeIsFeatured ? 0 : 1; ?>">
                        <button type="submit" class="btn secondary"><?php echo $recipeIsFeatured ? 'Unfeature' : 'Feature'; ?></button>
                      </form>
                    </div>
                  </td>
                  <td>
                    <div class="recipe-action-grid">
                      <a class="btn secondary" href="/HIRA/admin/cms.php?section=recipes&recipe_id=<?php echo $recipeId; ?>">Edit</a>
                      <?php if ($recipeIsPublished): ?>
                        <a class="btn secondary" href="<?php echo e(recipe_preview_url($recipeSlug)); ?>" target="_blank" rel="noreferrer">Preview</a>
                      <?php else: ?>
                        <span class="recipe-action-disabled" title="Publish this recipe to preview it on the frontend.">Preview</span>
                      <?php endif; ?>
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="section" value="recipes_duplicate">
                        <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
                        <button type="submit" class="btn secondary">Duplicate</button>
                      </form>
                      <form method="post" style="margin:0;">
                        <input type="hidden" name="section" value="recipes_delete">
                        <input type="hidden" name="recipe_id" value="<?php echo $recipeId; ?>">
                        <button type="submit" class="btn danger" onclick="return confirm('Delete this recipe?');">Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (empty($filtered)): ?>
                <tr>
                  <td colspan="7" style="text-align:center; padding:18px;">No recipes found for the current filter set.</td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="form-card">
        <div class="recipe-form-header">
          <div>
            <p class="brand-kicker" style="color:#f56c1b;"><?php echo $recipeEditingId > 0 ? 'Edit Recipe' : 'Add Recipe'; ?></p>
            <h3><?php echo $recipeEditingId > 0 ? 'Update recipe' : 'Create recipe'; ?></h3>
            <p class="hint" style="margin-top:8px;">
              Organize content in sections, preview media before saving, and drag rows to reorder ingredients and steps.
            </p>
          </div>
          <div class="recipe-form-header-actions">
            <?php if ($previewLink !== '' && $formVals['is_published'] === '1'): ?>
              <a class="btn secondary" href="<?php echo e($previewLink); ?>" target="_blank" rel="noreferrer">Preview Current</a>
            <?php endif; ?>
            <?php if ($recipeEditingId > 0): ?>
              <a class="btn secondary" href="/HIRA/admin/cms.php?section=recipes">+ Add New</a>
            <?php endif; ?>
          </div>
        </div>

        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="section" value="recipes">
          <?php if ($recipeEditingId > 0): ?>
            <input type="hidden" name="recipe_id" value="<?php echo (int) $recipeEditingId; ?>">
          <?php endif; ?>

          <details class="recipe-accordion" open>
            <summary>Basic Info</summary>
            <div class="recipe-accordion-body form-grid">
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
                <textarea name="r_short_description" rows="4" required><?php echo e($formVals['short_description']); ?></textarea>
              </div>
            </div>
          </details>

          <details class="recipe-accordion" open>
            <summary>Media</summary>
            <div class="recipe-accordion-body">
              <div class="form-grid">
                <div class="field full">
                  <label>Hero Image URL</label>
                  <input
                    type="url"
                    name="r_hero_image_url"
                    value="<?php echo e($formVals['hero_image_url']); ?>"
                    placeholder="https://example.com/hero.jpg"
                    data-preview-target="<?php echo e(preview_target('recipe_hero_image')); ?>"
                  >
                </div>
                <div class="field full">
                  <label>Hero Image Upload</label>
                  <input
                    class="file-input"
                    type="file"
                    name="r_hero_image"
                    accept="image/*"
                    data-preview-target="<?php echo e(preview_target('recipe_hero_image')); ?>"
                  >
                </div>
                <div class="field full">
                  <label>Thumbnail Image URL</label>
                  <input
                    type="url"
                    name="r_thumbnail_image_url"
                    value="<?php echo e($formVals['thumbnail_image_url']); ?>"
                    placeholder="https://example.com/thumb.jpg"
                    data-preview-target="<?php echo e(preview_target('recipe_thumbnail_image')); ?>"
                  >
                </div>
                <div class="field full">
                  <label>Thumbnail Image Upload</label>
                  <input
                    class="file-input"
                    type="file"
                    name="r_thumbnail_image"
                    accept="image/*"
                    data-preview-target="<?php echo e(preview_target('recipe_thumbnail_image')); ?>"
                  >
                </div>
              </div>
              <div class="recipe-preview-grid">
                <div class="image-preview-card">
                  <span>Hero Preview</span>
                  <img
                    id="<?php echo e(preview_target('recipe_hero_image')); ?>"
                    src="<?php echo e(cms_asset_preview($heroPreviewValue)); ?>"
                    alt="Recipe hero preview"
                  >
                </div>
                <div class="image-preview-card">
                  <span>Thumbnail Preview</span>
                  <img
                    id="<?php echo e(preview_target('recipe_thumbnail_image')); ?>"
                    src="<?php echo e(cms_asset_preview($thumbnailPreviewValue)); ?>"
                    alt="Recipe thumbnail preview"
                  >
                </div>
              </div>
            </div>
          </details>

          <details class="recipe-accordion" open>
            <summary>Recipe Details</summary>
            <div class="recipe-accordion-body form-grid">
              <div class="field">
                <label>Cook Time (minutes)</label>
                <input type="number" min="0" name="r_cook_time_minutes" value="<?php echo e($formVals['cook_time_minutes']); ?>">
              </div>
              <div class="field">
                <label>Servings</label>
                <input type="number" min="1" name="r_servings" value="<?php echo e($formVals['servings']); ?>">
              </div>
              <div class="field">
                <label>Difficulty</label>
                <input type="text" name="r_difficulty" value="<?php echo e($formVals['difficulty']); ?>" placeholder="Easy, Medium, Hard">
              </div>
              <div class="field">
                <label>Status</label>
                <select name="r_is_published">
                  <option value="1" <?php echo $formVals['is_published'] === '1' ? 'selected' : ''; ?>>Published</option>
                  <option value="0" <?php echo $formVals['is_published'] === '0' ? 'selected' : ''; ?>>Draft</option>
                </select>
              </div>
              <div class="field">
                <label>Featured</label>
                <select name="r_is_featured">
                  <option value="1" <?php echo $formVals['is_featured'] === '1' ? 'selected' : ''; ?>>Featured</option>
                  <option value="0" <?php echo $formVals['is_featured'] === '0' ? 'selected' : ''; ?>>Standard</option>
                </select>
              </div>
            </div>
          </details>

          <details class="recipe-accordion" open>
            <summary>Ingredients</summary>
            <div class="recipe-accordion-body">
              <p class="hint" style="margin-bottom:10px;">Add rows, remove rows, and drag to reorder before saving.</p>
              <div id="r-ingredients-container" class="dynamic-list sortable-container">
                <?php foreach ($recipeIngredientsToRender as $ingredientValue): ?>
                  <div class="dynamic-row sortable-row" draggable="true">
                    <span class="drag-handle" title="Drag to reorder">::</span>
                    <input type="text" name="r_ingredients[]" value="<?php echo e((string) $ingredientValue); ?>" placeholder="Ingredient" required>
                    <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="actions" style="margin-top:10px;">
                <button type="button" onclick="addIngredientRow('r-ingredients-container')">Add Ingredient</button>
              </div>
            </div>
          </details>

          <details class="recipe-accordion" open>
            <summary>Steps</summary>
            <div class="recipe-accordion-body">
              <p class="hint" style="margin-bottom:10px;">Drag steps into the final cooking order that should appear on the frontend.</p>
              <div id="r-steps-container" class="dynamic-list sortable-container">
                <?php foreach ($recipeStepsToRender as $stepValue): ?>
                  <div class="dynamic-row sortable-row" draggable="true">
                    <span class="drag-handle" title="Drag to reorder">::</span>
                    <textarea name="r_steps[]" rows="3" placeholder="Step" required><?php echo e((string) $stepValue); ?></textarea>
                    <button type="button" class="danger" onclick="removeDynamicRow(this)">Remove</button>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="actions" style="margin-top:10px;">
                <button type="button" onclick="addStepRow('r-steps-container')">Add Step</button>
              </div>
            </div>
          </details>

          <details class="recipe-accordion" open>
            <summary>Tips</summary>
            <div class="recipe-accordion-body">
              <div class="field" style="margin:0;">
                <label>Tips (one per line)</label>
                <textarea name="r_tips" rows="5" placeholder="One useful serving or cooking tip per line"><?php echo e((string) $formVals['tips']); ?></textarea>
              </div>
            </div>
          </details>

          <details class="recipe-accordion" open>
            <summary>Related Products</summary>
            <div class="recipe-accordion-body">
              <div class="field" style="margin:0;">
                <label>Suggested HIRA products</label>
                <select name="r_related_products[]" multiple size="<?php echo max(4, min(8, count($productOptions))); ?>">
                  <?php foreach ($productOptions as $product): ?>
                    <?php $productId = (int) ($product['id'] ?? 0); ?>
                    <option value="<?php echo $productId; ?>" <?php echo in_array($productId, $formVals['related_products'], true) ? 'selected' : ''; ?>>
                      <?php echo e((string) ($product['name'] ?? 'Product')); ?><?php echo !empty($product['category']) ? ' (' . e((string) $product['category']) . ')' : ''; ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <p class="hint">Hold Ctrl (Windows) or Command (Mac) to select multiple products for the recipe details page.</p>
              </div>
            </div>
          </details>

          <div class="actions" style="margin-top:18px;">
            <button type="submit"><?php echo $recipeEditingId > 0 ? 'Update Recipe' : 'Save Recipe'; ?></button>
            <a class="btn secondary" href="/HIRA/admin/cms.php?section=recipes">Cancel</a>
          </div>
        </form>
      </div>
    </div>
  </article>
</section>

<style>
  .recipe-summary-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }

  .recipe-stat-card {
    padding: 18px 20px;
    border-radius: 22px;
    background: linear-gradient(145deg, rgba(255, 255, 255, 0.98), rgba(255, 247, 237, 0.94));
    border: 1px solid rgba(231, 122, 68, 0.12);
    box-shadow: 0 24px 60px -42px rgba(99, 60, 28, 0.45);
    display: grid;
    gap: 6px;
  }

  .recipe-stat-card span,
  .recipe-stat-card small {
    color: #7a6b62;
  }

  .recipe-stat-card strong {
    font-size: 2rem;
    line-height: 1;
    color: #2f261f;
  }

  .recipe-toolbar-form {
    display: grid;
    gap: 14px;
    grid-template-columns: minmax(220px, 1.4fr) repeat(3, minmax(160px, 0.8fr)) auto;
    align-items: end;
  }

  .recipe-toolbar-actions {
    justify-content: flex-start;
  }

  .recipe-results-meta {
    display: flex;
    gap: 10px;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin: 14px 0 16px;
  }

  .recipe-table-wrap {
    overflow-x: auto;
  }

  .recipe-admin-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 1020px;
  }

  .recipe-table-image {
    width: 72px;
    height: 72px;
    border-radius: 18px;
    object-fit: cover;
    border: 1px solid rgba(231, 122, 68, 0.14);
  }

  .recipe-name-cell {
    display: grid;
    gap: 6px;
  }

  .recipe-name-cell span {
    font-size: 0.8rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #c76d2e;
  }

  .recipe-name-cell small,
  .recipe-inline-metrics small {
    color: #7a6b62;
  }

  .recipe-inline-metrics,
  .recipe-cell-stack {
    display: grid;
    gap: 8px;
  }

  .recipe-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .recipe-pill.live {
    background: rgba(21, 128, 61, 0.12);
    color: #166534;
  }

  .recipe-pill.draft {
    background: rgba(120, 113, 108, 0.12);
    color: #57534e;
  }

  .recipe-pill.featured {
    background: rgba(249, 115, 22, 0.12);
    color: #c2410c;
  }

  .recipe-pill.standard {
    background: rgba(217, 119, 6, 0.08);
    color: #92400e;
  }

  .recipe-action-grid {
    display: grid;
    gap: 8px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .recipe-action-grid form,
  .recipe-cell-stack form {
    display: block;
  }

  .recipe-action-disabled {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 44px;
    padding: 0 18px;
    border-radius: 999px;
    background: rgba(120, 113, 108, 0.12);
    color: #78716c;
    font-weight: 700;
    cursor: not-allowed;
  }

  .recipe-form-header {
    display: flex;
    gap: 16px;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    margin-bottom: 14px;
  }

  .recipe-form-header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }

  .recipe-accordion {
    border: 1px solid rgba(231, 122, 68, 0.12);
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.92);
    box-shadow: 0 18px 48px -40px rgba(47, 38, 31, 0.45);
  }

  .recipe-accordion + .recipe-accordion {
    margin-top: 14px;
  }

  .recipe-accordion summary {
    list-style: none;
    cursor: pointer;
    padding: 18px 22px;
    font-weight: 700;
    color: #2f261f;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .recipe-accordion summary::-webkit-details-marker {
    display: none;
  }

  .recipe-accordion summary::after {
    content: '+';
    font-size: 1.2rem;
    color: #c2410c;
  }

  .recipe-accordion[open] summary::after {
    content: '−';
  }

  .recipe-accordion-body {
    padding: 0 22px 22px;
  }

  .recipe-preview-grid {
    display: grid;
    gap: 14px;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    margin-top: 18px;
  }

  .recipe-preview-grid .image-preview-card {
    display: grid;
    gap: 10px;
  }

  .recipe-preview-grid .image-preview-card span {
    font-size: 0.78rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #7a6b62;
  }

  .sortable-row {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 10px;
    align-items: start;
  }

  .sortable-row.dragging {
    opacity: 0.55;
  }

  .drag-handle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 38px;
    min-height: 44px;
    border-radius: 14px;
    background: rgba(231, 122, 68, 0.08);
    border: 1px dashed rgba(231, 122, 68, 0.24);
    color: #c2410c;
    font-weight: 700;
    cursor: grab;
    user-select: none;
  }

  @media (max-width: 1180px) {
    .recipe-summary-grid {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .recipe-toolbar-form {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }

  @media (max-width: 720px) {
    .recipe-summary-grid,
    .recipe-toolbar-form,
    .recipe-preview-grid,
    .recipe-action-grid {
      grid-template-columns: 1fr;
    }

    .sortable-row {
      grid-template-columns: 1fr;
    }

    .drag-handle {
      width: 100%;
    }
  }
</style>

<script>
(() => {
  const root = document.getElementById('cms-tab-root');
  if (!root) return;

  const tabEls = Array.from(document.querySelectorAll('[data-cms-tab], [data-cms-tab-btn]'));
  const panelEls = Array.from(root.querySelectorAll('[data-cms-tab-panel]'));
  const breadcrumb = document.getElementById('cms-breadcrumb-section');
  const allowedTabs = new Set(panelEls.map((panel) => (panel.getAttribute('data-cms-tab-panel') || '').trim()).filter(Boolean));

  const getTabName = (el) => {
    return (el.getAttribute('data-cms-tab') || el.getAttribute('data-cms-tab-btn') || '').trim();
  };

  const clearTabs = () => {
    tabEls.forEach((tab) => {
      tab.classList.remove('active');
      tab.classList.add('secondary');
      tab.setAttribute('aria-pressed', 'false');
    });

    panelEls.forEach((panel) => {
      panel.style.display = 'none';
    });
  };

  const showTab = (name) => {
    if (!allowedTabs.has(name)) {
      return;
    }

    clearTabs();

    tabEls.forEach((tab) => {
      if (getTabName(tab) === name) {
        tab.classList.add('active');
        tab.classList.remove('secondary');
        tab.setAttribute('aria-pressed', 'true');
      }
    });

    panelEls.forEach((panel) => {
      if ((panel.getAttribute('data-cms-tab-panel') || '').trim() === name) {
        panel.style.display = '';
      }
    });

    if (breadcrumb) {
      breadcrumb.textContent = name.charAt(0).toUpperCase() + name.slice(1);
    }
  };

  tabEls.forEach((tab) => {
    tab.addEventListener('click', (event) => {
      event.preventDefault();
      const name = getTabName(tab);
      if (name !== '') {
        showTab(name);
      }
    });
  });

  showTab(root.getAttribute('data-initial-cms-tab') || 'home');

  const bindPreview = (input) => {
    const targetId = (input.getAttribute('data-preview-target') || '').trim();
    if (targetId === '') {
      return;
    }

    const preview = document.getElementById(targetId);
    if (!preview) {
      return;
    }

    if (input.type === 'file') {
      input.addEventListener('change', (event) => {
        const file = event.target.files && event.target.files[0];
        if (!file) {
          return;
        }

        const reader = new FileReader();
        reader.onload = (loadEvent) => {
          if (typeof loadEvent.target?.result === 'string') {
            preview.src = loadEvent.target.result;
          }
        };
        reader.readAsDataURL(file);
      });

      return;
    }

    input.addEventListener('input', (event) => {
      const value = event.target.value.trim();
      if (value !== '') {
        preview.src = value;
      }
    });
  };

  document.querySelectorAll('[data-preview-target]').forEach(bindPreview);

  let draggedRow = null;

  const decorateSortableRow = (row) => {
    if (!(row instanceof HTMLElement) || row.dataset.sortReady === '1') {
      return;
    }

    row.dataset.sortReady = '1';
    row.classList.add('sortable-row');
    row.setAttribute('draggable', 'true');

    if (!row.querySelector('.drag-handle')) {
      const handle = document.createElement('span');
      handle.className = 'drag-handle';
      handle.textContent = '::';
      handle.title = 'Drag to reorder';
      row.insertBefore(handle, row.firstChild);
    }

    row.addEventListener('dragstart', () => {
      draggedRow = row;
      row.classList.add('dragging');
    });

    row.addEventListener('dragend', () => {
      row.classList.remove('dragging');
      draggedRow = null;
    });
  };

  const decorateSortableContainer = (container) => {
    if (!(container instanceof HTMLElement) || container.dataset.sortReady === '1') {
      return;
    }

    container.dataset.sortReady = '1';

    container.addEventListener('dragover', (event) => {
      event.preventDefault();
      const targetRow = event.target.closest('.sortable-row');

      if (!draggedRow || !targetRow || draggedRow === targetRow || targetRow.parentElement !== container) {
        return;
      }

      const targetRect = targetRow.getBoundingClientRect();
      const shouldInsertAfter = event.clientY > targetRect.top + targetRect.height / 2;

      if (shouldInsertAfter) {
        targetRow.after(draggedRow);
      } else {
        targetRow.before(draggedRow);
      }
    });
  };

  document.querySelectorAll('.sortable-container').forEach((container) => {
    decorateSortableContainer(container);
    Array.from(container.querySelectorAll('.dynamic-row')).forEach(decorateSortableRow);
  });

  const buildDynamicRow = (type, name, placeholder) => {
    const row = document.createElement('div');
    row.className = 'dynamic-row sortable-row';
    row.setAttribute('draggable', 'true');

    const handle = document.createElement('span');
    handle.className = 'drag-handle';
    handle.textContent = '::';
    handle.title = 'Drag to reorder';
    row.appendChild(handle);

    if (type === 'textarea') {
      const textarea = document.createElement('textarea');
      textarea.name = name;
      textarea.rows = 3;
      textarea.placeholder = placeholder;
      textarea.required = true;
      row.appendChild(textarea);
    } else {
      const input = document.createElement('input');
      input.type = 'text';
      input.name = name;
      input.placeholder = placeholder;
      input.required = true;
      row.appendChild(input);
    }

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'danger';
    button.textContent = 'Remove';
    button.addEventListener('click', () => window.removeDynamicRow(button));
    row.appendChild(button);

    decorateSortableRow(row);
    return row;
  };

  window.removeDynamicRow = (button) => {
    const row = button.closest('.dynamic-row');
    const container = row?.parentElement;
    if (!row || !container) {
      return;
    }

    if (container.children.length === 1) {
      const field = row.querySelector('input, textarea');
      if (field) {
        field.value = '';
      }
      return;
    }

    row.remove();
  };

  window.addIngredientRow = (containerId = 'r-ingredients-container') => {
    const container = document.getElementById(containerId);
    if (!container) {
      return;
    }

    container.appendChild(buildDynamicRow('input', 'r_ingredients[]', 'Ingredient'));
  };

  window.addStepRow = (containerId = 'r-steps-container') => {
    const container = document.getElementById(containerId);
    if (!container) {
      return;
    }

    container.appendChild(buildDynamicRow('textarea', 'r_steps[]', 'Step'));
  };
})();
</script>
<?php
render_admin_footer();
?>
