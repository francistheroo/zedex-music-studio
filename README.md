# Zedex Music Studio

A personal music-release workspace built with PHP 8.3, Apache and Neon PostgreSQL.

## Current stage
- Responsive dark dashboard with working Overview, My Releases and Distribution navigation
- Release manager with add, edit and delete actions, release dates, status and notes
- JPG, PNG and WEBP cover-art upload (up to 5 MB), stored in Neon PostgreSQL
- Distribution guide with official distributor links, target-platform selection and per-release submission tracking
- Neon PostgreSQL connection through PHP PDO_PGSQL
- Admin sign-in with password hashing, CSRF protection, secure session cookies and login throttling
- PostgreSQL schema for admins, login attempts and music releases
- CLI scripts for one-time admin creation and database verification
- Docker image for Render

## 1. Set up Neon

From the repository root, install the Neon CLI:

```bash
npm i -g neon@latest
```

If global installation is unavailable, use `npx neon@latest` instead of `neon` for each CLI command.

Initialise this app with the selected Neon project:

```bash
neon init -y --org-id org-icy-sound-10008060 --project-id rapid-hat-49426289 --branch production --services postgres
```

If the CLI opens a browser sign-in, finish signing in there. If sign-in times out or fails, run `neon login` and retry.

Deploy the Neon configuration and pull its environment variables:

```bash
neon deploy
```

The CLI creates/updates `neon.ts` and pulls local environment values into `.env.local` (or `.env`). These files are ignored by Git. Do not commit them.

## 2. Create the database tables

Open the Neon SQL Editor for the selected project and run the complete contents of:

`database/schema.sql`

This creates:
- `admins` — the private admin account
- `login_attempts` — temporary login throttling
- `releases` — music release metadata and distribution tracking
- `release_artwork` — cover artwork stored persistently in Neon PostgreSQL

## 3. Configure the PHP app

The app uses PHP PDO with the `pdo_pgsql` extension. It reads Neon `DATABASE_URL` from the process environment. For local development, `config/load_env.php` also reads the CLI-generated `.env.local` or `.env`.

For Render, add the Neon `DATABASE_URL` as a **secret environment variable** in the Render service settings. Copy it from the Neon connection details; use the pooled connection string for a web app. Do not paste it into source code, GitHub, or chat.

The Docker image installs `pdo_pgsql`; no Node runtime or JavaScript build is required by this PHP app.

## 4. Create your admin account

After the database schema is applied and the app has access to `DATABASE_URL`, run this from the repository root in a trusted terminal:

```bash
php scripts/create_admin.php
```

This is a one-time CLI-only setup. It asks for an admin email and a password of at least 12 characters. The password is stored as a password hash. The script refuses to create another admin once one exists.

Then sign in at `/login.php`.

## 5. Verify the connection

Run:

```bash
php scripts/check_database.php
```

A successful check reports the Neon database, connected user, PostgreSQL version and confirms the application tables exist.

Check PHP syntax before deployment:

```bash
find . -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
```

There is currently no `npm run build` script because this is a PHP application, not a Node application. The PHP lint and database checks above are the appropriate checks.

## 6. Deploy on Render

1. Create a Render **Web Service** connected to this GitHub repository.
2. Choose **Docker** as the runtime.
3. Add `DATABASE_URL` from Neon as a secret environment variable.
4. Deploy the `main` branch.
5. Create the admin using the Render Shell if available, or run the CLI setup locally with the same Neon connection string in a trusted terminal.
6. Verify the site at `/login.php`.

## Important

This website organises release information. It is not a music distributor and cannot independently submit tracks to Instagram/Meta, Spotify, Apple Music or YouTube Music. Use an authorised distributor for delivery to those platforms.

Never commit passwords, API keys, database credentials, private audio, or access tokens.
