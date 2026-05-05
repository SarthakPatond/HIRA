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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $section = (string) ($_POST['section'] ?? '');

    try {
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

<section class="grid">
  <article class="form-card">
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

  <article class="form-card">
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

  <article class="form-card">
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
</section>
<script>
  document.querySelectorAll('[data-preview-target]').forEach((field) => {
    const preview = document.getElementById(field.dataset.previewTarget);
    if (!preview) {
      return;
    }

    if (field.type === 'file') {
      field.addEventListener('change', (event) => {
        const file = event.target.files && event.target.files[0];
        if (!file) {
          return;
        }

        const reader = new FileReader();
        reader.onload = (loadEvent) => {
          preview.src = loadEvent.target.result;
        };
        reader.readAsDataURL(file);
      });

      return;
    }

    field.addEventListener('input', (event) => {
      const value = event.target.value.trim();
      if (value !== '') {
        preview.src = value;
      }
    });
  });
</script>
<?php
render_admin_footer();
