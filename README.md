# Zedex Music Studio

A personal music-release workspace built with PHP 8.3 and Apache.

## Current stage
- Responsive dark dashboard starter
- Apache/PHP Docker image for Render
- No audio files are stored in this repository
- Release management, secure login and database integration are planned next

## Run locally with Docker
```bash
docker build -t zedex-music-studio .
docker run --rm -p 8080:80 zedex-music-studio
```
Open http://localhost:8080.

## Deploy on Render
1. Create a new **Web Service** and connect this repository.
2. Choose **Docker** as the runtime.
3. Deploy the `main` branch.
4. Add a database before enabling accounts or saving release data.

## Important
This website organises release information. It is not a music distributor and cannot independently submit tracks to Instagram/Meta, Spotify, Apple Music or YouTube Music. Use an authorised distributor for delivery to those platforms.

Do not commit passwords, API keys, database credentials, private audio, or access tokens.
