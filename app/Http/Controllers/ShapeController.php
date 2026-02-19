<?php

namespace App\Http\Controllers;

use App\Models\Route;
use App\Models\Shape;
use App\Models\Stop;
use App\Models\StopTime;
use App\Models\Trip;

class ShapeController extends Controller
{
    public function getShapesByTripId(string $trip_id)
    {
        $trip = Trip::select('shape_id')->find($trip_id);

        if (!$trip || !$trip->shape_id) 
        {
            return response()->json([
                'data' => ['points' => []],
                'errors' => ['Nincs shape_id a megadott járathoz.']
            ], 404, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $shapeId = $trip->shape_id;

        $points = Shape::where('id', $shapeId)
            ->orderBy('dist_traveled')
            ->select('dist_traveled', 'pt_lat', 'pt_lon')
            ->get()
            ->map(function ($point) {
                return [
                    'distance_traveled' => (int) $point->dist_traveled,
                    'location' => [
                        'lat' => (float) $point->pt_lat,
                        'lon' => (float) $point->pt_lon,
                    ],
                ];
            })->values();

        return response()->json([
            'data' => [
                'shape_id' => $shapeId,
                'points' => $points,
            ],
            'errors' => []
        ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    
}
