-- Run this file once against your Neon PostgreSQL database.
CREATE TABLE IF NOT EXISTS admins (
    id BIGSERIAL PRIMARY KEY,
    email VARCHAR(254) NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS login_attempts (
    email VARCHAR(254) NOT NULL,
    ip_hash CHAR(64) NOT NULL,
    attempts INTEGER NOT NULL DEFAULT 0,
    window_started TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    locked_until TIMESTAMPTZ NULL,
    PRIMARY KEY (email, ip_hash)
);

CREATE TABLE IF NOT EXISTS releases (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    artist_name VARCHAR(200) NOT NULL,
    release_type VARCHAR(30) NOT NULL DEFAULT 'single',
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    release_date DATE NULL,
    artwork_path TEXT NULL,
    notes TEXT NULL,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT releases_type_check CHECK (release_type IN ('single', 'ep', 'album')),
    CONSTRAINT releases_status_check CHECK (status IN ('draft', 'preparing', 'submitted', 'scheduled', 'released'))
);

CREATE INDEX IF NOT EXISTS releases_release_date_idx ON releases (release_date);
CREATE INDEX IF NOT EXISTS releases_status_idx ON releases (status);
