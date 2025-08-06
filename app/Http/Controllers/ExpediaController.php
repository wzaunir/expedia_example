<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Log;

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


        try {

            $auth = $this->signRequest();
            $key = config('services.expedia.key');

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => "EAN apikey={$key},signature={$auth['signature']},timestamp={$auth['timestamp']}",
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

    /**
     * Retrieve property content from Expedia Rapid API.
     */
    public function getPropertyContent(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|integer',
            'language' => 'nullable|string',
            'include' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $params = $validator->validated();

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . config('services.expedia.key'),
        ])->get('https://test.expediapartnercentral.com/rapid/properties/content', $params);

        return response()->json($response->json(), $response->status());
    }

    /**
     * Retrieve property availability from Expedia Rapid API.
     */
    public function getAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'property_id' => 'required|integer',
            'checkin' => 'required|date_format:Y-m-d',
            'checkout' => 'required|date_format:Y-m-d|after:checkin',
            'occupancy' => 'required|string',
            'language' => 'nullable|string',
            'currency' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $params = $validator->validated();

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . config('services.expedia.key'),
        ])->get('https://test.expediapartnercentral.com/rapid/properties/availability', $params);

        return response()->json($response->json(), $response->status());
    }
}
