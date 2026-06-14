<?php

declare(strict_types=1);

function seed_default_admin(): void
{
    $pdo = get_db();
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM admin_users');
    $row = $stmt->fetch();

    if ((int) ($row['total'] ?? 0) > 0) {
        return;
    }

    $insert = $pdo->prepare('INSERT INTO admin_users (username, password) VALUES (:username, :password)');
    $insert->execute([
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
    ]);
}

function attempt_admin_login(string $username, string $password): bool
{
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = :username LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password'])) {
        return false;
    }

    $_SESSION['admin_user'] = [
        'id' => (int) $user['id'],
        'username' => $user['username'],
    ];

    return true;
}

function is_admin_logged_in(): bool
{
    return isset($_SESSION['admin_user']['id']);
}

function require_admin_login(): void
{
    if (is_admin_logged_in()) {
        return;
    }

    header('Location: /admin/index.php');
    exit;
}

function admin_user(): ?array
{
    return $_SESSION['admin_user'] ?? null;
}

function admin_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}
