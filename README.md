# Expedia Laravel API Example

This project provides a minimal Laravel-style API for integrating with the Expedia Rapid Lodging API.

## Endpoints

- `GET /api/expedia/hotels?ancestor_id=178286&language=en-US&include[]=property_ids` – Proxies the Rapid `/regions` search to list the property ids within a geography.
- `GET /api/expedia/properties/availability?...` – Passes Rapid Shopping availability parameters straight through to `/properties/availability`.
- `POST /api/expedia/itineraries` – Forwards booking payloads to Rapid `/itineraries`.
- `GET /api/expedia/itineraries/{itinerary_id}` – Retrieves a previously created itinerary.
- `DELETE /api/expedia/itineraries/{itinerary_id}` – Cancels an itinerary (when permitted by Rapid).

Requests must include the header `X-API-TOKEN` with the value defined in `.env` as `API_TOKEN`.

### Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `ancestor_id` | int | Expedia region identifier (for example, a city). |
| `language` | string (`BCP47`) | Response locale, defaults to `en-US`. |
| `include[]` | array | One or more of `standard`, `details`, `property_ids`, `property_ids_expanded`. |
| `token` | string | Optional pagination token from the Rapid `Link` header. |

For availability, booking, and cancellation, use the parameters defined in the [Rapid Lodging Shopping](https://developers.expediagroup.com/rapid/lodging/shopping) and [Rapid Lodging Booking](https://developers.expediagroup.com/rapid/lodging/booking) docs. The API simply validates basic structure (email/phone/room guest details) and forwards the payload and query string to Rapid.

## Tests

Tests use `Laravel HTTP::fake()` to mock Expedia responses. Run tests with:

```bash
composer install
./vendor/bin/phpunit
```

Because this repository does not include vendor libraries, run `composer install` before executing tests.

## Docker

The project includes a simple Docker setup. Build and run the container with:

```bash
docker-compose up --build
```

The application will be available at http://localhost:8000.
