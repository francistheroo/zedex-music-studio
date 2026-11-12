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
    // Keep artwork in Neon PostgreSQL so it survives Render container restarts and redeploys.
    $pdo->exec("CREATE TABLE IF NOT EXISTS release_artwork (
        release_id BIGINT PRIMARY KEY REFERENCES releases(id) ON DELETE CASCADE,
        mime_type VARCHAR(40) NOT NULL,
        image_data TEXT NOT NULL,
        updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
    )");
    // Upgrade safely if an earlier deployment created this column as BYTEA.
    $pdo->exec("DO \$\$
    BEGIN
        IF EXISTS (
            SELECT 1 FROM information_schema.columns
            WHERE table_schema = current_schema()
              AND table_name = 'release_artwork'
              AND column_name = 'image_data'
              AND data_type = 'bytea'
        ) THEN
            ALTER TABLE release_artwork
            ALTER COLUMN image_data TYPE TEXT USING encode(image_data, 'base64');
        END IF;
    END
    \$\$");
}
