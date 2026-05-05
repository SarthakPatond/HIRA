<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_admin_login();

$pdo = get_db();
$productCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$comingSoonCount = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE is_coming_soon = 1')->fetchColumn();
$leadCount = (int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();
$pageCount = (int) $pdo->query('SELECT COUNT(*) FROM cms_pages')->fetchColumn();
$recentProducts = fetch_products(true);
$recentLeads = $pdo->query('SELECT * FROM leads ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();

render_admin_header('Overview', 'dashboard.php');
?>
<section class="grid cards">
  <article class="card">
    <p class="brand-kicker" style="color:#5e7c39;">Products</p>
    <h3>Total Catalog</h3>
    <p class="metric"><?php echo $productCount; ?></p>
  </article>
  <article class="card">
    <p class="brand-kicker" style="color:#f56c1b;">Launch Queue</p>
    <h3>Coming Soon</h3>
    <p class="metric"><?php echo $comingSoonCount; ?></p>
  </article>
  <article class="card">
    <p class="brand-kicker" style="color:#cb3a1a;">Inbound</p>
    <h3>Leads Captured</h3>
    <p class="metric"><?php echo $leadCount; ?></p>
  </article>
  <article class="card">
    <p class="brand-kicker" style="color:#5e7c39;">CMS</p>
    <h3>Editable Pages</h3>
    <p class="metric"><?php echo $pageCount; ?></p>
  </article>
</section>

<section class="split" style="margin-top:24px;">
  <article class="table-card">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:14px;">
      <div>
        <p class="brand-kicker" style="color:#5e7c39;">Catalog Snapshot</p>
        <h3>Latest Products</h3>
      </div>
      <a class="btn" href="/HIRA/admin/product-form.php">Add Product</a>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach (array_slice($recentProducts, 0, 6) as $product): ?>
            <tr>
              <td><?php echo e($product['name']); ?></td>
              <td><?php echo e($product['category']); ?></td>
              <td>
                <span class="status-pill <?php echo $product['is_coming_soon'] ? 'coming' : ''; ?>">
                  <?php echo $product['is_coming_soon'] ? 'Coming Soon' : 'Live'; ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recentProducts): ?>
            <tr>
              <td colspan="3">No products yet. Add your first product to populate the storefront.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>

  <article class="table-card">
    <div style="margin-bottom:14px;">
      <p class="brand-kicker" style="color:#cb3a1a;">Lead Feed</p>
      <h3>Recent Inquiries</h3>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Phone</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentLeads as $lead): ?>
            <tr>
              <td><?php echo e($lead['name']); ?></td>
              <td><?php echo e($lead['business_type']); ?></td>
              <td><?php echo e($lead['phone']); ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$recentLeads): ?>
            <tr>
              <td colspan="3">Leads will appear here once the distributor or contact forms start receiving submissions.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </article>
</section>
<?php
render_admin_footer();
