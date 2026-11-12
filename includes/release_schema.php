<?php
declare(strict_types=1);

/** Keep the releases table compatible with the current and expanded studio UI. */
function ensure_release_schema(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS releases (
        id BIGSERIAL PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        artist_name VARCHAR(200) NOT NULL,
        release_type VARCHAR(30) NOT NULL DEFAULT 'single',
        status VARCHAR(30) NOT NULL DEFAULT 'draft',
        release_date DATE NULL,
        artwork_path TEXT NULL,
        notes TEXT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    )");
    $pdo->exec("ALTER TABLE releases ADD COLUMN IF NOT EXISTS distributor_name VARCHAR(200) NULL");
    $pdo->exec("ALTER TABLE releases ADD COLUMN IF NOT EXISTS distributor_url TEXT NULL");
    $pdo->exec("ALTER TABLE releases ADD COLUMN IF NOT EXISTS submitted_at DATE NULL");
    $pdo->exec("ALTER TABLE releases ADD COLUMN IF NOT EXISTS platforms TEXT NULL");
}
