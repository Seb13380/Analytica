# Analytica (dev)

## Prérequis
- Docker Desktop (Linux containers)

## Démarrer
- `docker compose up -d --build`
- Installer deps front (si besoin): le service `node` lance `npm install` puis `npm run dev`.

## Commandes utiles
- Artisan: `docker compose exec app php artisan`
- Tinker: `docker compose exec app php artisan tinker`
- Composer: `docker compose exec app composer`
- Migrations: `docker compose exec app php artisan migrate`

## URLs
- App: http://localhost:8080
- Mailpit: http://localhost:8025
- MinIO console: http://localhost:9001
