<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpediaController extends Controller
{
    /**
     * Generate timestamp and SHA512 signature for Expedia requests.
     */
    private function signRequest(): array
    {
        $timestamp = time();
        $key = config('services.expedia.key');
        $secret = config('services.expedia.shared_secret');

        $signature = hash('sha512', $key . $secret . $timestamp);

        return [
            'timestamp' => $timestamp,
            'signature' => $signature,
        ];
    }

    /**
     * Retrieve hotels from Expedia Rapid API.
     */
    public function searchHotels(Request $request)
    {
        $auth = $this->signRequest();
        $key = config('services.expedia.key');

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => "EAN apikey={$key},signature={$auth['signature']},timestamp={$auth['timestamp']}",
        ])->get('https://test.expediapartnercentral.com/rapid/hotels', [
            'cityId' => $request->query('cityId', '1506246'),
            'room1' => '2',
        ]);

        return response()->json($response->json(), $response->status());
    }

    /**
     * Retrieve region information from Expedia Rapid API.
     */
    public function getRegion(Request $request, string $region_id)
    {
        $params = $request->only(['language', 'include']);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . config('services.expedia.key'),
        ])->get("https://test.expediapartnercentral.com/rapid/regions/{$region_id}", $params);

        return response()->json($response->json(), $response->status());
    }
}
