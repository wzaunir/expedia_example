<?php

namespace Tests\Feature;

use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;
use App\Http\Requests\SearchHotelsRequest;
use App\Http\Requests\ChainsRequest;
use App\Http\Requests\PropertyContentRequest;
use App\Http\Requests\GuestReviewsRequest;
use App\Http\Requests\AvailabilityRequest;
use App\Http\Requests\InactivePropertiesRequest;
use App\Http\Requests\DownloadPropertyContentRequest;
use App\Http\Requests\DownloadPropertyCatalogRequest;
use App\Http\Requests\CreateItineraryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class ExpediaControllerTest extends TestCase
{
    public function test_search_hotels_success()
    {
        Http::fake([
            'https://test.ean.com/v3/regions*' => Http::response([
                'regions' => [
                    ['id' => '178286', 'name' => 'Demo Region']
                ]
            ], 200),
        ]);

        $request = SearchHotelsRequest::create('/api/expedia/hotels', 'GET', [
            'ancestor_id' => '178286',
            'include' => ['property_ids'],
            'language' => 'en-US',
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        Http::assertSent(function ($request) {
            $auth = $request->header('Authorization');
            return !empty($auth)
                && str_contains($auth, 'EAN APIKey=demo-key')
                && str_contains($auth, 'Signature=')
                && str_contains($auth, 'timestamp=');
        });

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->getData(true)['regions']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://test.ean.com/v3/regions')
                && $query['language'] === 'en-US'
                && $query['include'] === 'property_ids'
                && $query['ancestor_id'] === '178286';
        });
    }

    public function test_search_hotels_requires_parameters()
    {
        Http::fake();

        $request = SearchHotelsRequest::create('/api/expedia/hotels', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_chains_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/chains*' => Http::response([
                'chains' => [
                    ['id' => '1', 'name' => 'Demo Chain']
                ],
            ], 200)
        ]);

        $request = ChainsRequest::create('/api/expedia/chains', 'GET', [
            'limit' => '10',
            'token' => 'prev'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getChains($req));

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->getData(true)['chains']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://test.ean.com/v3/chains')
                && $query['limit'] === '10'
                && $query['token'] === 'prev';
        });
    }

    public function test_get_chains_validates_parameters()
    {
        Http::fake();

        $request = ChainsRequest::create('/api/expedia/chains', 'GET', [
            'limit' => 'abc'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getChains($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_region_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/regions/*' => Http::response([
                'id' => '1',
                'name' => 'Demo Region'
            ], 200)
        ]);

        $request = Request::create('/api/expedia/region/1', 'GET', ['language' => 'en-US', 'include' => 'details']);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getRegion($req, '1'));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Demo Region', $response->getData(true)['name']);
    }

    public function test_get_property_content_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/content*' => Http::response([
                'property_id' => '123',
                'name' => 'Demo Property'
            ], 200)
        ]);

        $request = PropertyContentRequest::create('/api/expedia/property-content', 'GET', [
            'property_id' => ['123'],
            'language' => 'en-US',
            'supply_source' => 'expedia',
            'include' => ['details'],
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getPropertyContent($req));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Demo Property', $response->getData(true)['name']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $propertyIds = $query['property_id'] ?? ($query['property_id'][0] ?? null);
            if (is_array($propertyIds)) {
                $propertyIds = $propertyIds[0];
            }

            $include = $query['include'] ?? ($query['include'][0] ?? null);
            if (is_array($include)) {
                $include = $include[0];
            }

            return str_starts_with($url, 'https://test.ean.com/v3/properties/content')
                && $propertyIds === '123'
                && $query['language'] === 'en-US'
                && $query['supply_source'] === 'expedia'
                && $include === 'details';
        });
    }

    public function test_get_property_content_requires_property_id()
    {
        Http::fake();

        $request = PropertyContentRequest::create('/api/expedia/property-content', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getPropertyContent($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_guest_reviews_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/123/guest-reviews*' => Http::response([
                'reviews' => [
                    ['id' => '1', 'comment' => 'Great stay']
                ]
            ], 200)
        ]);

        $request = GuestReviewsRequest::create('/api/expedia/properties/123/guest-reviews', 'GET', ['language' => 'en-US']);

        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getGuestReviews($req, '123'));

        $this->assertEquals(200, $response->status());

        $this->assertEquals('Great stay', $response->getData(true)['reviews'][0]['comment']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://test.ean.com/v3/properties/123/guest-reviews')
                && $query['language'] === 'en-US';
        });
    }

    public function test_get_guest_reviews_validates_property_id()
    {
        Http::fake();

        $request = GuestReviewsRequest::create('/api/expedia/properties/abc/guest-reviews', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getGuestReviews($req, 'abc'));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_availability_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/availability*' => Http::response([
                'property_id' => '123',
                'available' => true
            ], 200)
        ]);

        $request = AvailabilityRequest::create('/api/expedia/properties/availability', 'GET', [
            'property_id' => ['123'],
            'checkin' => '2024-09-01',
            'checkout' => '2024-09-05',
            'occupancy' => ['2'],
            'language' => 'en-US',
            'currency' => 'USD',
            'country_code' => 'US',
            'rate_plan_count' => 1,
            'sales_channel' => 'website',
            'sales_environment' => 'hotel_only',
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getAvailability($req));

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->getData(true)['available']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);
            $propertyIds = $query['property_id'] ?? ($query['property_id'][0] ?? null);
            if (is_array($propertyIds)) {
                $propertyIds = $propertyIds[0];
            }
            $occupancy = $query['occupancy'] ?? ($query['occupancy'][0] ?? null);
            if (is_array($occupancy)) {
                $occupancy = $occupancy[0];
            }

            return str_starts_with($url, 'https://test.ean.com/v3/properties/availability')
                && $propertyIds === '123'
                && $query['checkin'] === '2024-09-01'
                && $query['checkout'] === '2024-09-05'
                && $occupancy === '2'
                && $query['language'] === 'en-US'
                && $query['currency'] === 'USD'
                && $query['country_code'] === 'US'
                && $query['rate_plan_count'] === '1'
                && $query['sales_channel'] === 'website'
                && $query['sales_environment'] === 'hotel_only';
        });
    }

    public function test_get_availability_requires_parameters()
    {
        Http::fake();

        $request = AvailabilityRequest::create('/api/expedia/properties/availability', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getAvailability($req));

        $this->assertEquals(422, $response->status());
    }


    public function test_get_inactive_properties_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/inactive*' => Http::response([
                'properties' => [
                    ['id' => '1']
                ]
            ], 200)
        ]);

        $request = InactivePropertiesRequest::create('/api/expedia/properties/inactive', 'GET', [
            'since' => '2024-09-01',
            'limit' => '10',
            'token' => 'abc',
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getInactiveProperties($req));

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->getData(true)['properties']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://test.ean.com/v3/properties/inactive')
                && $query['since'] === '2024-09-01'
                && $query['limit'] === '10'
                && $query['token'] === 'abc';
        });
    }

    public function test_get_inactive_properties_requires_since()
    {
        Http::fake();

        $request = InactivePropertiesRequest::create('/api/expedia/properties/inactive', 'GET');

        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();

        $response = $middleware->handle($request, fn($req) => $controller->getInactiveProperties($req));

        $this->assertEquals(422, $response->status());

    }

    public function test_download_property_content_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/files/properties/content*' => Http::response([
                'content_url' => 'https://example.com/file.zip'
            ], 200)
        ]);

        $request = DownloadPropertyContentRequest::create('/api/expedia/files/property-content', 'GET', [
            'language' => 'en-US',
            'supply_source' => 'expedia'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->downloadPropertyContent($req));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('https://example.com/file.zip', $response->getData(true)['content_url']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://test.ean.com/v3/files/properties/content')
                && $query['language'] === 'en-US'
                && $query['supply_source'] === 'expedia';
        });
    }

    public function test_download_property_content_requires_parameters()
    {
        Http::fake();

        $request = DownloadPropertyContentRequest::create('/api/expedia/files/property-content', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->downloadPropertyContent($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_download_property_catalog_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/files/properties/catalog*' => Http::response([
                'catalog' => 'data'
            ], 200)
        ]);

        $request = DownloadPropertyCatalogRequest::create('/api/expedia/files/properties/catalog', 'GET', [
            'language' => 'en-US',
            'supply_source' => 'expedia'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->downloadPropertyCatalog($req));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('data', $response->getData(true)['catalog']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://test.ean.com/v3/files/properties/catalog')
                && $query['language'] === 'en-US'
                && $query['supply_source'] === 'expedia';
        });

    }

    public function test_create_itinerary_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/itineraries' => Http::response([
                'itinerary_id' => 'ABC123'
            ], 200)
        ]);

        $payload = [
            'email' => 'john@example.com',
            'phone' => [
                'country_code' => '1',
                'number' => '5550077',
            ],
            'rooms' => [
                [
                    'given_name' => 'John',
                    'family_name' => 'Doe',
                ],
            ],
        ];

        $request = CreateItineraryRequest::create('/api/expedia/itineraries', 'POST', $payload);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->createItinerary($req));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('ABC123', $response->getData(true)['itinerary_id']);

        Http::assertSent(function ($request) use ($payload) {
            return $request->url() === 'https://test.ean.com/v3/itineraries'
                && $request->method() === 'POST'
                && $request->data()['email'] === $payload['email'];
        });
    }

    public function test_create_itinerary_requires_minimum_fields()
    {
        Http::fake();

        $request = CreateItineraryRequest::create('/api/expedia/itineraries', 'POST');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->createItinerary($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_itinerary_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/itineraries/ABC123*' => Http::response([
                'itinerary_id' => 'ABC123',
                'status' => 'booked'
            ], 200)
        ]);

        $request = Request::create('/api/expedia/itineraries/ABC123', 'GET', [
            'language' => 'en-US'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getItinerary($req, 'ABC123'));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('booked', $response->getData(true)['status']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return str_starts_with($url, 'https://test.ean.com/v3/itineraries/ABC123')
                && $query['language'] === 'en-US';
        });
    }

    public function test_cancel_itinerary_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/itineraries/ABC123*' => Http::response([
                'itinerary_id' => 'ABC123',
                'status' => 'canceled'
            ], 200)
        ]);

        $request = Request::create('/api/expedia/itineraries/ABC123', 'DELETE', [
            'language' => 'en-US'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->cancelItinerary($req, 'ABC123'));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('canceled', $response->getData(true)['status']);

        Http::assertSent(function ($request) {
            $url = $request->url();
            parse_str(parse_url($url, PHP_URL_QUERY), $query);

            return $request->method() === 'DELETE'
                && str_starts_with($url, 'https://test.ean.com/v3/itineraries/ABC123')
                && $query['language'] === 'en-US';
        });
    }
}
