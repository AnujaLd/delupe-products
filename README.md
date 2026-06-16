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
git clone https://github.com/AnujaLd/delupe-products.git
cd delupe-products
```

---


## One-command Docker run (cross-platform)

To build the images, start the containers, install Composer dependencies, generate the app key, run migrations, and import sample data with a single cross-platform command, run:

```bash
docker compose up --build -d
```

Important: If the terminal shows the Docker build completed successfully but the API returns a 502 Bad Gateway or an HTML page, the services are still processing/initialising. On lower-performance laptops this can take longer — please wait about 2–15 minutes and then check the API again; it should work once initialisation (PHP-FPM, migrations, composer install, seeding) finishes.

If you use Docker Desktop, you can also open the Docker Desktop app and check the Containers / Apps view or the Logs panel — if a container shows "starting" or the logs show startup activity, the stack is still initializing. Please wait until the containers report as running and the startup logs finish before calling the API.

This command works the same on macOS, Linux, and Windows (with Docker Desktop or Docker Engine installed).

What happens during container startup:

- The app container waits for the database to be ready.
- Composer is run inside the container if vendor dependencies are missing.
- `php artisan key:generate` is run when needed.
- `php artisan migrate --force` and `php artisan db:seed --force` are executed.
- If `products.json` is present at the project root, it is imported via `php artisan app:import-products`.

If you prefer to run steps manually, follow the "Running with Docker (recommended)" section above.

## API Reference (Postman tutorial)

This section shows how to run and test the API using Postman only (no collection file is included). You'll create a small Postman collection and four requests (Health, List Products, Summary, Duplicates) and add simple tests to each request.

Prerequisite: the app should be running (for Docker see the "Running with Docker" section above). The base URL used below is `http://localhost:8080`.

Step-by-step Postman setup

1. Open Postman and create a new Collection. Name it `Delupe Products`.
2. Create a new Environment (click the gear icon → Manage Environments). Add two variables:
  - `baseUrl` = `http://localhost:8080`
  -  Header - (Key)`X-API-Key` = (Value)`my-secret-api-key-12345` (the project sets this value in `docker-compose.yml` at the repository root)
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
  - Header: `X-API-Key: my-secret-api-key-12345`
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
  - Header: `X-API-Key: my-secret-api-key-12345`
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
  - Header: `X-API-Key: my-secret-api-key-12345`
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

## Quick summary

- App: Laravel 10 API that stores product records in PostgreSQL.
- Run with Docker Compose (recommended) or natively with PHP + Composer.
- Protected API: `X-API-Key` header (value set to `my-secret-api-key-12345` in `docker-compose.yml`).
- Important endpoints: `/api/health`, `/api/products`, `/api/products/summary`, `/api/products/duplicates`.

## API Endpoints

Below is a complete list of available API endpoints, whether authentication is required, and a short description for each.

| # | Method | Endpoint | Auth Required | Description |
|---:|:-------|:---------|:--------------:|:------------|
| 1 | GET | /api/health | ❌ | Health check |
| 2 | GET | /api/products | ✅ | Get all products |
| 3 | GET | /api/products?currency=USD | ✅ | Filter by currency |
| 4 | GET | /api/products?min_price=100 | ✅ | Filter min price |
| 5 | GET | /api/products?max_price=200 | ✅ | Filter max price |
| 6 | GET | /api/products?min_price=50&max_price=200 | ✅ | Filter price range |
| 7 | GET | /api/products?page=1&limit=2 | ✅ | Pagination |
| 8 | GET | /api/products?currency=USD&min_price=50&max_price=500&page=1&limit=10 | ✅ | All filters combined |
| 9 | GET | /api/products/summary | ✅ | Price summary (count, total, average, currencies) |
|10 | GET | /api/products/duplicates | ✅ | Find duplicate products by name or link |


## Repository layout (relevant files)

- `src/` — Laravel application code.
- `docker-compose.yml` — Docker setup (app, nginx, postgres, queue).
- `src/products.json` — sample product feed used by the importer.
- `src/app/Console/Commands/ImportProducts.php` — artisan command: `app:import-products`.
- `src/app/Jobs/ProcessProductImport.php` — job which writes/updates products.
- `src/app/Http/Controllers/ProductController.php` — API implementations.

## Prerequisites

- Docker & Docker Compose (recommended).
- If running natively: PHP 8.1+, Composer, PostgreSQL

## Environment variables

Copy `src/.env` (provided in repo) or create from `src/.env.example` and configure these values as needed:

- `APP_URL` — base URL (default: `http://localhost:8080`).
- `API_KEY` — API key used by the (Header -`X-API-Key`). Default value in repo: (Value is the `my-secret-api-key-12345`.)

## Running with Docker (recommended)

Start the full application with the single cross-platform command shown above. If you need to run steps manually, use `docker compose` or `docker-compose` subcommands and `docker compose exec` to run artisan commands inside the app container.

## PRICE ADJUSTMENT COMMAND

Adjust product prices with the built-in artisan command `app:update-prices`. The recommended way is to run it inside the project's PHP container so it uses the same PHP/runtime as the app

Docker (exec into running app container):

```powershell
docker compose exec app php artisan app:update-prices 10
```

One-off container run (doesn't require the app container to be already running):

```powershell
docker compose run --rm app php artisan app:update-prices 10
```

If you prefer to run the command natively on your machine, ensure your local PHP matches the project's requirements (PHP >= 8.4.1) and run:

```powershell
cd src
php artisan app:update-prices 10
```

Replace `10` with the percentage or value your `UpdatePrices` command expects (check `src/app/Console/Commands/UpdatePrices.php` for exact argument semantics).



Running tests

- Use the Collection Runner in Postman to run the `Delupe Products` collection against the environment you created. The tests above will run for each request and show pass/fail per request.


## Running tests

Run tests inside the container (recommended):

```powershell
docker-compose exec app ./vendor/bin/phpunit
```

```powershell
cd src
./vendor/bin/phpunit
```

## Troubleshooting

- If the terminal shows the Docker build completed successfully but the API returns a 502 Bad Gateway or an HTML page, the services are still processing/initialising. On lower-performance laptops this can take longer — please wait about 2–15 minutes and then check the API again; it should work once initialisation (PHP-FPM, migrations, composer install, seeding) finishes.

- To check startup progress, follow the app container logs. From the project root run:

```powershell
docker compose logs -f app
```

If the logs show migration, seeding, composer install, or PHP-FPM startup messages, the app is still loading — wait until the logs stop showing startup activity or you see messages indicating the application is ready, then call the API.

- Docker Desktop users: the Docker Desktop UI also shows container status and logs. If the app or db container is still starting in Docker Desktop, wait until it reports "running" (or the logs stop showing startup activity) — this can take 2–15 minutes on slower machines.

- `Unauthorized` 401 responses: ensure the `X-API-Key` header matches the API key set in `docker-compose.yml` (default: `my-secret-api-key-12345`).
- If imports don't show up: verify the importer logged progress in `storage/logs/laravel.log` and that a queue worker is running (if queue connection is not `sync`).

## Viewing logs

To inspect application logs from the app container use the following Docker commands (run from the project root):

```bash
# show last 200 lines in app container
docker compose exec app tail -n 200 storage/logs/laravel.log

# follow log live inside container
docker compose exec app tail -f storage/logs/laravel.log
```

These commands let you quickly view recent log activity and follow new log entries as they are written.

