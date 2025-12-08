<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Server;
use App\Services\GPSService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GPSServiceTest extends TestCase
{

    protected GPSService $gpsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gpsService = new GPSService;
        Config::set('app.gps_api_key', 'test-api-key');
    }

    /** @test */
    public function it_can_discover_projects_within_radius(): void
    {
        // Create projects at different locations
        $nearProject = Project::factory()->create([
            'name' => 'Near Project',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $farProject = Project::factory()->create([
            'name' => 'Far Project',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $projectWithoutLocation = Project::factory()->create([
            'name' => 'No Location Project',
            'latitude' => null,
            'longitude' => null,
        ]);

        // Search from Cairo center with 10km radius
        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 10);

        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Near Project', $results[0]['name']);
        $this->assertEquals(30.0444, $results[0]['latitude']);
        $this->assertEquals(31.2357, $results[0]['longitude']);
        $this->assertArrayHasKey('distance', $results[0]);
        $this->assertArrayHasKey('status', $results[0]);
    }

    /** @test */
    public function it_orders_discovered_projects_by_distance(): void
    {
        // Create projects at different distances
        Project::factory()->create([
            'name' => 'Close Project',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Project::factory()->create([
            'name' => 'Medium Project',
            'latitude' => 30.0600,
            'longitude' => 31.2500,
        ]);

        Project::factory()->create([
            'name' => 'Far Project',
            'latitude' => 30.0800,
            'longitude' => 31.2800,
        ]);

        // Search from Cairo center with large radius
        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 100);

        $this->assertCount(3, $results);
        $this->assertEquals('Close Project', $results[0]['name']);
        $this->assertLessThan($results[1]['distance'], $results[0]['distance']);
        $this->assertLessThan($results[2]['distance'], $results[1]['distance']);
    }

    /** @test */
    public function it_excludes_projects_without_coordinates(): void
    {
        Project::factory()->create([
            'name' => 'With Location',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Project::factory()->create([
            'name' => 'Null Latitude',
            'latitude' => null,
            'longitude' => 31.2357,
        ]);

        Project::factory()->create([
            'name' => 'Null Longitude',
            'latitude' => 30.0444,
            'longitude' => null,
        ]);

        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 10);

        $this->assertCount(1, $results);
        $this->assertEquals('With Location', $results[0]['name']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_projects_in_radius(): void
    {
        Project::factory()->create([
            'name' => 'Far Away Project',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Search from Cairo with small radius
        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 1);

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_rounds_distance_to_two_decimal_places(): void
    {
        Project::factory()->create([
            'name' => 'Test Project',
            'latitude' => 30.0500,
            'longitude' => 31.2400,
        ]);

        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 10);

        $this->assertCount(1, $results);
        $this->assertIsFloat($results[0]['distance']);
        $this->assertMatchesRegularExpression('/^\d+\.\d{1,2}$/', (string) $results[0]['distance']);
    }

    /** @test */
    public function it_can_discover_servers_within_radius(): void
    {
        Server::factory()->create([
            'name' => 'Near Server',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Server::factory()->create([
            'name' => 'Far Server',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        Server::factory()->create([
            'name' => 'No Location Server',
            'latitude' => null,
            'longitude' => null,
        ]);

        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 10);

        $this->assertNotEmpty($results);
        $this->assertCount(1, $results);
        $this->assertEquals('Near Server', $results[0]['name']);
        $this->assertEquals(30.0444, $results[0]['latitude']);
        $this->assertEquals(31.2357, $results[0]['longitude']);
        $this->assertArrayHasKey('distance', $results[0]);
        $this->assertArrayHasKey('status', $results[0]);
    }

    /** @test */
    public function it_orders_discovered_servers_by_distance(): void
    {
        Server::factory()->create([
            'name' => 'Close Server',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Server::factory()->create([
            'name' => 'Medium Server',
            'latitude' => 30.0600,
            'longitude' => 31.2500,
        ]);

        Server::factory()->create([
            'name' => 'Far Server',
            'latitude' => 30.0800,
            'longitude' => 31.2800,
        ]);

        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 100);

        $this->assertCount(3, $results);
        $this->assertEquals('Close Server', $results[0]['name']);
        $this->assertLessThan($results[1]['distance'], $results[0]['distance']);
        $this->assertLessThan($results[2]['distance'], $results[1]['distance']);
    }

    /** @test */
    public function it_excludes_servers_without_coordinates(): void
    {
        Server::factory()->create([
            'name' => 'With Location',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Server::factory()->create([
            'name' => 'Null Latitude',
            'latitude' => null,
            'longitude' => 31.2357,
        ]);

        Server::factory()->create([
            'name' => 'Null Longitude',
            'latitude' => 30.0444,
            'longitude' => null,
        ]);

        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 10);

        $this->assertCount(1, $results);
        $this->assertEquals('With Location', $results[0]['name']);
    }

    /** @test */
    public function it_returns_empty_array_when_no_servers_in_radius(): void
    {
        Server::factory()->create([
            'name' => 'Far Away Server',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 1);

        $this->assertEmpty($results);
    }

    /** @test */
    public function it_can_get_location_name_from_coordinates(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => [
                    'city' => 'Cairo',
                    'state' => 'Cairo Governorate',
                    'country' => 'Egypt',
                ],
            ], 200),
        ]);

        $locationName = $this->gpsService->getLocationName(30.0444, 31.2357);

        $this->assertNotNull($locationName);
        $this->assertEquals('Cairo, Cairo Governorate, Egypt', $locationName);
    }

    /** @test */
    public function it_returns_location_name_with_only_city_and_country(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => [
                    'city' => 'Cairo',
                    'country' => 'Egypt',
                ],
            ], 200),
        ]);

        $locationName = $this->gpsService->getLocationName(30.0444, 31.2357);

        $this->assertEquals('Cairo, Egypt', $locationName);
    }

    /** @test */
    public function it_returns_location_name_with_only_country(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => [
                    'country' => 'Egypt',
                ],
            ], 200),
        ]);

        $locationName = $this->gpsService->getLocationName(30.0444, 31.2357);

        $this->assertEquals('Egypt', $locationName);
    }

    /** @test */
    public function it_returns_null_when_api_key_not_configured(): void
    {
        Config::set('app.gps_api_key', null);
        $service = new GPSService;

        $locationName = $service->getLocationName(30.0444, 31.2357);

        $this->assertNull($locationName);
        Http::assertNothingSent();
    }

    /** @test */
    public function it_returns_null_when_reverse_geocoding_fails(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([], 500),
        ]);

        $locationName = $this->gpsService->getLocationName(30.0444, 31.2357);

        $this->assertNull($locationName);
    }

    /** @test */
    public function it_returns_null_when_address_data_missing(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => [],
            ], 200),
        ]);

        $locationName = $this->gpsService->getLocationName(30.0444, 31.2357);

        $this->assertNull($locationName);
    }

    /** @test */
    public function it_logs_error_when_reverse_geocoding_throws_exception(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Reverse geocoding failed', \Mockery::on(function ($context) {
                return isset($context['error']);
            }));

        Http::fake([
            'nominatim.openstreetmap.org/*' => function () {
                throw new \Exception('Network error');
            },
        ]);

        $locationName = $this->gpsService->getLocationName(30.0444, 31.2357);

        $this->assertNull($locationName);
    }

    /** @test */
    public function it_makes_correct_api_request_to_nominatim(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/*' => Http::response([
                'address' => [
                    'city' => 'Cairo',
                    'country' => 'Egypt',
                ],
            ], 200),
        ]);

        $this->gpsService->getLocationName(30.0444, 31.2357);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://nominatim.openstreetmap.org/reverse' &&
                   $request['lat'] === 30.0444 &&
                   $request['lon'] === 31.2357 &&
                   $request['format'] === 'json';
        });
    }

    /** @test */
    public function it_can_calculate_distance_between_two_points(): void
    {
        // Distance between Cairo and Alexandria (approx 179 km)
        $distance = $this->gpsService->calculateDistance(
            30.0444, 31.2357, // Cairo
            31.2001, 29.9187  // Alexandria
        );

        $this->assertGreaterThan(170, $distance);
        $this->assertLessThan(190, $distance);
    }

    /** @test */
    public function it_returns_zero_for_same_location(): void
    {
        $distance = $this->gpsService->calculateDistance(
            30.0444, 31.2357,
            30.0444, 31.2357
        );

        $this->assertEquals(0.0, $distance);
    }

    /** @test */
    public function it_calculates_short_distances_accurately(): void
    {
        // Very close points (should be less than 1 km)
        $distance = $this->gpsService->calculateDistance(
            30.0444, 31.2357,
            30.0450, 31.2360
        );

        $this->assertLessThan(1, $distance);
        $this->assertGreaterThan(0, $distance);
    }

    /** @test */
    public function it_calculates_long_distances_accurately(): void
    {
        // Distance between Cairo and New York (approx 9,000 km)
        $distance = $this->gpsService->calculateDistance(
            30.0444, 31.2357,  // Cairo
            40.7128, -74.0060  // New York
        );

        $this->assertGreaterThan(8900, $distance);
        $this->assertLessThan(9100, $distance);
    }

    /** @test */
    public function it_rounds_calculated_distance_to_two_decimals(): void
    {
        $distance = $this->gpsService->calculateDistance(
            30.0444, 31.2357,
            30.0500, 31.2400
        );

        $this->assertMatchesRegularExpression('/^\d+\.\d{1,2}$/', (string) $distance);
    }

    /** @test */
    public function it_handles_negative_longitude_in_distance_calculation(): void
    {
        // Cairo to London (negative longitude)
        $distance = $this->gpsService->calculateDistance(
            30.0444, 31.2357,   // Cairo
            51.5074, -0.1278    // London
        );

        $this->assertGreaterThan(3500, $distance);
        $this->assertLessThan(3700, $distance);
    }

    /** @test */
    public function it_handles_negative_latitude_in_distance_calculation(): void
    {
        // Cairo to Sydney (negative latitude)
        $distance = $this->gpsService->calculateDistance(
            30.0444, 31.2357,    // Cairo
            -33.8688, 151.2093   // Sydney
        );

        $this->assertGreaterThan(14000, $distance);
        $this->assertLessThan(15000, $distance);
    }

    /** @test */
    public function it_handles_equator_crossing_in_distance_calculation(): void
    {
        // Singapore (near equator) to Cairo
        $distance = $this->gpsService->calculateDistance(
            1.3521, 103.8198,  // Singapore
            30.0444, 31.2357   // Cairo
        );

        $this->assertGreaterThan(7800, $distance);
        $this->assertLessThan(8200, $distance);
    }

    /** @test */
    public function it_calculates_distance_with_extreme_coordinates(): void
    {
        // North pole to South pole
        $distance = $this->gpsService->calculateDistance(
            90.0, 0.0,   // North Pole
            -90.0, 0.0   // South Pole
        );

        // Half circumference of Earth (approx 20,000 km)
        $this->assertGreaterThan(19900, $distance);
        $this->assertLessThan(20100, $distance);
    }

    /** @test */
    public function it_supports_custom_radius_for_project_discovery(): void
    {
        Project::factory()->create([
            'name' => 'Project at 5km',
            'latitude' => 30.0444,
            'longitude' => 31.2800,
        ]);

        // Search with 3km radius - should find nothing
        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 3);
        $this->assertEmpty($results);

        // Search with 10km radius - should find project
        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 10);
        $this->assertNotEmpty($results);
    }

    /** @test */
    public function it_supports_custom_radius_for_server_discovery(): void
    {
        Server::factory()->create([
            'name' => 'Server at 5km',
            'latitude' => 30.0444,
            'longitude' => 31.2800,
        ]);

        // Search with 3km radius - should find nothing
        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 3);
        $this->assertEmpty($results);

        // Search with 10km radius - should find server
        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 10);
        $this->assertNotEmpty($results);
    }

    /** @test */
    public function it_includes_project_id_in_discovery_results(): void
    {
        $project = Project::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 10);

        $this->assertCount(1, $results);
        $this->assertEquals($project->id, $results[0]['id']);
    }

    /** @test */
    public function it_includes_server_id_in_discovery_results(): void
    {
        $server = Server::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 10);

        $this->assertCount(1, $results);
        $this->assertEquals($server->id, $results[0]['id']);
    }

    /** @test */
    public function it_includes_project_status_in_discovery_results(): void
    {
        Project::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'status' => 'running',
        ]);

        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 10);

        $this->assertCount(1, $results);
        $this->assertEquals('running', $results[0]['status']);
    }

    /** @test */
    public function it_includes_server_status_in_discovery_results(): void
    {
        Server::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'status' => 'online',
        ]);

        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 10);

        $this->assertCount(1, $results);
        $this->assertEquals('online', $results[0]['status']);
    }

    /** @test */
    public function it_handles_very_large_radius_for_global_search(): void
    {
        Project::factory()->create([
            'name' => 'Project 1',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        Project::factory()->create([
            'name' => 'Project 2',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
        ]);

        // Search with very large radius (half Earth circumference)
        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 20000);

        $this->assertCount(2, $results);
    }

    /** @test */
    public function it_discovers_multiple_projects_and_orders_them_correctly(): void
    {
        $project1 = Project::factory()->create([
            'name' => 'Project 1',
            'latitude' => 30.0450,
            'longitude' => 31.2360,
        ]);

        $project2 = Project::factory()->create([
            'name' => 'Project 2',
            'latitude' => 30.0600,
            'longitude' => 31.2500,
        ]);

        $project3 = Project::factory()->create([
            'name' => 'Project 3',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $results = $this->gpsService->discoverProjects(30.0444, 31.2357, 100);

        $this->assertCount(3, $results);
        // Project 3 should be first (exact match = 0 km)
        $this->assertEquals('Project 3', $results[0]['name']);
        $this->assertEquals(0.0, $results[0]['distance']);
        // Verify ordering
        for ($i = 1; $i < count($results); $i++) {
            $this->assertGreaterThanOrEqual($results[$i - 1]['distance'], $results[$i]['distance']);
        }
    }

    /** @test */
    public function it_discovers_multiple_servers_and_orders_them_correctly(): void
    {
        Server::factory()->create([
            'name' => 'Server 1',
            'latitude' => 30.0450,
            'longitude' => 31.2360,
        ]);

        Server::factory()->create([
            'name' => 'Server 2',
            'latitude' => 30.0600,
            'longitude' => 31.2500,
        ]);

        Server::factory()->create([
            'name' => 'Server 3',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
        ]);

        $results = $this->gpsService->discoverServers(30.0444, 31.2357, 100);

        $this->assertCount(3, $results);
        // Server 3 should be first (exact match = 0 km)
        $this->assertEquals('Server 3', $results[0]['name']);
        $this->assertEquals(0.0, $results[0]['distance']);
        // Verify ordering
        for ($i = 1; $i < count($results); $i++) {
            $this->assertGreaterThanOrEqual($results[$i - 1]['distance'], $results[$i]['distance']);
        }
    }
}
