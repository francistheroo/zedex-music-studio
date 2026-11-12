<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit("Not found\n");
}
require_once __DIR__ . '/../config/database.php';
try {
    $pdo = database_connection();
    $row = $pdo->query('SELECT current_database() AS database_name, current_user AS database_user, version() AS server_version')->fetch();
    $tables = $pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'public' AND table_name IN ('admins', 'login_attempts', 'releases', 'release_artwork')")->fetchColumn();
    fwrite(STDOUT, "Neon PostgreSQL connection: OK\n");
    fwrite(STDOUT, "Database: " . $row['database_name'] . "\n");
    fwrite(STDOUT, "Database user: " . $row['database_user'] . "\n");
    fwrite(STDOUT, "Expected application tables found: " . $tables . "/3\n");
    fwrite(STDOUT, "PostgreSQL: " . strtok((string)$row['server_version'], "\n") . "\n");
    if ((int)$tables !== 4) {
        fwrite(STDERR, "Run database/schema.sql in the Neon SQL Editor, then repeat this check.\n");
        exit(2);
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Neon database check failed: " . $e->getMessage() . "\n");
    exit(1);
}
