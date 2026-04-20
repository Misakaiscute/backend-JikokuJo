<?php

namespace Tests\Feature;

use App\Jobs\PollVehiclePosition;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChannelVehicleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_ping_returns_error_for_invalid_channel()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/channel-activity', ['channel' => 'invalid-channel']);

        $response->assertStatus(422)
            ->assertJson(['error' => 'Invalid channel']);
    }

    public function test_ping_returns_ok_for_valid_channel()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        $response = $this->postJson('/api/channel-activity', ['channel' => 'trip.T10']);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'ok',
                'channel' => 'trip.T10',
                'expires_in' => 120,
            ]);
    }

    public function test_start_polling_dispatches_job_for_existing_trip()
    {
        Bus::fake();

        $trip = Trip::factory()->create(['id' => 'T6']);

        $response = $this->getJson("/api/vehicle-positions/poll/{$trip->id}");

        $response->assertStatus(200)
            ->assertJson(['trip_id' => $trip->id]);

        Bus::assertDispatched(PollVehiclePosition::class);
    }

    public function test_start_polling_returns_error_for_missing_trip()
    {
        $response = $this->getJson('/api/vehicle-positions/poll/missing-trip');

        $response->assertStatus(400)
            ->assertJson(['error' => 'Helytelen trip ID']);
    }
}
