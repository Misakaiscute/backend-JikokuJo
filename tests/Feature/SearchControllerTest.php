<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Stop;
use App\Models\Route;
use PHPUnit\Framework\Attributes\Test;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function returns_404_when_no_data()
    {
        $this->getJson('/api/queryables')
            ->assertStatus(404)
            ->assertJson([
                'data' => [
                    'stops' => [],
                    'routes' => [],
                ],
                'errors' => ['No data available']
            ]);
    }

    #[Test]
    public function returns_grouped_stops()
    {
        Stop::factory()->count(2)->create([
            'name' => 'Deák tér'
        ]);

        Stop::factory()->create([
            'name' => 'Astoria'
        ]);

        $this->getJson('/api/queryables')
            ->assertStatus(200)
            ->assertJsonFragment(['name' => 'Deák tér'])
            ->assertJsonFragment(['name' => 'Astoria']);
    }

    #[Test]
    public function groups_ids_correctly()
    {
        $stops = Stop::factory()->count(2)->create([
            'name' => 'Deák tér'
        ]);

        $ids = $stops->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->toArray();

        $this->getJson('/api/queryables')
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Deák tér',
                'ids' => $ids
            ]);
    }

    #[Test]
    public function correct_routes_mapping()
    {
        $metro = Route::factory()->create([
            'type' => 1,
            'short_name' => 'M1',
            'color' => '#FFD800',
        ]);

        $tram = Route::factory()->create([
            'type' => 0,
            'short_name' => '47',
            'color' => '#FF0000',
        ]);

        $this->getJson('/api/queryables')
            ->assertStatus(200)
            ->assertJsonFragment([
                'route_id' => $metro->id,
                'route_short_name' => 'M1',
                'type' => 3,
                'color' => '#FFD800'
            ])
            ->assertJsonFragment([
                'route_id' => $tram->id,
                'route_short_name' => '47',
                'type' => 2,
                'color' => '#FF0000'
            ]);
    }

    #[Test]
    public function correct_id_format_for_single_stop()
    {
        $stop = Stop::factory()->create([
            'name' => 'Test Stop'
        ]);

        $this->getJson('/api/queryables')
            ->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Test Stop',
                'ids' => [(string) $stop->id]
            ]);
    }

    #[Test]
    public function returns_both_stops_and_routes()
    {
        Stop::factory()->create([
            'name' => 'Kálvin tér'
        ]);

        Route::factory()->create([
            'type' => 3
        ]);

        $this->getJson('/api/queryables')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'stops' => [
                        ['name', 'ids']
                    ],
                    'routes' => [
                        ['route_id', 'short_name', 'type', 'color']
                    ]
                ],
                'errors'
            ]);
    }
}