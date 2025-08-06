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
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/hotels*' => Http::response([
                'hotels' => [
                    ['id' => '1', 'name' => 'Demo Hotel']
                ]
            ], 200)
        ]);

        $request = Request::create('/api/expedia/hotels', 'GET', ['cityId' => '1506246']);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->getData(true)['hotels']);
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
}
