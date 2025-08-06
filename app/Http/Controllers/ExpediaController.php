<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpediaController extends Controller
{
    /**
     * Retrieve hotels from Expedia Rapid API.
     */
    public function searchHotels(Request $request)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . config('services.expedia.key'),
        ])->get('https://test.expediapartnercentral.com/rapid/hotels', [
            'cityId' => $request->query('cityId', '1506246'),
            'room1' => '2',
        ]);

        return response()->json($response->json(), $response->status());
    }
}
