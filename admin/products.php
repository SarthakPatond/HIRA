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

    redirect('/HIRA/admin/products.php');
}

$products = fetch_products(true);

render_admin_header('Products', 'products.php');
?>
<section class="table-card">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px;">
    <div>
      <p class="brand-kicker" style="color:#5e7c39;">Catalog Management</p>
      <h3>All Products</h3>
    </div>
    <a class="btn" href="/HIRA/admin/product-form.php">Add Product</a>
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
        <?php foreach ($products as $product): ?>
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
                <a class="btn secondary" href="/HIRA/admin/product-form.php?id=<?php echo $product['id']; ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Delete this product permanently?');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                  <button type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?>
          <tr>
            <td colspan="6">No products found. Add the first product to activate the storefront catalog.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
render_admin_footer();
