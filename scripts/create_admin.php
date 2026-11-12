<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}
require_once __DIR__ . '/../config/database.php';

function prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $value = fgets(STDIN);
    return trim($value === false ? '' : $value);
}

function hidden_prompt(string $label): string
{
    fwrite(STDOUT, $label);
    $canHide = DIRECTORY_SEPARATOR === '/' && function_exists('shell_exec');
    if ($canHide) {
        @shell_exec('stty -echo');
    }
    $value = fgets(STDIN);
    if ($canHide) {
        @shell_exec('stty echo');
        fwrite(STDOUT, PHP_EOL);
    }
    return trim($value === false ? '' : $value);
}

try {
    $pdo = database_connection();
    if ((int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) {
        fwrite(STDERR, "An admin already exists. Initial setup is locked.\n");
        exit(1);
    }
    $email = strtolower(prompt('Admin email: '));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Please enter a valid email address.');
    }
    $password = hidden_prompt('Password (minimum 12 characters): ');
    $confirm = hidden_prompt('Confirm password: ');
    if (strlen($password) < 12) {
        throw new RuntimeException('Use a password with at least 12 characters.');
    }
    if (!hash_equals($password, $confirm)) {
        throw new RuntimeException('The passwords do not match.');
    }
    $stmt = $pdo->prepare('INSERT INTO admins (email, password_hash) VALUES (:email, :hash)');
    $stmt->execute(['email' => $email, 'hash' => password_hash($password, PASSWORD_DEFAULT)]);
    fwrite(STDOUT, "Admin account created. You can now sign in at /login.php.\n");
} catch (Throwable $e) {
    fwrite(STDERR, 'Setup failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
