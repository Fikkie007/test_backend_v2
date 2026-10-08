# Product management API technical design

Status: Approved
Based on PRD version/date: Frozen PRD, 2026-10-08
Owner: Main agent
Approval: Approved by user on 2026-10-08

## Current system state

The repository currently contains the recruitment-test PDF and the frozen product
requirements. Application code has not been implemented.

Local tool facts:

- PHP 8.5.9
- Composer 2.10.3
- PostgreSQL client 18.4
- Docker is not installed locally

The target is a Laravel 13 API using PostgreSQL, Sanctum 4.3.3, L5-Swagger 11.1.0,
and Docker artefacts for the reviewer.

## Overall approach

- Use Laravel's standard HTTP, Eloquent, validation, routing, pagination, and
  authentication conventions.
- Pin `laravel/sanctum` to `^4.3` and `darkaonline/l5-swagger` to `^11.1` for the
  Laravel 13 dependency set.
- Keep the CRUD module direct: routes -> controllers -> Form Requests and models ->
  API Resources.
- Use model observers for product and file audit fields.
- Keep product and file metadata writes inside database transactions.
- Store image metadata in a polymorphic `files` table. No binary upload is in scope.
- Use a separate hashed refresh-token table because Sanctum personal access tokens
  are access tokens, not refresh tokens.

## Modules and responsibilities

| Module | Responsibility | Related FRs | Owned contracts |
| --- | --- | --- | --- |
| Auth routes/controller | Register, login, refresh, logout | FR-001 to FR-005 | Auth response and token lifecycle |
| Product routes/controller | List, detail, create, update, delete products | FR-006 to FR-013 | Product route behavior |
| Form Requests | Validate and normalize request shape without changing approved username case | FR-001, FR-003, FR-007 to FR-012, FR-017 | Validation rules |
| Product model/observer | Product persistence, relations, `created_by`, `updated_by` | FR-014 | Product audit attribution |
| File model/observer | Polymorphic metadata, path, file audit attribution | FR-015, FR-016 | File relation and path rules |
| API Resources | Serialize product, user, file paths, pagination metadata | FR-006 to FR-018 | JSON response shape |
| Exception handling | Consistent JSON errors and numeric response code | FR-010, FR-017 | Error envelope |
| Swagger | OpenAPI documentation and Bearer security scheme | FR-018 | Documentation contract |
| Docker artefacts | Laravel app and PostgreSQL services | FR-019 | Container startup contract |

## Interface contracts

### Routes

Public routes:

```text
POST /api/auth/register
POST /api/auth/login
POST /api/auth/refresh
GET  /api/products
GET  /api/products/{product}
```

Protected routes:

```text
POST   /api/auth/logout
POST   /api/products
PUT    /api/products/{product}
DELETE /api/products/{product}
```

Product routes use `Route::apiResource`, split into public read routes and protected
mutation routes.

### Product request

Create requires:

```json
{
  "title": "Awesome T-Shirt",
  "price": 99.99,
  "description": null,
  "category": "Clothes",
  "images": [
    "2026/10/08/tshirt-front.jpg"
  ]
}
```

Update requires `title`, `price`, and `category`. `description` and `images` are
optional. When `images` is omitted, existing file metadata remains unchanged. When
provided, it replaces the existing list.

### Success envelope

```json
{
  "success": true,
  "code": 200,
  "message": "Product retrieved successfully",
  "data": {},
  "meta": {}
}
```

`meta` is present for paginated list responses. A successful delete returns `204`
with no response body.

### Error envelope

```json
{
  "success": false,
  "code": 400,
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."]
  }
}
```

The HTTP response code and body `code` must match. Validation failures use `400`,
not Laravel's default `422`.

## Data model and migration

### users

Use Laravel's default users table with these changes:

- add unique `username` string, 3-50 characters;
- preserve the submitted case;
- keep `name` and set it to `username` during register;
- make `email` nullable because it is not part of the required register payload;
- keep Laravel's hashed `password` and `remember_token` behavior.

### products

```text
id              big integer primary key
title           varchar
price           numeric(15,2), non-negative
description     text, nullable
category        varchar
created_by      foreign key users.id, nullable
updated_by      foreign key users.id, nullable
created_at      timestamp
updated_at      timestamp
```

Use `nullOnDelete` for the audit user foreign keys. Product create and update are
protected by Sanctum, so normal API writes have an authenticated actor.

### files

```text
id              big integer primary key
parent_id       big integer
parent_table    varchar
path            varchar
created_by      foreign key users.id, nullable
updated_by      foreign key users.id, nullable
created_at      timestamp
updated_at      timestamp
```

Add an index on `(parent_table, parent_id)`. `parent_id` has no database foreign key
because the parent is polymorphic. `parent_table` stores an application-controlled
morph alias such as `products`; clients cannot submit it.

### refresh_tokens

```text
id              big integer primary key
user_id         foreign key users.id
token_hash      unique string
expires_at      timestamp
revoked_at      timestamp, nullable
created_at      timestamp
updated_at      timestamp
```

Only the hash is persisted. The raw token is returned once to the client.

## State transitions

### Access token

```text
issued -> active -> revoked
                 -> expired
```

### Refresh token

```text
issued -> active -> consumed/revoked
                 -> expired
```

Refresh rotation consumes the presented token and creates its replacement in one
transaction. A consumed or revoked token cannot create another access token.

### Product images

```text
product created -> file metadata rows created
product updated -> images omitted: retain rows
product updated -> images supplied: replace rows
product deleted -> related file metadata rows deleted
```

## Concurrency, consistency, and idempotency

- Product plus file metadata create/update uses a database transaction.
- Refresh rotation uses a transaction and locks or atomically updates the active
  refresh-token row before creating a replacement.
- Product update uses the bound model instance, not a bulk update, so observers run.
- Repeated delete of a missing product returns `404`.
- Repeated use of a refresh token returns `401` after the first successful rotation.
- No idempotency key is required for product mutations in the take-home scope.

## Authentication, authorization, privacy, and security

- Sanctum Bearer tokens protect product mutations and logout.
- All authenticated users may mutate all products; there is no ownership policy.
- Passwords use Laravel's password hashing.
- Access tokens expire after 60 minutes.
- Refresh tokens expire after 30 days, are hashed, and rotate on use.
- Tokens and passwords must not be logged or returned in error messages.
- Client input cannot set audit fields or polymorphic parent fields.
- Username validation allows only alphanumeric characters and underscore, without
  spaces, while preserving case.
- Image paths must match `YYYY/MM/DD/filename` and reject `..`, URLs, leading slash,
  and backslash.
- CORS uses an environment-configured origin allowlist. Wildcard is local-only.

## Failures, retry, recovery, and degradation

- Invalid input returns the agreed `400` JSON envelope.
- Invalid or missing authentication returns `401` JSON.
- Missing product returns `404` JSON.
- Duplicate username returns `409` JSON.
- Transaction failures roll back product and file metadata together.
- Refresh-token replay returns `401`; the application does not issue a successor.
- Cache and rate limiting are optional and do not block the required release.

## Performance and capacity

- Product lists use Laravel's built-in paginator with default limit 10 and maximum
  limit 100.
- Product list/detail queries eager-load `createdBy`, `updatedBy`, and `files` to
  avoid per-row relation queries.
- PostgreSQL indexes cover username uniqueness, product category, and file parent
  lookup. Search title uses a parameterized query.
- No performance target beyond bounded pagination is required for the take-home.

## Observability: logs, metrics, and alerts

- Use Laravel application logging for unexpected server failures.
- Never log passwords, access tokens, refresh tokens, or full request bodies.
- Include route and safe error class in unexpected error logs.
- Metrics and alerting are out of scope.

## Compatibility

- PHP 8.5.9 and Composer 2.10.3 are available locally.
- Packagist compatibility was checked: L5-Swagger 11.1.0 supports Laravel 13 and
  Sanctum 4.3.3 supports Laravel 13 components.
- PostgreSQL is available through the local client; Docker is not installed locally.
- Docker files target a reviewer environment with Docker Compose.
- API paths remain under `/api` without a version segment to match the test.

## Release and rollback

- Delivery includes migrations, seed data, Swagger output/configuration, README,
  Dockerfile, Compose file, and `.dockerignore`.
- Database changes are additive for the initial project; rollback uses Laravel
  migration rollback before delivery if a local migration needs correction.
- Public repository push is a delivery step after validation.

## Test boundaries

Feature tests cross the HTTP interface and cover:

- register, exact username login, duplicate username, and password failure;
- login token response, refresh rotation, replay rejection, and logout;
- public product list/detail and filtering/pagination;
- protected create/update/delete and unauthenticated rejection;
- product and file audit attribution through observers;
- image replacement and retention behavior;
- validation, path traversal rejection, missing product, and consistent response code;
- response resource shape without exposing password or token secrets.

## Alternatives considered

- Sanctum-only token pair: rejected because an access token should not be reused as a
  refresh token and Sanctum personal access tokens do not provide the required refresh
  lifecycle by themselves.
- JSONB images column: rejected because the approved design uses reusable polymorphic
  file metadata rows.
- Service layer for simple CRUD: deferred to keep the module direct and small.
- Soft delete: out of scope because the test asks for basic delete behavior.
- Docker local verification: unavailable because Docker is not installed locally.

## Blocked technical issues

| Issue | Product dependency | Impact | Status |
| --- | --- | --- | --- |
| L5-Swagger and Laravel 13 compatibility | Laravel 13 dependency resolution | Package versions are compatible according to Packagist | Resolved for design; Composer install verification remains part of implementation |
| Docker image/build verification | Docker installation | Cannot run local container checks | Blocked locally; artefacts still required |

## Technical decision log

| Decision ID | Decision | Alternatives | Rationale | Related FRs |
| --- | --- | --- | --- | --- |
| TD-001 | Use Laravel standard controllers, Form Requests, Eloquent models, observers, and Resources without a service layer. | Add service layer | Keeps simple CRUD local and reduces unnecessary interfaces. | FR-006 to FR-016 |
| TD-002 | Use polymorphic `files` metadata with custom `parent_table` morph column. | Product JSONB or product-specific image table | Supports multiple image paths and future parent models. | FR-015, FR-016 |
| TD-003 | Store refresh-token hashes and rotate tokens transactionally. | Store raw tokens or reuse access tokens | Limits disclosure and replay impact. | FR-004, FR-005 |
| TD-004 | Use Laravel paginator and eager-load response relations. | Manual pagination and lazy relations | Uses framework behavior and avoids N+1 queries. | FR-006, FR-009, FR-010 |
| TD-005 | Use Sanctum `^4.3` and L5-Swagger `^11.1`. | Older package versions or alternate Swagger package | Both package constraints support Laravel 13 according to Packagist metadata. | FR-004, FR-018 |
