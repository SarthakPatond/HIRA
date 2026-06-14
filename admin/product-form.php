<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_admin_login();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$editing = $id > 0;
$product = $editing ? fetch_product_by_id($id) : null;

if ($editing && !$product) {
    set_flash('error', 'Product not found.');
    redirect('/admin/products.php');
}

$formData = [
    'name' => $product['name'] ?? '',
    'category' => $product['category'] ?? '',
    'description' => $product['description'] ?? '',
    'benefits' => isset($product['benefits']) ? implode(PHP_EOL, $product['benefits']) : '',
    'pack_sizes' => isset($product['pack_sizes']) ? implode(PHP_EOL, $product['pack_sizes']) : '',
    'image_url' => isset($product['image_path']) && preg_match('/^https?:\/\//i', (string) $product['image_path']) ? (string) $product['image_path'] : '',
    'is_coming_soon' => !empty($product['is_coming_soon']) ? '1' : '0',
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'category' => trim((string) ($_POST['category'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'benefits' => (string) ($_POST['benefits'] ?? ''),
        'pack_sizes' => (string) ($_POST['pack_sizes'] ?? ''),
        'image_url' => trim((string) ($_POST['image_url'] ?? '')),
        'is_coming_soon' => (string) ($_POST['is_coming_soon'] ?? '0'),
    ];

    try {
        if ($formData['name'] === '' || $formData['category'] === '' || $formData['description'] === '') {
            throw new RuntimeException('Name, category, and description are required.');
        }

        $imagePath = $formData['image_url'] !== '' ? $formData['image_url'] : ($product['image_path'] ?? null);
        $newImage = save_uploaded_image($_FILES['image'] ?? []);
        if ($newImage) {
            delete_local_upload($imagePath);
            $imagePath = $newImage;
        } elseif (($product['image_path'] ?? null) && $imagePath !== ($product['image_path'] ?? null)) {
            delete_local_upload($product['image_path']);
        }

        $payload = [
            'name' => $formData['name'],
            'category' => $formData['category'],
            'description' => $formData['description'],
            'benefits' => encode_json_value(normalize_multiline_list($formData['benefits'])),
            'pack_sizes' => encode_json_value(normalize_multiline_list($formData['pack_sizes'])),
            'image' => $imagePath,
            'is_coming_soon' => (int) $formData['is_coming_soon'],
        ];

        if ($editing) {
            $stmt = get_db()->prepare(
                'UPDATE products SET
                  name = :name,
                  category = :category,
                  description = :description,
                  benefits = :benefits,
                  pack_sizes = :pack_sizes,
                  image = :image,
                  is_coming_soon = :is_coming_soon
                WHERE id = :id'
            );
            $payload['id'] = $id;
            $stmt->execute($payload);
            set_flash('success', 'Product updated successfully.');
        } else {
            $stmt = get_db()->prepare(
                'INSERT INTO products (name, category, description, benefits, pack_sizes, image, is_coming_soon)
                 VALUES (:name, :category, :description, :benefits, :pack_sizes, :image, :is_coming_soon)'
            );
            $stmt->execute($payload);
            set_flash('success', 'Product added successfully.');
        }

        redirect('/admin/products.php');
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

render_admin_header($editing ? 'Edit Product' : 'Add Product', 'products.php');
?>
<section class="form-card">
  <div style="margin-bottom:18px;">
    <p class="brand-kicker" style="color:#f56c1b;">Product Management</p>
    <h3><?php echo $editing ? 'Edit Product' : 'Create Product'; ?></h3>
  </div>

  <?php if ($error !== ''): ?>
    <div class="flash error"><?php echo e($error); ?></div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <?php if ($editing): ?>
      <input type="hidden" name="id" value="<?php echo $id; ?>">
    <?php endif; ?>
    <div class="form-grid">
      <div class="field">
        <label for="name">Product Name</label>
        <input id="name" type="text" name="name" value="<?php echo e($formData['name']); ?>" required>
      </div>
      <div class="field">
        <label for="category">Category</label>
        <input id="category" type="text" name="category" value="<?php echo e($formData['category']); ?>" placeholder="Poha, Sabudana, Parmal..." required>
      </div>
      <div class="field full">
        <label for="description">Description</label>
        <textarea id="description" name="description" required><?php echo e($formData['description']); ?></textarea>
      </div>
      <div class="field">
        <label for="benefits">Benefits</label>
        <textarea id="benefits" name="benefits" placeholder="One benefit per line"><?php echo e($formData['benefits']); ?></textarea>
      </div>
      <div class="field">
        <label for="pack_sizes">Pack Sizes</label>
        <textarea id="pack_sizes" name="pack_sizes" placeholder="One pack size per line"><?php echo e($formData['pack_sizes']); ?></textarea>
      </div>
      <div class="field">
        <label for="image_url">Product Image URL</label>
        <input id="image_url" type="url" name="image_url" value="<?php echo e($formData['image_url']); ?>" placeholder="https://example.com/image.jpg">
        <p class="hint">Paste an external image URL or upload a file below. Upload takes priority.</p>
      </div>
      <div class="field">
        <label for="image">Product Image</label>
        <input class="file-input" id="image" type="file" name="image" accept="image/*">
        <p class="hint">Supported formats: JPG, PNG, WEBP, GIF. Max size 5 MB.</p>
      </div>
      <div class="field">
        <label for="is_coming_soon">Visibility</label>
        <select id="is_coming_soon" name="is_coming_soon">
          <option value="0" <?php echo $formData['is_coming_soon'] === '0' ? 'selected' : ''; ?>>Live Product</option>
          <option value="1" <?php echo $formData['is_coming_soon'] === '1' ? 'selected' : ''; ?>>Coming Soon</option>
        </select>
      </div>
    </div>

    <div class="preview-box">
      <img id="image-preview" src="<?php echo e($formData['image_url'] !== '' ? $formData['image_url'] : ($product['image'] ?? 'https://via.placeholder.com/160x160?text=Preview')); ?>" alt="Preview">
      <div>
        <strong>Upload Preview</strong>
        <p class="hint">A live preview appears here before saving so the admin can confirm the selected image.</p>
      </div>
    </div>

    <div class="actions">
      <button type="submit"><?php echo $editing ? 'Update Product' : 'Save Product'; ?></button>
      <a class="btn secondary" href="/admin/products.php">Cancel</a>
    </div>
  </form>
</section>

<script>
  const input = document.getElementById('image');
  const urlInput = document.getElementById('image_url');
  const preview = document.getElementById('image-preview');

  if (input && preview) {
    input.addEventListener('change', function (event) {
      const file = event.target.files && event.target.files[0];
      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (loadEvent) {
        preview.src = loadEvent.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  if (urlInput && preview) {
    urlInput.addEventListener('input', function (event) {
      const value = event.target.value.trim();
      if (value !== '') {
        preview.src = value;
      }
    });
  }
</script>
<?php
render_admin_footer();
