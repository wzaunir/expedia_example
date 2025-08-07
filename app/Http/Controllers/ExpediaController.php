<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchHotelsRequest;
use App\Http\Requests\ChainsRequest;
use App\Http\Requests\PropertyContentRequest;
use App\Http\Requests\GuestReviewsRequest;
use App\Http\Requests\AvailabilityRequest;
use App\Http\Requests\PropertiesByPolygonRequest;
use App\Http\Requests\InactivePropertiesRequest;
use App\Http\Requests\DownloadPropertyContentRequest;
use App\Http\Requests\DownloadPropertyCatalogRequest;
use App\Http\Resources\ExpediaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

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
     * Format API responses using Laravel resources.
     */
    private function respond($response)
    {
        $data = $response->json();
        $resource = is_array($data) && array_is_list($data)
            ? ExpediaResource::collection($data)
            : new ExpediaResource($data);

        return $resource->response()->setStatusCode($response->status());
    }

    /**
     * Retrieve hotels from Expedia Rapid API.
     */
    public function searchHotels(SearchHotelsRequest $request)
    {
        $request->validateResolved();
        $params = $request->validated();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get('https://test.expediapartnercentral.com/rapid/hotels', $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve hotel chains from Expedia Rapid API.
     */
    public function getChains(ChainsRequest $request)
    {
        $request->validateResolved();
        $params = $request->validated();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get('https://test.expediapartnercentral.com/rapid/chains', $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve region information from Expedia Rapid API.
     */
    public function getRegion(Request $request, string $region_id)
    {
        $params = $request->only(['language', 'include']);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get("https://test.expediapartnercentral.com/rapid/regions/{$region_id}", $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve property content from Expedia Rapid API.
     */
    public function getPropertyContent(PropertyContentRequest $request)
    {
        $request->validateResolved();
        $params = $request->validated();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get('https://test.expediapartnercentral.com/rapid/properties/content', $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve guest reviews for a property from Expedia Rapid API.
     */
    public function getGuestReviews(GuestReviewsRequest $request, string $property_id)
    {
        $request->merge(['property_id' => $property_id]);
        $request->validateResolved();
        $params = $request->validated();
        $id = $params['property_id'];
        unset($params['property_id']);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get("https://test.expediapartnercentral.com/rapid/properties/{$id}/guest-reviews", $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve property availability from Expedia Rapid API.
     */
    public function getAvailability(AvailabilityRequest $request)
    {
        $request->validateResolved();
        $params = $request->validated();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get('https://test.expediapartnercentral.com/rapid/properties/availability', $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve properties by polygon from Expedia Rapid API.
     */
    public function getPropertiesByPolygon(PropertiesByPolygonRequest $request)
    {
        $request->validateResolved();
        $validated = $request->validated();
        $geojson = $validated['geojson'];
        $params = array_intersect_key($validated, array_flip(['include', 'supply_source']));

        $url = 'https://test.expediapartnercentral.com/rapid/properties/geography';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->withBody($geojson, 'application/json')->post($url);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve inactive properties from Expedia Rapid API.
     */
    public function getInactiveProperties(InactivePropertiesRequest $request)
    {
        $request->validateResolved();
        $params = $request->validated();

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . config('services.expedia.key'),
            ])->get('https://test.expediapartnercentral.com/rapid/properties/inactive', $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download property content file from Expedia.
     */
    public function downloadPropertyContent(DownloadPropertyContentRequest $request)
    {
        $request->validateResolved();
        $params = array_merge($request->validated(), [
            'key' => config('services.expedia.key'),
        ], $this->signRequest());

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get('https://test.expediapartnercentral.com/files/properties/content', $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download property catalog from Expedia API.
     */
    public function downloadPropertyCatalog(DownloadPropertyCatalogRequest $request)
    {
        $request->validateResolved();
        $params = array_merge($request->validated(), [
            'key' => config('services.expedia.key'),
        ], $this->signRequest());

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
            ])->get('https://test.expediapartnercentral.com/files/properties/catalog', $params);

            return $this->respond($response);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
