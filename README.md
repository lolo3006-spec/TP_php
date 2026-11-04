# API de gestion de films

API REST Symfony pour créer, consulter, rechercher, modifier et supprimer des films.
Les données sont stockées dans une base SQLite locale.

## Installation et lancement

Depuis le dossier du projet :

```bash
composer install
php bin/console doctrine:migrations:migrate
symfony serve
```

Le serveur indique l'adresse locale à ouvrir, généralement `http://127.0.0.1:8000`.

## Utilisation

Les routes de films sont préfixées par `/api/movies`. Les requêtes peuvent être
envoyées avec un client HTTP comme l'extension REST Client de VS Code, en utilisant
les exemples du fichier [`api.http`](./api.http).

### Lister et rechercher des films

```http
GET /api/movies
GET /api/movies?title=odyss
GET /api/movies?year=2026
GET /api/movies?page=2&limit=10
GET /api/movies?title=odyss&year=2026&page=1&limit=10
```

La recherche par titre ne distingue pas les majuscules et cherche une partie du
titre. La pagination commence à la page 1 ; `limit` accepte une valeur de 1 à 100.
La réponse contient `total`, `lastPage` et `items`.

### Ajouter un film

Envoyer une requête `POST` à `/api/movies` avec un corps JSON :

```http
POST /api/movies
Content-Type: application/json

{
  "title": "Le Voyage de Chihiro",
  "description": "Une jeune fille découvre un monde mystérieux.",
  "year": 2001
}
```

Le titre et la description sont obligatoires (255 caractères maximum). L'année
doit être comprise entre 1888 et 2100. L'identifiant est attribué automatiquement.
Une création réussie renvoie le statut `201 Created`.

### Consulter, modifier et supprimer un film

```http
GET /api/movies/1
PATCH /api/movies/1
Content-Type: application/json

{
  "title": "Nouveau titre"
}

DELETE /api/movies/1
```

La modification `PATCH` est partielle : seuls les champs fournis sont changés.
La suppression réussie renvoie `204 No Content`.

`GET /movies` redirige vers la liste de films (`/api/movies`). Pour les créations
et les autres opérations, utiliser les routes `/api/movies`.

## Tests

```bash
php bin/phpunit
```

Les tests fonctionnels utilisent une base SQLite dédiée à l'environnement `test`.

## Limites actuelles

L'authentification des utilisateurs, les notes des films et leur feed ne sont pas
encore implémentés.

## Utilisation de l'IA

J'ai dû me servir de l'IA car localhost:8000 n'affichait pas mes retombés, comme je ne comprennais pourquoi, j'ai demandé à l'IA de m'aider.
