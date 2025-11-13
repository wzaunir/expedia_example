<?php

namespace Tests\Feature;

use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;
use App\Http\Requests\SearchHotelsRequest;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function test_request_without_token_is_unauthorized()
    {
        $middleware = new ApiTokenMiddleware();
        $controller = new ExpediaController();
        $request = SearchHotelsRequest::create('/api/expedia/hotels', 'GET');
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        $response = $middleware->handle($request, fn($req) => $controller->searchHotels($req));

        $this->assertEquals(401, $response->status());
    }
}
