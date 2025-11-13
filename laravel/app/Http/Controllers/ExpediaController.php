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
use App\Http\Requests\CreateItineraryRequest;
use App\Http\Resources\ExpediaResource;
use GuzzleHttp\Psr7\Query;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

        if (!$key || !$secret) {
            throw new \RuntimeException('Expedia API credentials are not configured.');
        }

        $signature = hash('sha512', $key . $secret . $timestamp);
        $authorization = sprintf(
            'EAN APIKey=%s,Signature=%s,timestamp=%s',
            $key,
            $signature,
            $timestamp
        );

        return [
            'timestamp' => $timestamp,
            'signature' => $signature,
            'authorization' => $authorization,
        ];
    }

    /**
     * Build a configured HTTP client for Expedia.
     */
    private function expediaClient(?Request $request = null): PendingRequest
    {
        $signature = $this->signRequest();
        $userAgent = config('services.expedia.user_agent', 'ExpediaLaravelDemo/1.0');
        $sessionId = optional($request)->header('X-Customer-Session-Id')
            ?? ($request && method_exists($request, 'hasSession') && $request->hasSession()
                ? $request->session()->getId()
                : (string) Str::uuid());

        return Http::baseUrl(config('services.expedia.base_url'))
            ->acceptJson()
            ->withHeaders(array_filter([
                'Accept-Encoding' => 'gzip',
                'User-Agent' => $userAgent,
                'Customer-Ip' => optional($request)?->ip() ?? '127.0.0.1',
                'Customer-Session-Id' => $sessionId,
                'Authorization' => $signature['authorization'],
            ]));
    }

    /**
     * Normalize Expedia query parameters and return a query string.
     */
    private function buildQueryString(array $params): string
    {
        $normalized = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                $clean = array_values(array_filter($value, fn ($item) => $item !== null && $item !== ''));

                if (!empty($clean)) {
                    $normalized[$key] = $clean;
                }
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            $normalized[$key] = $value;
        }

        if (empty($normalized)) {
            return '';
        }

        return Query::build($normalized);
    }

    private function getFromExpedia(?Request $request, string $path, array $params = [])
    {
        $client = $this->expediaClient($request);
        $options = [];
        $query = $this->buildQueryString($params);

        if ($query !== '') {
            $options['query'] = $query;
        }

        $response = $client->withOptions($options)->get($path);

        return $this->respond($response);
    }

    private function postToExpedia(?Request $request, string $path, array $query = [], array $body = [])
    {
        $client = $this->expediaClient($request);
        $options = [];
        $queryString = $this->buildQueryString($query);

        if ($queryString !== '') {
            $options['query'] = $queryString;
        }

        $response = $client->withOptions($options)->post($path, $body);

        return $this->respond($response);
    }

    private function deleteFromExpedia(?Request $request, string $path, array $params = [])
    {
        $client = $this->expediaClient($request);
        $options = [];
        $query = $this->buildQueryString($params);

        if ($query !== '') {
            $options['query'] = $query;
        }

        $response = $client->withOptions($options)->delete($path);

        return $this->respond($response);
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
        $params = $request->validated();

        try {
            return $this->getFromExpedia($request, '/regions', $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve hotel chains from Expedia Rapid API.
     */
    public function getChains(ChainsRequest $request)
    {
        $params = $request->validated();

        try {
            return $this->getFromExpedia($request, '/chains', $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve region information from Expedia Rapid API.
     */
    public function getRegion(Request $request, string $region_id)
    {
        $params = $request->only(['language', 'include', 'token']);

        try {
            return $this->getFromExpedia($request, "/regions/{$region_id}", $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve property content from Expedia Rapid API.
     */
    public function getPropertyContent(PropertyContentRequest $request)
    {
        $params = $request->validated();

        try {
            return $this->getFromExpedia($request, '/properties/content', $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve guest reviews for a property from Expedia Rapid API.
     */
    public function getGuestReviews(GuestReviewsRequest $request, string $property_id)
    {
        $params = Arr::except($request->validated(), ['property_id']);
        $id = $property_id;

        try {
            return $this->getFromExpedia($request, "/properties/{$id}/guest-reviews", $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve property availability from Expedia Rapid API.
     */
    public function getAvailability(AvailabilityRequest $request)
    {
        $params = $request->validated();

        try {
            return $this->getFromExpedia($request, '/properties/availability', $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve properties by polygon from Expedia Rapid API.
     */
    public function getPropertiesByPolygon(PropertiesByPolygonRequest $request)
    {
        $validated = $request->validated();
        $query = Arr::only($validated, ['include', 'supply_source', 'billing_terms', 'partner_point_of_sale', 'payment_terms', 'platform_name']);
        $body = $validated['geojson'];

        try {
            return $this->postToExpedia($request, '/properties/geography', $query, $body);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve inactive properties from Expedia Rapid API.
     */
    public function getInactiveProperties(InactivePropertiesRequest $request)
    {
        $params = $request->validated();

        try {
            return $this->getFromExpedia($request, '/properties/inactive', $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download property content file from Expedia.
     */
    public function downloadPropertyContent(DownloadPropertyContentRequest $request)
    {
        $params = $request->validated();

        try {
            return $this->getFromExpedia($request, '/files/properties/content', $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Download property catalog from Expedia API.
     */
    public function downloadPropertyCatalog(DownloadPropertyCatalogRequest $request)
    {
        $params = $request->validated();

        try {
            return $this->getFromExpedia($request, '/files/properties/catalog', $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create an itinerary (booking) with Expedia Rapid API.
     */
    public function createItinerary(CreateItineraryRequest $request)
    {
        $payload = $request->validated();

        try {
            return $this->postToExpedia($request, '/itineraries', [], $payload);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve itinerary details.
     */
    public function getItinerary(Request $request, string $itinerary_id)
    {
        $params = $request->only(['language', 'currency']);

        try {
            return $this->getFromExpedia($request, "/itineraries/{$itinerary_id}", $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Cancel an itinerary.
     */
    public function cancelItinerary(Request $request, string $itinerary_id)
    {
        $params = $request->only(['language']);

        try {
            return $this->deleteFromExpedia($request, "/itineraries/{$itinerary_id}", $params);
        } catch (Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
