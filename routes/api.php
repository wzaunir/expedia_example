<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;

Route::middleware([ApiTokenMiddleware::class])->group(function () {
    Route::get('/expedia/hotels', [ExpediaController::class, 'searchHotels']);
    Route::get('/expedia/region/{region_id}', [ExpediaController::class, 'getRegion']);
    Route::get('/expedia/property-content', [ExpediaController::class, 'getPropertyContent']);
});
