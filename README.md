# Mini-projet web: Iran Info (NestJS + React + PostgreSQL)
## ETU003123 - ETU003142

## Stack
- Backend: NestJS (TypeScript)
- Frontend unique: React + Vite (pages publiques + admin)
- Base de donnees: PostgreSQL
- Orchestration: Docker Compose

## Demarrage rapide
1. Lancer les services:
   - `docker compose up --build -d`
2. Ouvrir:
   - Application web (public + admin): `http://localhost:5173`
   - API health: `http://localhost:3000/api/health`

## Compte admin par defaut
- username: `admin`
- password: `admin123`

## Base de donnees
Le schema SQL est initialise automatiquement au premier demarrage PostgreSQL via:
- `sql/news_schema.sql`

Note: les scripts de `docker-entrypoint-initdb.d` ne sont executes que lors de l'initialisation d'un nouveau volume.
