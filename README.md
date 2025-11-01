# lpaecomms

Ecommerce Website/Webstore to validate CTI assessments.

## Requirements

* PHP 8.1+
* MySQL 8+
* Composer
* Redis (used for catalog caching)

## Environment configuration

Copy the example environment file and update it with your local credentials:

```bash
cp .env.example .env
```

The following variables control the catalog cache helper:

* `REDIS_HOST` and `REDIS_PORT` – where the application should connect to Redis (defaults to `127.0.0.1:6379`).
* `CATALOG_CACHE_TTL` – lifetime of cached catalog listings in seconds (defaults to `300`).

Ensure that a Redis server is available and reachable with these settings before loading the product catalog.
