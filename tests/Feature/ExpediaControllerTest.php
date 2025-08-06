<?php

namespace Tests\Feature;

use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\TestCase;

class ExpediaControllerTest extends TestCase
{
    public function test_search_hotels_returns_response()
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


    public function test_search_hotels_handles_api_error()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/hotels*' => Http::response([], 500),
        ]);

        $request = Request::create('/api/expedia/hotels', 'GET', ['cityId' => '1506246']);

        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();

        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        $this->assertEquals(500, $response->status());
        $data = $response->getData(true);
        $this->assertEquals(500, $data['status']);
        $this->assertArrayHasKey('message', $data);
    }

    public function test_search_hotels_handles_network_exception()
    {
        Http::fake(function ($request) {
            throw new \Exception('Network error');
        });

        $request = Request::create('/api/expedia/hotels', 'GET', ['cityId' => '1506246']);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        $this->assertEquals(500, $response->status());
        $data = $response->getData(true);
        $this->assertEquals(500, $data['status']);
        $this->assertArrayHasKey('message', $data);

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
}
