# Task Manager API (Laravel + Eloquent)

Backend API egy task/issue tracker rendszerhez.

Fobb modulok:
- Auth (register/login/refresh/logout/me)
- Projects (CRUD + tagsagkezeles)
- Tasks (CRUD + szures + lapozas)
- Comments (task kommentek CRUD)

## Technologia
- PHP 8.3+
- Laravel 13
- Eloquent ORM
- PostgreSQL
- JWT auth (custom guard)
- Docker Compose (lokalis adatbazishoz)

## Teljes Setup (lepesrol lepesre)

## 1. Elofeltetelek
- PHP 8.3+
- Composer 2+
- Node.js 22+
- npm 10+
- Docker Desktop + Docker Compose

## 2. Projekt klonozasa
```bash
git clone <repo-url>
cd Laravel-Eloquent
```

## 3. Fuggosegek telepitese
```bash
composer install
npm install
```

## 4. Kornyezeti valtozok beallitasa
Hozz letre egy `.env` fajlt a `.env.example` alapjan.

```bash
cp .env.example .env
```

Kotelezo valtozok:
- `APP_KEY`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `JWT_SECRET`
- `JWT_TTL`
- `JWT_REFRESH_TTL`
- `JWT_ISSUER`
- `CORS_ALLOWED_ORIGINS`

Minimalis pelda:
```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=laravel-eloquent
DB_USERNAME=laravel
DB_PASSWORD=laravel

JWT_SECRET="replace-with-strong-secret"
JWT_TTL=900
JWT_REFRESH_TTL=1209600
JWT_ISSUER=task-manager-api

CORS_ALLOWED_ORIGINS=http://localhost:3000,http://127.0.0.1:3000
```

Generald az APP key-t:
```bash
php artisan key:generate
```

## 5. PostgreSQL inditasa Dockerrel
```bash
docker compose up -d postgres
```

Ellenorzes:
```bash
docker compose ps
```

## 6. Migracio futtatasa
```bash
php artisan migrate
```

## 7. Alkalmazas inditasa
Fejlesztoi mod (Laravel API):
```bash
php artisan serve
```

Komplett local dev folyamat (PHP server + queue listener + log tail + Vite):
```bash
composer dev
```

Dockeres API futtatas (opcionalis):
```bash
docker compose up -d app
```

## 8. Dokumentacio ellenorzese
- API root health-check: `GET http://localhost:8000/api/`
- Laravel health endpoint: `GET http://localhost:8000/up`
- Postman collectionok: `postman/` mappa iteracionkent

Megjegyzes:
- Swagger UI (`/docs`) es OpenAPI JSON (`/docs-json`) jelenleg nincs kulon route-kent bekotve ebben a Laravel projektben.

## 9. Minosegi ellenorzesek
```bash
composer lint
composer analyse
composer test
composer quality
```

## Auth hasznalat roviden
1. `POST /auth/register` vagy `POST /auth/login`
2. `accessToken` megy `Authorization: Bearer <token>` headerben
3. Lejart access token eseten `POST /auth/refresh`
4. Kijelentkezes: `POST /auth/logout`

## API Dokumentacio

## Base URL
- `http://localhost:8000/api`

## Hitelesites
- A vedett endpointok `Authorization: Bearer <accessToken>` headert varnak.

## Endpoint lista (minden vegpont)

| Method | Endpoint | Auth | Leiras |
|---|---|---|---|
| GET | `/` | No | Egyszeru health-check jellegu valasz |
| POST | `/auth/register` | No | Uj user regisztracio |
| POST | `/auth/login` | No | Bejelentkezes |
| POST | `/auth/refresh` | No | Token frissites refresh tokennel |
| POST | `/auth/logout` | Yes | Kijelentkezes + refresh token hash torles |
| GET | `/auth/me` | Yes | Aktualis bejelentkezett user payload |
| POST | `/projects` | Yes | Projekt letrehozas |
| GET | `/projects` | Yes | Projektek listazasa (owner/member) |
| GET | `/projects/{id}` | Yes | Egy projekt lekerdezese |
| PATCH | `/projects/{id}` | Yes | Projekt modositasa (owner) |
| DELETE | `/projects/{id}` | Yes | Projekt torlese (owner) |
| POST | `/projects/{id}/members` | Yes | Tag hozzaadasa projekthez (owner) |
| DELETE | `/projects/{id}/members/{memberUserId}` | Yes | Tag eltavolitasa projektbol (owner) |
| POST | `/projects/{projectId}/tasks` | Yes | Task letrehozas projektben |
| GET | `/projects/{projectId}/tasks` | Yes | Task lista szuressel/lapozassal |
| GET | `/projects/{projectId}/tasks/{taskId}` | Yes | Egy task lekerdezese |
| PATCH | `/projects/{projectId}/tasks/{taskId}` | Yes | Task modositasa |
| DELETE | `/projects/{projectId}/tasks/{taskId}` | Yes | Task torlese |
| POST | `/tasks/{taskId}/comments` | Yes | Komment letrehozas taskhoz |
| GET | `/tasks/{taskId}/comments` | Yes | Komment lista taskhoz (lapozhato) |
| GET | `/tasks/{taskId}/comments/{commentId}` | Yes | Egy komment lekerdezese |
| PATCH | `/tasks/{taskId}/comments/{commentId}` | Yes | Komment modositasa (author vagy project owner) |
| DELETE | `/tasks/{taskId}/comments/{commentId}` | Yes | Komment torlese (author vagy project owner) |

## Request body referencia

### POST /auth/register
```json
{
  "name": "John Doe",
  "email": "user@example.com",
  "password": "Password123"
}
```

### POST /auth/login
```json
{
  "email": "user@example.com",
  "password": "Password123"
}
```

### POST /auth/refresh
```json
{
  "refreshToken": "<refresh_token>"
}
```

### POST /projects
```json
{
  "name": "My Project",
  "description": "Optional project description"
}
```

### PATCH /projects/{id}
```json
{
  "name": "Updated name",
  "description": "Updated description"
}
```

### POST /projects/{id}/members
```json
{
  "memberUserId": 2
}
```

### POST /projects/{projectId}/tasks
```json
{
  "title": "Implement endpoint",
  "description": "Optional",
  "status": "TODO",
  "priority": "HIGH",
  "assignedUserId": 2,
  "dueDate": "2031-01-01T00:00:00.000Z"
}
```

### PATCH /projects/{projectId}/tasks/{taskId}
```json
{
  "title": "Updated task",
  "status": "IN_PROGRESS",
  "priority": "MEDIUM"
}
```

### GET /projects/{projectId}/tasks query paramok
- `status`: `TODO` | `IN_PROGRESS` | `DONE`
- `priority`: `LOW` | `MEDIUM` | `HIGH`
- `assigneeId`: integer user id
- `dueFrom`: ISO date string
- `dueTo`: ISO date string
- `sortBy`: `createdAt` | `dueDate` | `priority` | `status`
- `sortOrder`: `asc` | `desc`
- `page`: integer (min: 1)
- `limit`: integer (1-100)

### POST /tasks/{taskId}/comments
```json
{
  "content": "This task needs clarification"
}
```

### PATCH /tasks/{taskId}/comments/{commentId}
```json
{
  "content": "Updated comment text"
}
```

### GET /tasks/{taskId}/comments query paramok
- `page`: integer (min: 1)
- `limit`: integer (1-100)

## Response forma

Tobb endpoint ezt hasznalja:
```json
{
  "success": true,
  "data": {}
}
```

Hibak globalisan:
```json
{
  "success": false,
  "statusCode": 400,
  "message": "...",
  "path": "/requested/path",
  "timestamp": "2026-03-28T12:00:00.000Z"
}
```

## Hasznos script parancsok
- `php artisan serve` - API futtatasa localban
- `composer dev` - teljes local dev folyamat
- `php artisan migrate` - migraciok futtatasa
- `composer test` - teszt futtatas
- `composer lint` - Pint ellenorzes
- `composer analyse` - PHPStan ellenorzes
- `composer quality` - lint + analyse + test
- `composer migrate:docker` - migracio docker artisan containerrel

## Release checklist (iteracio 8)
- `.env` validalva (DB/JWT/CORS/rate-limit)
- Migraciok sikeresen lefutnak (`php artisan migrate --force`)
- Minosegi gate zold (`composer quality`)
- Auth flow rendben (`register -> login -> me -> refresh -> logout`)
- Access policyk rendben (project owner/member szabalyok)
- Task filter/sort/pagination kombinaciok ellenorizve
- Comment jogosultsagok ellenorizve (author/project owner)
- Health endpointek valaszolnak (`/api/`, `/up`)
- Postman smoke/auth/projects/tasks/comments collectionok lefuttatva
