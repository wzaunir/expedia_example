<?php

namespace Tests\Feature;

use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_request_without_token_is_unauthorized()
    {
        $middleware = new ApiTokenMiddleware();
        $controller = new ExpediaController();
        $request = Request::create('/api/expedia/hotels', 'GET');

        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        $this->assertEquals(401, $response->status());
    }
}
