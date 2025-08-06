<?php

namespace Tests\Feature;

use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class ExpediaControllerTest extends TestCase
{
    public function test_search_hotels_success()
    {
        Http::fake(function ($request) {
            return Http::response([
                'hotels' => [
                    ['id' => '1', 'name' => 'Demo Hotel']
                ]
            ], 200);
        });

        $request = Request::create('/api/expedia/hotels', 'GET', [
            'cityId' => '1506246',
            'checkin' => '2024-09-01',
            'checkout' => '2024-09-05',
            'room1' => '2',
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer demo-key');
        });

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->getData(true)['hotels']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.expediapartnercentral.com/rapid/hotels'
                && $request['cityId'] === '1506246'
                && $request['checkin'] === '2024-09-01'
                && $request['checkout'] === '2024-09-05'
                && $request['room1'] === '2';
        });
    }

    public function test_search_hotels_requires_parameters()
    {
        Http::fake();

        $request = Request::create('/api/expedia/hotels', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_chains_returns_response()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/chains*' => Http::response([
                'chains' => [
                    ['id' => '1', 'name' => 'Demo Chain']
                ],
                'token' => 'next'
            ], 200)
        ]);

        $request = Request::create('/api/expedia/chains', 'GET', [
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
            return $request->url() === 'https://test.expediapartnercentral.com/rapid/chains'
                && $request['limit'] === '10'
                && $request['token'] === 'prev';
        });
    }

    public function test_get_chains_validates_parameters()
    {
        Http::fake();

        $request = Request::create('/api/expedia/chains', 'GET', [
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
            'https://test.expediapartnercentral.com/rapid/regions/*' => Http::response([
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
            'https://test.expediapartnercentral.com/rapid/properties/content*' => Http::response([
                'property_id' => '123',
                'name' => 'Demo Property'
            ], 200)
        ]);

        $request = Request::create('/api/expedia/property-content', 'GET', [
            'property_id' => '123',
            'language' => 'en-US',
            'include' => 'details'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getPropertyContent($req));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Demo Property', $response->getData(true)['name']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.expediapartnercentral.com/rapid/properties/content'
                && $request['property_id'] === '123'
                && $request['language'] === 'en-US'
                && $request['include'] === 'details';
        });
    }

    public function test_get_property_content_requires_property_id()
    {
        Http::fake();

        $request = Request::create('/api/expedia/property-content', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getPropertyContent($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_guest_reviews_returns_response()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/properties/123/guest-reviews*' => Http::response([
                'reviews' => [
                    ['id' => '1', 'comment' => 'Great stay']
                ]
            ], 200)
        ]);

        $request = Request::create('/api/expedia/properties/123/guest-reviews', 'GET', ['language' => 'en-US']);

        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getGuestReviews($req, '123'));

        $this->assertEquals(200, $response->status());

        $this->assertEquals('Great stay', $response->getData(true)['reviews'][0]['comment']);


        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.expediapartnercentral.com/rapid/properties/123/guest-reviews'
                && $request['language'] === 'en-US';
        });
    }

    public function test_get_guest_reviews_validates_property_id()
    {
        Http::fake();

        $request = Request::create('/api/expedia/properties/abc/guest-reviews', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getGuestReviews($req, 'abc'));

        $this->assertEquals(422, $response->status());
    }

    public function test_get_availability_returns_response()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/properties/availability*' => Http::response([
                'property_id' => '123',
                'available' => true
            ], 200)
        ]);

        $request = Request::create('/api/expedia/properties/availability', 'GET', [
            'property_id' => '123',
            'checkin' => '2024-09-01',
            'checkout' => '2024-09-05',
            'occupancy' => '2',
            'language' => 'en-US',
            'currency' => 'USD',
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getAvailability($req));

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->getData(true)['available']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.expediapartnercentral.com/rapid/properties/availability'
                && $request['property_id'] === '123'
                && $request['checkin'] === '2024-09-01'
                && $request['checkout'] === '2024-09-05'
                && $request['occupancy'] === '2'
                && $request['language'] === 'en-US'
                && $request['currency'] === 'USD';
        });
    }

    public function test_get_availability_requires_parameters()
    {
        Http::fake();

        $request = Request::create('/api/expedia/properties/availability', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getAvailability($req));

        $this->assertEquals(422, $response->status());
    }


    public function test_get_inactive_properties_returns_response()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/properties/inactive*' => Http::response([
                'properties' => [
                    ['id' => '1']
                ]
            ], 200)
        ]);

        $request = Request::create('/api/expedia/properties/inactive', 'GET', [
            'since' => '2024-09-01',
            'page' => '1',
            'limit' => '10',
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getInactiveProperties($req));

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->getData(true)['properties']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://test.expediapartnercentral.com/rapid/properties/inactive'
                && $request['since'] === '2024-09-01'
                && $request['page'] === '1'
                && $request['limit'] === '10';
        });
    }

    public function test_get_inactive_properties_requires_since()
    {
        Http::fake();

        $request = Request::create('/api/expedia/properties/inactive', 'GET');

        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();

        $response = $middleware->handle($request, fn($req) => $controller->getInactiveProperties($req));

        $this->assertEquals(422, $response->status());

    }

    public function test_download_property_content_returns_response()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/files/properties/content*' => Http::response([
                'content_url' => 'https://example.com/file.zip'
            ], 200)
        ]);

        $request = Request::create('/api/expedia/files/property-content', 'GET', [
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
            return $request->url() === 'https://test.expediapartnercentral.com/files/properties/content'
                && $request['language'] === 'en-US'
                && $request['supply_source'] === 'expedia'
                && isset($request['signature'])
                && isset($request['timestamp'])
                && isset($request['key']);
        });
    }

    public function test_download_property_content_requires_parameters()
    {
        Http::fake();

        $request = Request::create('/api/expedia/files/property-content', 'GET');
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->downloadPropertyContent($req));

        $this->assertEquals(422, $response->status());
    }

    public function test_download_property_catalog_returns_response()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/files/properties/catalog*' => Http::response([
                'catalog' => 'data'
            ], 200)
        ]);

        $request = Request::create('/api/expedia/files/properties/catalog', 'GET', [
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
            return $request->url() === 'https://test.expediapartnercentral.com/files/properties/catalog'
                && $request['language'] === 'en-US'
                && $request['supply_source'] === 'expedia';
        });

    }
}

