<?php
declare(strict_types=1);

/**
 * Minimal dotenv loader for local PHP runs.
 * Hosting providers such as Render should inject environment variables directly.
 */
function load_local_environment(): void
{
    foreach ([dirname(__DIR__) . '/.env.local', dirname(__DIR__) . '/.env'] as $file) {
        if (!is_readable($file)) {
            continue;
        }
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            continue;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }
            $separator = strpos($line, '=');
            if ($separator === false) {
                continue;
            }
            $key = trim(substr($line, 0, $separator));
            $value = trim(substr($line, $separator + 1));
            if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $key)) {
                continue;
            }
            if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
                $quote = $value[0];
                $value = substr($value, 1, -1);
                if ($quote === '"') {
                    $value = stripcslashes($value);
                }
            } else {
                $comment = strpos($value, ' #');
                if ($comment !== false) {
                    $value = rtrim(substr($value, 0, $comment));
                }
            }
            if (getenv($key) === false) {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}
load_local_environment();
