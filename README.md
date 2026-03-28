# Mini-projet web: Iran Info (NestJS + React + PostgreSQL)

## Stack
- Backend: NestJS (TypeScript)
- FrontOffice: React + Vite
- BackOffice: React + Vite
- Base de donnees: PostgreSQL
- Orchestration: Docker Compose

## Demarrage rapide
1. Copier `.env.example` vers `.env`
2. Lancer:
   - `docker compose up --build`
3. Ouvrir:
   - FrontOffice: `http://localhost:5173`
   - BackOffice: `http://localhost:5174`
   - API health: `http://localhost:3000/api/health`

## Pourquoi cette stack
- Simple pour un mini-projet et rapide a coder en TypeScript partout.
- Docker assure un environnement reproductible pour toute l'equipe.
- NestJS structure le backend proprement (modules, services, controllers).
- React separe clairement site public et admin.

## SEO et URL rewriting
- FrontOffice utilise des routes lisibles (`/article/:slug`).
- Le backend expose des slugs SEO (`/api/articles/:slug`).
- Pour un SEO maximal en production, faire un pre-render du FrontOffice (ou evoluer vers Next.js pour SSR).
