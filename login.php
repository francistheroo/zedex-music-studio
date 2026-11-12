<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

if (current_admin()) {
    redirect_to('index.php');
}

$error = '';
$hasAdmin = false;
try {
    $hasAdmin = (int)database_connection()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
            $error = 'Enter your admin email and password.';
        } elseif (login_is_throttled($email)) {
            $error = 'Too many attempts. Please wait 15 minutes before trying again.';
        } else {
            $stmt = database_connection()->prepare('SELECT id, password_hash FROM admins WHERE email = :email LIMIT 1');
            $stmt->execute(['email' => $email]);
            $row = $stmt->fetch();
            if ($row && password_verify($password, (string)$row['password_hash'])) {
                if (password_needs_rehash((string)$row['password_hash'], PASSWORD_DEFAULT)) {
                    $update = database_connection()->prepare('UPDATE admins SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
                    $update->execute(['hash' => password_hash($password, PASSWORD_DEFAULT), 'id' => (int)$row['id']]);
                }
                clear_failed_logins($email);
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$row['id'];
                redirect_to('index.php');
            }
            record_failed_login($email);
            $error = 'The email or password is incorrect.';
        }
    }
} catch (Throwable $e) {
    error_log('Zedex login error: ' . $e->getMessage());
    $error = 'The database is not ready. Complete the setup steps in the project README.';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#090b12"><title>Admin Login · Zedex Music Studio</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="auth-page">
  <main class="auth-card">
    <a class="brand auth-brand" href="login.php"><span class="brand-mark">Z</span><span>ZEDEX<span class="brand-sub">MUSIC STUDIO</span></span></a>
    <div class="eyebrow">PRIVATE WORKSPACE</div>
    <h1>Welcome back</h1>
    <p class="auth-copy">Sign in to manage your music releases.</p>
    <?php if (!$hasAdmin && !$error): ?><div class="notice">Admin account not created yet. Follow the one-time setup instructions in README.md.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="form-error" role="alert"><?= h($error) ?></div><?php endif; ?>
    <form method="post" action="login.php" autocomplete="on">
      <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
      <label for="email">Admin email</label>
      <input id="email" name="email" type="email" maxlength="254" autocomplete="username" required>
      <label for="password">Password</label>
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button class="primary-button auth-submit" type="submit">Sign in <span>↗</span></button>
    </form>
    <p class="auth-foot">Authorised admin access only.</p>
  </main>
</body>
</html>
