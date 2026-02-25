# Analytica

SaaS d’analyse de documents bancaires (MVP) pour notaires et particuliers.

## Stack
- Laravel 11 (PHP 8.4)
- Blade + Alpine (Breeze)
- PostgreSQL + Redis (queues)
- Stockage S3 compatible (MinIO en local) avec chiffrement applicatif AES-256-GCM + hash SHA256
- Stripe (Laravel Cashier)

## Démarrage (dev)
- `docker compose up -d --build`
- App: http://localhost:8080
- Mailpit: http://localhost:8026
- MinIO console: http://localhost:9001

## MVP (déjà codé)
- Auth (Breeze) + rôle à l’inscription (`particulier` / `pro`)
- Dossiers: création + ajout comptes + upload relevés
- Import async (queue) CSV + normalisation + détection doublons (index unique)
- Moteur d’analyse (règles R1–R6) + score global dossier
- Dashboard dossier minimal + génération rapport PDF (DomPDF) chiffré au stockage
