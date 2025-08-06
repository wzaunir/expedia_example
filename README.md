# Expedia Laravel API Example

This project provides a minimal Laravel-style API for integrating with the Expedia Rapid Lodging API.

## Endpoints

- `GET /api/expedia/hotels?cityId=1506246&checkin=2024-09-01&checkout=2024-09-05&room1=2` – Fetches hotel data from Expedia.
- `GET /api/expedia/calendars/availability?property_id=123&start_date=2024-09-01&end_date=2024-09-30` – Retrieves an availability calendar for a property.

Requests must include the header `X-API-TOKEN` with the value defined in `.env` as `API_TOKEN`.

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `cityId`  | int  | Expedia city identifier. |
| `checkin` | date (`YYYY-MM-DD`) | Check-in date. |
| `checkout` | date (`YYYY-MM-DD`) | Check-out date, must be after `checkin`. |
| `room1` | string | Room occupancy description, e.g. `2` for two adults. |

## Tests

Tests use `Laravel HTTP::fake()` to mock Expedia responses. Run tests with:

```bash
composer install
./vendor/bin/phpunit
```

Because this repository does not include vendor libraries, run `composer install` before executing tests.
