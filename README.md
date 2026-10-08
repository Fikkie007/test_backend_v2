# Product management API

Laravel 13 REST API for username authentication and product management. The API
uses PostgreSQL in deployment, Laravel Sanctum for access tokens, and a separate
hashed refresh-token table.

## Requirements

- PHP 8.5 or later
- Composer 2.10 or later
- PostgreSQL 18 for the application database
- Docker and Docker Compose for the container workflow

## Local setup

Run these commands from the project root:

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

Set these values in `.env` when you use PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=product_api
DB_USERNAME=product_api
DB_PASSWORD=product_api
CORS_ALLOWED_ORIGINS=http://localhost:3000
```

The test configuration uses an in-memory SQLite database. The API defaults to
`http://localhost:8000` when you run `php artisan serve`.

## Docker setup

Start the application and PostgreSQL with:

```bash
docker compose up --build
```

The application listens on `http://localhost:8000`. Docker Compose creates the
`product_api` database and runs migrations when the application starts.

## Authentication flow

1. Register with a unique username and password.
2. Log in to receive an access token and refresh token.
3. Send the access token as `Authorization: Bearer your_access_token_here`.
4. Send the refresh token to `/api/auth/refresh` before the access token expires.
5. Replace both tokens with the rotated values from the refresh response.

Access tokens expire after 60 minutes. Refresh tokens expire after 30 days and are
stored only as SHA-256 hashes. Logout revokes the current access token and the
user's active refresh tokens.

## API endpoints

| Method | Path | Auth | Purpose |
| --- | --- | --- | --- |
| POST | `/api/auth/register` | No | Create a user |
| POST | `/api/auth/login` | No | Create access and refresh tokens |
| POST | `/api/auth/refresh` | No | Rotate the refresh token |
| POST | `/api/auth/logout` | Yes | Revoke the current session |
| GET | `/api/products` | No | List products |
| GET | `/api/products/{product}` | No | Show one product |
| POST | `/api/products` | Yes | Create a product |
| PUT | `/api/products/{product}` | Yes | Replace product fields |
| DELETE | `/api/products/{product}` | Yes | Delete a product |

## Request examples

Register:

```json
{
  "username": "Api_User",
  "password": "password123",
  "password_confirmation": "password123"
}
```

Create a product:

```json
{
  "title": "Awesome T-Shirt",
  "price": 99.99,
  "description": "Cotton shirt",
  "category": "Clothes",
  "images": ["2026/10/08/tshirt-front.jpg"]
}
```

Image values are metadata paths. The API does not upload or delete binary files.
Each path must match `YYYY/MM/DD/filename`. Paths containing traversal segments,
URLs, leading slashes, or backslashes are rejected.

On `PUT`, `title`, `price`, and `category` are required. Omitting `images` keeps
the current image metadata. Sending `images` replaces the full image list.

Product lists accept these query parameters:

- `search` filters the title
- `category` applies an exact category match
- `page` selects the page number
- `limit` sets the page size from 1 to 100; the default is 10

## Response format

Successful responses use this shape:

```json
{
  "success": true,
  "code": 200,
  "message": "Product retrieved successfully",
  "data": {},
  "meta": {}
}
```

Validation errors use HTTP `400` and include field messages. Duplicate usernames
return `409`. Invalid credentials, invalid refresh tokens, and missing authentication
return `401`. Missing products return `404`. Successful deletes return `204` with no
response body.

## Swagger documentation

Generate the OpenAPI document with:

```bash
php artisan l5-swagger:generate
```

Open Swagger UI at `/api/documentation` after starting the application.

## Testing

Run the feature and unit tests with:

```bash
php artisan test
```

The feature tests cover authentication, refresh-token rotation and replay
rejection, protected product mutations, pagination, audit attribution, image
retention and replacement, validation, path traversal rejection, case-sensitive
login, and delete responses.

## Scope

The project does not include a frontend, binary uploads, image processing, roles,
ownership restrictions, soft deletes, caching, or rate limiting. Product image
records store paths and audit metadata in the polymorphic `files` table.
