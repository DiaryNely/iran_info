# Iran Info - PHP Native

Base de migration Java Servlet/JSP vers PHP natif (sans framework).

## Arborescence

- `public/` front controller + assets + `.htaccess`
- `app/Core/` routeur et utilitaires coeur
- `app/Controllers/` controleurs web/api/backoffice
- `app/Views/` templates PHP
- `routes/` declaration des routes
- `config/` configuration application et base de donnees
- `storage/` uploads et logs

## Rewriting

Deux cas:

1. DocumentRoot pointe vers `backend-php/public`:
   - Le fichier `public/.htaccess` suffit.
2. DocumentRoot pointe vers `backend-php/`:
   - Le fichier racine `.htaccess` redirige vers `public/`.

## Lancer en local (dev rapide)

Depuis `backend-php`:

```bash
php -S localhost:8081 -t public
```

## Lancer en conteneur (recommande pour test)

Depuis la racine du projet:

```bash
docker compose up --build -d backend-php
```

Application PHP disponible sur:

- `http://localhost:8081/`
- `http://localhost:8081/api/articles`

## Prochaines etapes migration

1. Reprendre les DAO Java en repositories PDO
2. Migrer l'auth (session + JWT)
3. Migrer les routes API `/api/*`
4. Migrer le front-office (home + detail)
5. Migrer le backoffice (login, dashboard, CRUD)
