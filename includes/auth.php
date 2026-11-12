<?php
declare(strict_types=1);

require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/database.php';

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) {
        return null;
    }
    $stmt = database_connection()->prepare('SELECT id, email, created_at FROM admins WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => (int)$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        unset($_SESSION['admin_id']);
        return null;
    }
    return $admin;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) {
        redirect_to('login.php');
    }
    return $admin;
}

function login_is_throttled(string $email): bool
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $secret = getenv('APP_KEY') ?: 'zedex-login-throttle-change-this-key';
    $ipHash = hash_hmac('sha256', $ip, $secret);
    $stmt = database_connection()->prepare(
        'SELECT locked_until FROM login_attempts WHERE email = :email AND ip_hash = :ip_hash'
    );
    $stmt->execute(['email' => $email, 'ip_hash' => $ipHash]);
    $row = $stmt->fetch();
    return $row && $row['locked_until'] !== null && strtotime((string)$row['locked_until']) > time();
}

function record_failed_login(string $email): void
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $secret = getenv('APP_KEY') ?: 'zedex-login-throttle-change-this-key';
    $ipHash = hash_hmac('sha256', $ip, $secret);
    $pdo = database_connection();
    $stmt = $pdo->prepare(
        "INSERT INTO login_attempts (email, ip_hash, attempts, window_started, locked_until)
         VALUES (:email, :ip_hash, 1, NOW(), NULL)
         ON CONFLICT (email, ip_hash) DO UPDATE SET
           attempts = CASE WHEN login_attempts.window_started < NOW() - INTERVAL '15 minutes'
                           THEN 1 ELSE login_attempts.attempts + 1 END,
           window_started = CASE WHEN login_attempts.window_started < NOW() - INTERVAL '15 minutes'
                                 THEN NOW() ELSE login_attempts.window_started END,
           locked_until = CASE WHEN
             (CASE WHEN login_attempts.window_started < NOW() - INTERVAL '15 minutes'
                   THEN 1 ELSE login_attempts.attempts + 1 END) >= 5
             THEN NOW() + INTERVAL '15 minutes' ELSE NULL END"
    );
    $stmt->execute(['email' => $email, 'ip_hash' => $ipHash]);
}

function clear_failed_logins(string $email): void
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $secret = getenv('APP_KEY') ?: 'zedex-login-throttle-change-this-key';
    $stmt = database_connection()->prepare('DELETE FROM login_attempts WHERE email = :email AND ip_hash = :ip_hash');
    $stmt->execute(['email' => $email, 'ip_hash' => hash_hmac('sha256', $ip, $secret)]);
}
