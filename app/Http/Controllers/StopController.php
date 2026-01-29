<?php

namespace App\Http\Controllers;

use App\Models\Trip;

class StopController extends Controller
{
    public function getStopsByTripId(string $trip_id)
    {
        $trip = Trip::query()
            ->with([
                'stopTimes' => fn($q) => $q
                    ->select('trip_id', 'stop_id', 'arrival_time', 'stop_sequence')
                    ->orderBy('stop_sequence'),
                'stopTimes.stop' => fn($q) => $q
                    ->select('id', 'name', 'lat', 'lon')
            ])
            ->find($trip_id);

        if (!$trip) 
        {
            return response()->json([
                'data' => ['stops' => []],
                'errors' => ['Járat nem található.']
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $stops = $trip->stopTimes->map(function ($stopTime) {
            return [
                'id'            => $stopTime->stop_id,
                'stop_sequence' => (int) ($stopTime->stop_sequence ?? 0),
                'name'          => $stopTime->stop->name ?? 'Ismeretlen megálló',
                'arrival_time'  => $stopTime->arrival_time,
                'location'      => [
                    'lat' => (float) ($stopTime->stop->lat ?? 0),
                    'lon' => (float) ($stopTime->stop->lon ?? 0),
                ],
            ];
        })->values();

        return response()->json([
            'data' => [
                'stops' => $stops,
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
