# Mini-projet web: Iran Info (PHP + Java legacy + PostgreSQL)
## ETU003123 - ETU003142

## Stack actuelle
- Base de donnees: PostgreSQL 16
- Backend PHP (principal): PHP natif (Apache + PDO)
- Orchestration: Docker Compose

## Services Docker Compose
- `db`: PostgreSQL (schema initialise par `sql/news_schema.sql`)
- `backend-php`: application PHP principale


## Structure du projet
- `backend-php/`: application PHP (routes web/api, frontoffice, backoffice)
- `sql/news_schema.sql`: schema SQL + seed admin
- `docker-compose.yml`: orchestration locale

## Demarrage rapide
1. Lancer tous les services:
   - `docker compose up --build -d`
2. URLs utiles:
   - PHP frontoffice: `http://localhost:8081/`
   - PHP article: `http://localhost:8081/article/{slug}`
   - PHP backoffice login: `http://localhost:8081/backoffice/login`
   - PHP API articles: `http://localhost:8081/api/articles`
   - Java API health (legacy): `http://localhost:3000/api/health` (selon votre `.env`)

## Compte admin par defaut
- email: `admin@iran.local`
- mot de passe: `admin123`

Le compte admin est seed directement dans `sql/news_schema.sql` (insert SQL idempotent).
Cela permet au backoffice PHP de fonctionner sans dependre de l'auto-creation presente dans le backend Java.

## Notes base de donnees
- Le schema SQL est execute au premier demarrage de la base (volume vide).
- Les scripts montes dans `docker-entrypoint-initdb.d` ne sont pas rejoues si le volume existe deja.
- Si vous modifiez `sql/news_schema.sql`, recreez le volume Postgres pour reappliquer l'initialisation.

Exemple reset local complet:
- `docker compose down --volumes --remove-orphans`
- `docker compose up --build -d`


