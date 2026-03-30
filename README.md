# Mini-projet web: Iran Info (Java Servlet/JSP + React + PostgreSQL)
## ETU003123 - ETU003142

## Stack
- Backend: Java 17, Servlet/JSP (Tomcat), JWT
- Frontoffice: React + Vite
- Backoffice: JSP (rendu serveur) + appels API
- Base de donnees: PostgreSQL
- Orchestration: Docker Compose

## Structure
- `backend-java/`: API Java + pages JSP backoffice
- `frontoffice/`: application publique React
- `sql/news_schema.sql`: initialisation de la base

## Demarrage rapide
1. Lancer les services:
   - `docker compose up --build -d`
2. Ouvrir:
   - Backoffice login: `http://localhost:3000/backoffice/login`
   - API health: `http://localhost:3000/api/health`


## Compte admin par defaut
- email: `admin@iran.local`
- password: `admin123`

Le compte admin est auto-cree lors de la premiere connexion backoffice avec ces identifiants.

## Notes base de donnees
- Le schema est initialise au premier demarrage via `sql/news_schema.sql`.
- Les scripts `docker-entrypoint-initdb.d` ne sont executes que lors de l'initialisation d'un nouveau volume.
- Si vous changez le schema, recreez le volume Postgres pour reappliquer l'init.


