<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpediaController extends Controller
{
    /**
     * Retrieve hotels from Expedia Rapid API.
     */
    public function searchHotels(Request $request)
    {
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get('https://test.expediapartnercentral.com/rapid/hotels', [
                'cityId' => $request->query('cityId', '1506246'),
                'room1' => '2',
            ]);

            if ($response->successful()) {
                return response()->json($response->json(), $response->status());
            }

            Log::error('Expedia API returned error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'message' => 'Expedia API error',
                'status' => $response->status(),
            ], $response->status());
        } catch (\Throwable $e) {
            Log::error('Expedia API request failed', ['exception' => $e->getMessage()]);
            return response()->json([
                'message' => 'Expedia API request failed',
                'status' => 500,
            ], 500);
        }
    }
}
