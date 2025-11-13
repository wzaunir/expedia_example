<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExpediaControllerTest extends TestCase
{
    public function test_search_hotels_success()
    {
        Http::fake([
            'https://test.ean.com/v3/regions*' => Http::response([
                'regions' => [
                    ['id' => '178286', 'name' => 'Demo Region']
                ]
            ], 200),
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/hotels?ancestor_id=178286&language=en-US&include[]=property_ids');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('regions'));

        Http::assertSent(function ($request) {
            $auth = $request->header('Authorization');
            $auth = is_array($auth) ? $auth[0] : $auth;
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://test.ean.com/v3/regions')
                && str_contains($auth, 'EAN APIKey=demo-key')
                && $query['ancestor_id'] === '178286'
                && $query['language'] === 'en-US'
                && $query['include'] === 'property_ids';
        });
    }

    public function test_search_hotels_requires_parameters()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/hotels');

        $response->assertStatus(422);
    }

    public function test_get_chains_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/chains*' => Http::response([
                'chains' => [
                    ['id' => '1', 'name' => 'Demo Chain']
                ],
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/chains?limit=10&token=prev');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('chains'));

        Http::assertSent(fn($request) => str_starts_with($request->url(), 'https://test.ean.com/v3/chains'));
    }

    public function test_get_chains_validates_parameters()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/chains?limit=abc');

        $response->assertStatus(422);
    }

    public function test_get_region_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/regions/*' => Http::response([
                'id' => '1',
                'name' => 'Demo Region'
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/region/1?language=en-US&include=details');

        $response->assertStatus(200);
        $this->assertEquals('Demo Region', $response->json('name'));
    }

    public function test_get_property_content_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/content*' => Http::response([
                '123' => ['name' => 'Demo Property']
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/property-content?property_id[]=123&language=en-US&supply_source=expedia&include=details');

        $response->assertStatus(200);
        $this->assertEquals('Demo Property', data_get($response->json(), '0.name'));
    }

    public function test_get_property_content_requires_property_id()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/property-content');

        $response->assertStatus(422);
    }

    public function test_get_guest_reviews_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/123/guest-reviews*' => Http::response([
                'reviews' => [
                    ['id' => '1', 'comment' => 'Great stay']
                ]
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/properties/123/guest-reviews?language=en-US');

        $response->assertStatus(200);
        $this->assertEquals('Great stay', $response->json('reviews.0.comment'));
    }

    public function test_get_guest_reviews_validates_property_id()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/properties/abc/guest-reviews?language=en-US');

        $response->assertStatus(422);
    }

    public function test_get_availability_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/availability*' => Http::response([
                'property_id' => '123',
                'available' => true
            ], 200)
        ]);

        $query = http_build_query([
            'property_id' => ['123'],
            'checkin' => '2024-09-01',
            'checkout' => '2024-09-05',
            'language' => 'en-US',
            'currency' => 'USD',
            'country_code' => 'US',
            'occupancy' => ['2'],
            'rate_plan_count' => 1,
            'sales_channel' => 'website',
            'sales_environment' => 'hotel_only',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/properties/availability?'.$query);

        $response->assertStatus(200);
        $this->assertTrue($response->json('available'));
    }

    public function test_get_availability_requires_parameters()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/properties/availability');

        $response->assertStatus(422);
    }

    public function test_get_inactive_properties_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/properties/inactive*' => Http::response([
                'properties' => [
                    ['id' => '1']
                ]
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/properties/inactive?since=2024-09-01&limit=10&token=abc');

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json('properties'));
    }

    public function test_get_inactive_properties_requires_since()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/properties/inactive');

        $response->assertStatus(422);
    }

    public function test_download_property_content_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/files/properties/content*' => Http::response([
                'content_url' => 'https://example.com/file.zip'
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/files/property-content?language=en-US&supply_source=expedia');

        $response->assertStatus(200);
        $this->assertEquals('https://example.com/file.zip', $response->json('content_url'));
    }

    public function test_download_property_content_requires_parameters()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/files/property-content');

        $response->assertStatus(422);
    }

    public function test_download_property_catalog_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/files/properties/catalog*' => Http::response([
                'catalog' => 'data'
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/files/properties/catalog?language=en-US&supply_source=expedia');

        $response->assertStatus(200);
        $this->assertEquals('data', $response->json('catalog'));
    }

    public function test_create_itinerary_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/itineraries' => Http::response([
                'itinerary_id' => 'ABC123'
            ], 200)
        ]);

        $payload = [
            'email' => 'john@example.com',
            'phone' => [
                'country_code' => '1',
                'number' => '5550077',
            ],
            'rooms' => [
                [
                    'given_name' => 'John',
                    'family_name' => 'Doe',
                ],
            ],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/expedia/itineraries', $payload);

        $response->assertStatus(200);
        $this->assertEquals('ABC123', $response->json('itinerary_id'));

        Http::assertSent(fn($request) => $request->method() === 'POST'
            && $request->url() === 'https://test.ean.com/v3/itineraries');
    }

    public function test_create_itinerary_requires_minimum_fields()
    {
        Http::fake();

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/expedia/itineraries', []);

        $response->assertStatus(422);
    }

    public function test_get_itinerary_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/itineraries/ABC123*' => Http::response([
                'itinerary_id' => 'ABC123',
                'status' => 'booked'
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/expedia/itineraries/ABC123?language=en-US');

        $response->assertStatus(200);
        $this->assertEquals('booked', $response->json('status'));
    }

    public function test_cancel_itinerary_returns_response()
    {
        Http::fake([
            'https://test.ean.com/v3/itineraries/ABC123*' => Http::response([
                'itinerary_id' => 'ABC123',
                'status' => 'canceled'
            ], 200)
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson('/api/expedia/itineraries/ABC123?language=en-US');

        $response->assertStatus(200);
        $this->assertEquals('canceled', $response->json('status'));
    }

    private function authHeaders(): array
    {
        return [
            'X-API-TOKEN' => config('app.api_token', 'secret-token'),
        ];
    }
}
