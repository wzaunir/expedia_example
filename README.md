# Expedia Laravel API Example

This project provides a minimal Laravel-style API for integrating with the Expedia Rapid Lodging API.

## Endpoints

- `GET /api/expedia/hotels?cityId=1506246` – Fetches hotel data from Expedia.

Requests must include the header `X-API-TOKEN` with the value defined in `.env` as `API_TOKEN`.

## Tests

Tests use `Laravel HTTP::fake()` to mock Expedia responses. Run tests with:

```bash
composer install
./vendor/bin/phpunit
```

Because this repository does not include vendor libraries, run `composer install` before executing tests.
