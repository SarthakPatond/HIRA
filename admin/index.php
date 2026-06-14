<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if (is_admin_logged_in()) {
    redirect('/admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (attempt_admin_login($username, $password)) {
        set_flash('success', 'Welcome back. You are now signed in.');
        redirect('/admin/dashboard.php');
    }

    $error = 'Invalid username or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Hira Admin Login</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
</head>
<body>
  <div class="login-page">
    <section class="login-panel">
      <p class="brand-kicker" style="color:#5e7c39;">Hira FMCG</p>
      <h1>Admin Login</h1>
      <p>Use the secure dashboard to manage products, homepage storytelling, contact details, and incoming leads.</p>
      <?php if ($error !== ''): ?>
        <div class="flash error"><?php echo e($error); ?></div>
      <?php endif; ?>
      <form method="post">
        <div class="field">
          <label for="username">Username</label>
          <input id="username" type="text" name="username" required>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" type="password" name="password" required>
        </div>
        <input type="submit" value="Login">
      </form>
      <p class="hint">Default first-time credentials: <strong>admin</strong> / <strong>admin123</strong></p>
    </section>
  </div>
</body>
</html>
