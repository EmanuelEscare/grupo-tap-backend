# Grupo TAP API

Backend API for technical test built with Laravel, PHP, and MongoDB. Implement bearer token authentication, product management, user management, profile management, section lookup, audit logging, and export endpoints for an Angular 19 frontend.

## Technologies

- PHP 8.2
- Laravel 11
- MongoDB
- mongodb/laravel-mongodb
- Laravel Pint
- Laravel Excel
- Spatie Laravel PDF with Dompdf driver
- PHPUnit

## Requirements

- PHP 8.2 or higher
- Composer
- MongoDB running locally or remotely
- MongoDB PHP extension enabled
- Node.js and npm only if Laravel frontend assets need to be built

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan storage:link
```

If frontend assets are needed:

```bash
npm install
npm run build
```

## MongoDB Configuration

Configure these variables in `.env`:

```dotenv
DB_CONNECTION=mongodb
DB_URI=mongodb://127.0.0.1:27017
DB_DATABASE=grupo_tap
DB_USERNAME=
DB_PASSWORD=
DB_AUTH_SOURCE=
```

If MongoDB requires authentication, fill in `DB_USERNAME`, `DB_PASSWORD`, and `DB_AUTH_SOURCE`.

## Dependencies

Install PHP dependencies:

```bash
composer install
```

Install JavaScript dependencies only if needed:

```bash
npm install
```

## Migrations and Seeders

Run migrations:

```bash
php artisan migrate
```

Run seeders:

```bash
php artisan db:seed
```

Run both:

```bash
php artisan migrate --seed
```

The base seeder creates the `products`, `users`, and `profiles` sections.

## Tests

```bash
php artisan test
```

Format code with Laravel Pint:

```bash
./vendor/bin/pint
./vendor/bin/pint --test
```

The test environment uses the `grupo_tap_testing` MongoDB database as configured in `phpunit.xml`.

## Authentication

Login endpoint:

```http
POST /api/auth/login
```

Request body:

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

The response includes an `access_token`. Send it to protected endpoints with:

```http
Authorization: Bearer {access_token}
Accept: application/json
```

Logout invalidates the current token:

```http
POST /api/auth/logout
```

## Response Format

Successful response:

```json
{
  "success": true,
  "message": "Message",
  "data": {}
}
```

Validation error response:

```json
{
  "success": false,
  "message": "Validation error",
  "errors": {}
}
```

## Endpoint Summary

Authentication:

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/forgot-password`
- `GET /api/me`
- `GET /api/me/sections`

Products:

- `GET /api/products`
- `POST /api/products`
- `GET /api/products/{id}`
- `PUT /api/products/{id}`
- `DELETE /api/products/{id}`
- `GET /api/products/export/pdf`
- `GET /api/products/export/excel`

Users:

- `GET /api/users`
- `POST /api/users`
- `GET /api/users/{id}`
- `PUT /api/users/{id}`
- `DELETE /api/users/{id}`
- `GET /api/users/export/pdf`
- `GET /api/users/export/excel`

Profiles:

- `GET /api/profiles`
- `POST /api/profiles`
- `GET /api/profiles/{id}`
- `PUT /api/profiles/{id}`
- `DELETE /api/profiles/{id}`
- `GET /api/profiles/export/pdf`
- `GET /api/profiles/export/excel`

Sections:

- `GET /api/sections`

## Audit Log

Audit records are stored in the `audit_logs` collection. The system creates an audit log when products, users, or profiles are updated or deleted.

Main fields:

- `collection`
- `document_id`
- `action`
- `before`
- `after`
- `user_id`
- `created_at`

For updates, `before` contains the previous state and `after` contains the new state. For deletes, `before` contains the deleted document data and `after` is `null`.

## Section-Based Access

Access to modules is controlled by `EnsureUserHasSectionAccess`.

Users have `profile_ids`. Each profile has `section_ids`. The middleware checks that at least one of the user's profiles is related to the requested section.

Base sections:

- `products`
- `users`
- `profiles`

Product endpoints require access to `products`. User endpoints require access to `users`. Profile endpoints require access to `profiles`. The section index endpoint only requires authentication.

## Postman Collection

The Postman collection is available at:

```text
docs/postman_collection.json
```

It includes the `base_url` and `token` variables and documents the available API endpoints.
