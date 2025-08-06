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
}
