# Mini-projet web: Iran Info (Java Servlet/JSP + PostgreSQL)
## ETU003123 - ETU003142

## Stack
- Backend: Java 17, Servlet/JSP (Tomcat), JWT
- Frontoffice: JSP (rendu serveur) + JavaScript vanilla
- Backoffice: JSP (rendu serveur) + appels API
- Base de donnees: PostgreSQL
- Orchestration: Docker Compose

## Structure
- `backend-java/`: API Java + pages JSP backoffice
- `backend-java/src/main/webapp/frontoffice/`: pages frontoffice JSP
- `sql/news_schema.sql`: initialisation de la base

## Demarrage rapide
1. Lancer les services:
   - `docker compose up --build -d`
2. Ouvrir:
   - Frontoffice: `http://localhost:5173/`
   - Backoffice login: `http://localhost:5174/backoffice/login`
   - API health: `http://localhost:5173/api/health`

Les 2 conteneurs frontoffice et backoffice sont separes, mais executent la meme application JSP (webapp Tomcat), sans React.


## Compte admin par defaut
- email: `admin@iran.local`
- password: `admin123`

Le compte admin est auto-cree lors de la premiere connexion backoffice avec ces identifiants.

## Notes base de donnees
- Le schema est initialise au premier demarrage via `sql/news_schema.sql`.
- Les scripts `docker-entrypoint-initdb.d` ne sont executes que lors de l'initialisation d'un nouveau volume.
- Si vous changez le schema, recreez le volume Postgres pour reappliquer l'init.


