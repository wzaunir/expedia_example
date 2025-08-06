<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

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
        $validator = Validator::make($request->all(), [
            'cityId' => 'required|integer',
            'checkin' => 'required|date_format:Y-m-d',
            'checkout' => 'required|date_format:Y-m-d|after:checkin',
            'room1' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $params = $validator->validated();

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Authorization' => 'Bearer ' . config('services.expedia.key'),
        ])->get('https://test.expediapartnercentral.com/rapid/hotels', $params);

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

    /**
     * Retrieve itinerary information from Expedia Rapid API.
     */
    public function retrieveItinerary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'affiliate_reference_id' => 'required|string',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $params = $validator->validated();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get('https://test.expediapartnercentral.com/rapid/itineraries', $params);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Network error',
            ], 500);
        }

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => false,
            'message' => $response->json('message', 'Request failed'),
        ], $response->status());
    }

    /**
     * Cancel an itinerary using the provided cancellation link.
     */
    public function cancelItinerary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cancel_link' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $params = $validator->validated();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->delete($params['cancel_link']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Network error',
            ], 500);
        }

        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json(),
            ], $response->status());
        }

        return response()->json([
            'success' => false,
            'message' => $response->json('message', 'Request failed'),
        ], $response->status());
    }
}
