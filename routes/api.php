<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;

Route::middleware([ApiTokenMiddleware::class])->group(function () {
    Route::get('/expedia/hotels', [ExpediaController::class, 'searchHotels']);
});
