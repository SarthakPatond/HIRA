<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';
require_admin_login();

$leads = get_db()->query('SELECT * FROM leads ORDER BY created_at DESC, id DESC')->fetchAll();

render_admin_header('Leads', 'leads.php');
?>
<section class="table-card">
  <div style="margin-bottom:18px;">
    <p class="brand-kicker" style="color:#cb3a1a;">Lead Management</p>
    <h3>All Distributor and Contact Inquiries</h3>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Phone</th>
          <th>Business Type</th>
          <th>Message</th>
          <th>Received</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($leads as $lead): ?>
          <tr>
            <td><?php echo e($lead['name']); ?></td>
            <td><?php echo e($lead['phone']); ?></td>
            <td><?php echo e($lead['business_type']); ?></td>
            <td><?php echo e($lead['message']); ?></td>
            <td><?php echo e($lead['created_at']); ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$leads): ?>
          <tr>
            <td colspan="5">No inquiries yet. Distributor and contact form submissions will appear here automatically.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php
render_admin_footer();
