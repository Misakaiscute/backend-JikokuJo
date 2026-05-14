<?php

namespace Tests\Feature;

use App\Models\Stop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TripControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function get_trips_by_route_id_requires_valid_route()
    {
        $response = $this->postJson('/api/route/trip', [
            'date' => '20260513',
            'time' => '0800',
            'route_id' => 'NON_EXISTENT',
        ]);

        // Should return empty trips or error (no data available)
        $this->assertTrue(
            in_array($response->getStatusCode(), [200, 206]),
            'Should handle non-existent route gracefully'
        );
    }

    #[Test]
    public function invalid_date_format_returns_error()
    {
        $stop = Stop::factory()->create(['id' => 'STOP_BAD_DATE']);

        $response = $this->postJson('/api/stop/trip', [
            'ids' => $stop->id,
            'date' => '2026-05-13', // Wrong format
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('errors.0', 'Hibás dátum formátum (YYYYMMDD).');
    }

    #[Test]
    public function invalid_time_format_returns_error()
    {
        $stop = Stop::factory()->create(['id' => 'STOP_BAD_TIME']);

        $response = $this->postJson('/api/stop/trip', [
            'ids' => $stop->id,
            'date' => '20260513',
            'time' => '25:00', // Invalid time
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('errors.0', 'Hibás időformátum (HHMM).');
    }

    #[Test]
    public function get_trips_by_stop_id_rejects_missing_ids()
    {
        $response = $this->postJson('/api/stop/trip', [
            'date' => '20260103',
            'time' => '0900',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('errors.0', '"ids" paraméter megadása kötelező (tömb vagy vesszővel elválasztott string).');
    }
}
