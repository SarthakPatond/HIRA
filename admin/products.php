<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_admin_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $id = (int) ($_POST['id'] ?? 0);
  $product = fetch_product_by_id($id);

  if ($product) {
    $stmt = get_db()->prepare('DELETE FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    delete_local_upload($product['image_path']);
    set_flash('success', 'Product deleted successfully.');
  } else {
    set_flash('error', 'Product not found.');
  }

  redirect('/admin/products.php');
}

$products = fetch_products(true);

$searchQuery = trim((string) ($_GET['q'] ?? ''));
$statusFilter = (string) ($_GET['status'] ?? 'all');
$categoryFilter = trim((string) ($_GET['category'] ?? ''));

$categories = [];
foreach ($products as $p) {
  $cat = trim((string) ($p['category'] ?? ''));
  if ($cat !== '')
    $categories[] = $cat;
}
$categories = array_values(array_unique($categories));

$filteredProducts = array_values($products);

if ($searchQuery !== '') {
  $needle = mb_strtolower($searchQuery);
  $filteredProducts = array_values(array_filter($filteredProducts, function ($p) use ($needle) {
    $name = mb_strtolower((string) ($p['name'] ?? ''));
    $cat = mb_strtolower((string) ($p['category'] ?? ''));
    return (strpos($name, $needle) !== false) || (strpos($cat, $needle) !== false);
  }));
}

if ($statusFilter === 'live') {
  $filteredProducts = array_values(array_filter($filteredProducts, function ($p) {
    return empty($p['is_coming_soon']);
  }));
} elseif ($statusFilter === 'coming') {
  $filteredProducts = array_values(array_filter($filteredProducts, function ($p) {
    return !empty($p['is_coming_soon']);
  }));
}

if ($categoryFilter !== '') {
  $filteredProducts = array_values(array_filter($filteredProducts, function ($p) use ($categoryFilter) {
    return trim((string) ($p['category'] ?? '')) === $categoryFilter;
  }));
}

render_admin_header('Products', 'products.php');
?>
<section class="table-card">
  <div
    style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;margin-bottom:18px;flex-wrap:wrap;">
    <div>
      <p class="brand-kicker" style="color:#5e7c39;">Catalog Management</p>
      <h3>Products</h3>
      <div class="hint" style="margin-top:6px;">
        <?php
        $activeFilters = [];
        if ($searchQuery !== '')
          $activeFilters[] = 'Search: ' . $searchQuery;
        if ($statusFilter !== 'all')
          $activeFilters[] = ($statusFilter === 'live' ? 'Live' : 'Coming Soon');
        if ($categoryFilter !== '')
          $activeFilters[] = 'Category: ' . $categoryFilter;
        echo $activeFilters ? 'Filtering: ' . e(implode(' • ', $activeFilters)) : 'Use search and filters to quickly find products.';
        ?>
      </div>
    </div>
    <a class="btn" href="/admin/product-form.php">Add Product</a>
  </div>

  <div class="form-card" style="padding:16px; margin-bottom:18px;">
    <form method="get" action="/admin/products.php">
      <div class="form-grid" style="grid-template-columns: 1.6fr 1fr 1fr; align-items:end;">
        <div class="field">
          <label for="q">Search</label>
          <input id="q" type="text" name="q" value="<?php echo e($searchQuery); ?>"
            placeholder="Product name or category">
        </div>
        <div class="field">
          <label for="category">Category</label>
          <select id="category" name="category">
            <option value="" <?php echo $categoryFilter === '' ? 'selected' : ''; ?>>All</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?php echo e($cat); ?>" <?php echo $categoryFilter === $cat ? 'selected' : ''; ?>>
                <?php echo e($cat); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label for="status">Status</label>
          <select id="status" name="status">
            <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
            <option value="live" <?php echo $statusFilter === 'live' ? 'selected' : ''; ?>>Live</option>
            <option value="coming" <?php echo $statusFilter === 'coming' ? 'selected' : ''; ?>>Coming Soon</option>
          </select>
        </div>
      </div>

      <div class="actions" style="margin-top:14px;">
        <button type="submit">Apply</button>
        <a class="btn secondary" href="/admin/products.php">Reset</a>
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
          <th>Pack Sizes</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($filteredProducts as $product): ?>
          <tr>
            <td>
              <?php if ($product['image']): ?>
                <img class="thumb" src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
              <?php else: ?>
                <span class="hint">No image</span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?php echo e($product['name']); ?></strong>
              <div class="hint"><?php echo e(substr($product['description'], 0, 90)); ?>...</div>
            </td>
            <td><?php echo e($product['category']); ?></td>
            <td><?php echo e(implode(', ', $product['pack_sizes'])); ?></td>
            <td>
              <span class="status-pill <?php echo $product['is_coming_soon'] ? 'coming' : ''; ?>">
                <?php echo $product['is_coming_soon'] ? 'Coming Soon' : 'Live'; ?>
              </span>
            </td>
            <td>
              <div class="actions">
                <a class="btn secondary" href="/admin/product-form.php?id=<?php echo $product['id']; ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Delete this product permanently?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                  <button type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$filteredProducts): ?>
          <tr>
            <td colspan="6">No products match current filters.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
render_admin_footer();
?>