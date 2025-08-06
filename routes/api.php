<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExpediaController;
use App\Http\Middleware\ApiTokenMiddleware;

Route::middleware([ApiTokenMiddleware::class])->group(function () {
    Route::get('/expedia/hotels', [ExpediaController::class, 'searchHotels']);
    Route::get('/expedia/region/{region_id}', [ExpediaController::class, 'getRegion']);
    Route::get('/expedia/property-content', [ExpediaController::class, 'getPropertyContent']);
    Route::get('/expedia/properties/{property_id}/guest-reviews', [ExpediaController::class, 'getGuestReviews']);
    Route::get('/expedia/properties/availability', [ExpediaController::class, 'getAvailability']);

    Route::get('/expedia/properties/inactive', [ExpediaController::class, 'getInactiveProperties']);
    Route::post('/expedia/properties/geography', [ExpediaController::class, 'getPropertiesByPolygon']);


    Route::get('/expedia/files/properties/catalog', [ExpediaController::class, 'downloadPropertyCatalog']);

});

