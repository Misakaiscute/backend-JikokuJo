<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Stop;
use App\Models\Route;

class SearchControllerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $sql = file_get_contents(database_path('/seeders/statements.sql'));
        \DB::unprepared($sql);
    }

    /** @test */
    public function test_returns_404_when_no_data()
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

    /** @test */
    public function test_returns_grouped_stops()
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

    /** @test */
    public function test_groups_ids_correctly()
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

    /** @test */
    public function test_correct_routes_mapping()
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

    /** @test */
    public function test_correct_id_format_for_single_stop()
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

    /** @test */
    public function test_returns_both_stops_and_routes()
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