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


    public function test_get_properties_by_polygon_returns_response()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/properties/geography*' => Http::response([
                'properties' => [
                    ['id' => '1', 'name' => 'Demo Property']
                ]
            ], 200)
        ]);

        $geoJson = json_encode([
            'type' => 'Polygon',
            'coordinates' => [[[0,0], [0,1], [1,1], [1,0], [0,0]]]
        ]);

        $request = Request::create('/api/expedia/properties/geography', 'POST', [
            'include' => 'details',
            'supply_source' => 'expedia',
        ], [], [], [], $geoJson);
        $request->headers->set('X-API-TOKEN', 'secret-token');
        $request->headers->set('Content-Type', 'application/json');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getPropertiesByPolygon($req));

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Demo Property', $response->getData(true)['properties'][0]['name']);

        Http::assertSent(function ($request) use ($geoJson) {
            return $request->url() === 'https://test.expediapartnercentral.com/rapid/properties/geography?include=details&supply_source=expedia'
                && $request->body() === $geoJson
                && $request->method() === 'POST';
        });
    }

    public function test_get_properties_by_polygon_requires_geojson()
    {
        Http::fake();

        $request = Request::create('/api/expedia/properties/geography', 'POST', [
            'include' => 'details'
        ]);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->getPropertiesByPolygon($req));

        $this->assertEquals(422, $response->status());

    }
}
