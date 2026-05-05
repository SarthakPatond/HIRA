<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';

function admin_nav_items(): array
{
    return [
        'dashboard.php' => 'Dashboard',
        'products.php' => 'Products',
        'cms.php' => 'CMS Content',
        'leads.php' => 'Leads',
    ];
}

function render_admin_header(string $title, string $activePage): void
{
    $flash = get_flash();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?php echo e($title); ?> | Hira Admin</title>
      <link rel="stylesheet" href="/HIRA/admin/assets/admin.css">
    </head>
    <body>
      <div class="admin-shell">
        <aside class="sidebar">
          <div>
            <p class="brand-kicker">Hira FMCG</p>
            <h1>Admin Control</h1>
            <p class="muted">Manage products, storytelling content, and incoming leads.</p>
          </div>
          <nav class="nav-list">
            <?php foreach (admin_nav_items() as $url => $label): ?>
              <a class="<?php echo $activePage === $url ? 'active' : ''; ?>" href="/HIRA/admin/<?php echo e($url); ?>">
                <?php echo e($label); ?>
              </a>
            <?php endforeach; ?>
          </nav>
          <div class="sidebar-footer">
            <div class="user-chip">
              <span>Signed in as</span>
              <strong><?php echo e(admin_user()['username'] ?? 'admin'); ?></strong>
            </div>
            <a class="logout-link" href="/HIRA/admin/logout.php">Logout</a>
          </div>
        </aside>
        <main class="main-panel">
          <header class="topbar">
            <div>
              <p class="brand-kicker">Dashboard</p>
              <h2><?php echo e($title); ?></h2>
            </div>
          </header>
          <?php if ($flash): ?>
            <div class="flash <?php echo e($flash['type']); ?>">
              <?php echo e($flash['message']); ?>
            </div>
          <?php endif; ?>
    <?php
}

function render_admin_footer(): void
{
    ?>
        </main>
      </div>
    </body>
    </html>
    <?php
}
