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

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer demo-key');
        });

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($response->getData(true)['hotels']);
    }

    public function test_search_hotels_with_invalid_city_returns_error()
    {
        Http::fake([
            'https://test.expediapartnercentral.com/rapid/hotels*' => Http::response([
                'message' => 'Invalid cityId',
            ], 422)
        ]);

        $request = Request::create('/api/expedia/hotels', 'GET', ['cityId' => 'invalid']);
        $request->headers->set('X-API-TOKEN', 'secret-token');

        $controller = new ExpediaController();
        $middleware = new ApiTokenMiddleware();
        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer demo-key');
        });

        $this->assertEquals(422, $response->status());
        $this->assertEquals('Invalid cityId', $response->getData(true)['message']);
    }
}
