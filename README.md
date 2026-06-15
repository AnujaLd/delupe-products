# Delupe Products API

A Laravel + PostgreSQL REST API for importing and managing product feeds from merchants.
Built with Laravel 10, PostgreSQL, Docker, and includes a full CI/CD pipeline.

---

## Tech Stack

- PHP 8.4
- Laravel 10
- PostgreSQL 15
- Docker & Docker Compose
- PHPUnit (Testing)
- PHPStan + Larastan (Static Analysis)
- GitHub Actions (CI/CD)

---

## Prerequisites

Before starting, install the following on your Windows machine:

| Software | Download Link |
|----------|--------------|
| Docker Desktop | https://www.docker.com/products/docker-desktop |
| Git | https://git-scm.com/download/win |
| VS Code (optional) | https://code.visualstudio.com |

> After installing Docker Desktop, restart your PC and make sure Docker is running (whale icon in taskbar shows "Engine running")

---

## Project Structure
delupe-products/

├── .github/

│   └── workflows/

│       └── ci.yml            # GitHub Actions CI/CD pipeline

├── docker/

│   ├── nginx/

│   │   └── default.conf      # Nginx web server config

│   └── php/

│       └── Dockerfile        # PHP 8.4 container

├── src/                      # Laravel application

│   ├── app/

│   │   ├── Console/

│   │   │   └── Commands/

│   │   │       ├── ImportProducts.php    # Import command

│   │   │       └── UpdatePrices.php      # Price update command

│   │   ├── Http/

│   │   │   ├── Controllers/

│   │   │   │   └── ProductController.php

│   │   │   └── Middleware/

│   │   │       └── ApiKeyMiddleware.php

│   │   ├── Jobs/

│   │   │   └── ProcessProductImport.php  # Queue job

│   │   └── Models/

│   │       └── Product.php

│   ├── database/

│   │   └── migrations/

│   │       └── create_products_table.php

│   ├── routes/

│   │   └── api.php

│   ├── tests/

│   │   └── Feature/

│   │       └── ProductTest.php

│   ├── products.json         # Sample product data

│   └── phpstan.neon          # PHPStan config

├── docker-compose.yml

└── README.md

---

## Step 1: Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/delupe-products.git
cd delupe-products
```

---

## Step 2: Configure Environment

```bash
cp src/.env.example src/.env
```

Open `src/.env` and make sure these values are set:

```env
APP_NAME=DelupeProducts
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=delupe_db
DB_USERNAME=delupe_user
DB_PASSWORD=delupe_pass

QUEUE_CONNECTION=database

API_KEY=my-secret-api-key-12345
```

## Quick summary

- App: Laravel 10 API that stores product records in PostgreSQL.
- Run with Docker Compose (recommended) or natively with PHP + Composer.
- Protected API: `X-API-Key` header (set by `API_KEY` in your `.env`).
- Important endpoints: `/api/health`, `/api/products`, `/api/products/summary`, `/api/products/duplicates`.

## Repository layout (relevant files)

- `src/` — Laravel application code.
- `docker-compose.yml` — Docker setup (app, nginx, postgres, queue).
- `src/products.json` — sample product feed used by the importer.
- `src/app/Console/Commands/ImportProducts.php` — artisan command: `app:import-products`.
- `src/app/Jobs/ProcessProductImport.php` — job which writes/updates products.
- `src/app/Http/Controllers/ProductController.php` — API implementations.

## Prerequisites

- Docker & Docker Compose (recommended).
- If running natively: PHP 8.1+, Composer, PostgreSQL (or you can use Docker DB).

## Environment variables

Copy `src/.env` (provided in repo) or create from `src/.env.example` and configure these values as needed:

- `APP_URL` — base URL (default: `http://localhost:8080`).
- `API_KEY` — API key used by the `X-API-Key` header. Default in repo: `my-secret-api-key-12345`.
- `DB_*` — standard Laravel DB connection variables (the Docker compose file creates a `db` service with PostgreSQL and sensible defaults).

When running Docker Compose the `src` folder is mounted into the app container, so the `.env` inside `src/` is the active environment file.

## Running with Docker (recommended)

1. From the repository root, start Docker Compose (builds the PHP image and starts services):

```powershell
docker-compose up -d --build
```

2. Wait a few seconds for the Postgres container to be ready. Then run migrations:

```powershell
docker-compose exec app php artisan migrate --force
```

3. (Optional) Import the sample `products.json` bundled in `src/`:

```powershell
# the file is available inside the container at /var/www/products.json
docker-compose exec app php artisan app:import-products /var/www/products.json
```

4. Open the API at: http://localhost:8080 (Nginx on port 8080 is mapped in `docker-compose.yml`).

5. The repository includes a `queue` service. If you want to run a dedicated worker use the `queue` service (already defined in the compose file) or run a worker manually in the `app` container:

```powershell
docker-compose exec app php artisan queue:work --sleep=3 --tries=3
```

Stopping and removing containers:

```powershell
docker-compose down
```

## API Reference (Postman tutorial)

This section shows how to run and test the API using Postman only (no collection file is included). You'll create a small Postman collection and four requests (Health, List Products, Summary, Duplicates) and add simple tests to each request.

Prerequisite: the app should be running (for Docker see the "Running with Docker" section above). The base URL used below is `http://localhost:8080`.

Step-by-step Postman setup

1. Open Postman and create a new Collection. Name it `Delupe Products`.
2. Create a new Environment (click the gear icon → Manage Environments). Add two variables:
   - `baseUrl` = `http://localhost:8080`
   - `API_KEY` = the value from `src/.env` (default `my-secret-api-key-12345`)
3. Select the `Delupe Products` collection and add four requests (below). For each request, set the URL using the environment variable, e.g. `{{baseUrl}}/api/health`.

Requests and tests to add

- Health — GET `{{baseUrl}}/api/health`
  - Authorization: none
  - (Suggested test in Postman: assert HTTP 200 and that `status` === "ok")

  - Example response JSON:

    ```json
    {
      "status": "ok",
      "database": "connected"
    }
    ```

- List Products — GET `{{baseUrl}}/api/products`
  - Params (optional): `currency=USD`, `min_price=0`, `max_price=1000`, `limit=20`
  - Header: `X-API-Key: {{API_KEY}}`
  - (Suggested tests in Postman: assert HTTP 200, presence of `data` and pagination fields, and that `data` is an array)

  - Example response JSON (paginated):

    ```json
    {
      "data": [
        {
          "id": 1,
          "merchant_id": "M001",
          "name": "Blue Sneakers",
          "link": "https://shop.com/blue-sneakers",
          "image_link": "https://shop.com/images/blue-sneakers.jpg",
          "price": "89.99",
          "original_price": null,
          "currency": "USD",
          "created_at": "2026-06-14T19:51:00.000000Z",
          "updated_at": "2026-06-14T19:51:00.000000Z"
        }
      ],
      "current_page": 1,
      "last_page": 1,
      "per_page": 50,
      "total": 1
    }
    ```

- Summary — GET `{{baseUrl}}/api/products/summary`
  - Header: `X-API-Key: {{API_KEY}}`
  - (Suggested tests in Postman: assert HTTP 200 and presence of `count`, `total_price`, and `average_price`)

  - Example response JSON:

    ```json
    {
      "count": 10,
      "total_price": 1234.56,
      "average_price": 123.46,
      "currencies": {
        "USD": 8,
        "EUR": 2
      }
    }
    ```

- Duplicates — GET `{{baseUrl}}/api/products/duplicates`
  - Header: `X-API-Key: {{API_KEY}}`
  - (Suggested tests in Postman: assert HTTP 200 and that the response is an array)

  - Example response JSON (array of duplicated product objects):

    ```json
    [
      {
        "id": 1,
        "merchant_id": "M001",
        "name": "Blue Sneakers",
        "link": "https://shop.com/blue-sneakers",
        "image_link": "https://shop.com/images/blue-sneakers.jpg",
        "price": "89.99",
        "original_price": null,
        "currency": "USD",
        "created_at": "2026-06-14T19:51:00.000000Z",
        "updated_at": "2026-06-14T19:51:00.000000Z"
      },
      {
        "id": 4,
        "merchant_id": "M002",
        "name": "Blue Sneakers",
        "link": "https://shop.com/blue-sneakers-v2",
        "image_link": null,
        "price": "75.00",
        "original_price": null,
        "currency": "USD",
        "created_at": "2026-06-14T20:00:00.000000Z",
        "updated_at": "2026-06-14T20:00:00.000000Z"
      }
    ]
    ```

Running tests

- Use the Collection Runner in Postman to run the `Delupe Products` collection against the environment you created. The tests above will run for each request and show pass/fail per request.


## Running tests

Run tests inside the container (recommended):

```powershell
docker-compose exec app ./vendor/bin/phpunit
```

Or run locally (after `composer install`):

```powershell
cd src
./vendor/bin/phpunit
```

## Troubleshooting

- Postgres not ready: the first `php artisan migrate` may fail if DB isn't ready. Retry a few seconds later or check `docker-compose logs db`.
- Port 8080 taken: edit `docker-compose.yml` or stop the service using the port.
- `Unauthorized` 401 responses: ensure `X-API-Key` header matches `API_KEY` from `src/.env`.
- If imports don't show up: verify the importer logged progress in `storage/logs/laravel.log` and that a queue worker is running (if queue connection is not `sync`).


## TL;DR - minimal commands (Docker)

```powershell
docker-compose up -d --build
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan app:import-products /var/www/products.json
