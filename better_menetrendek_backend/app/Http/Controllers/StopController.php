<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Trip;

class StopController extends Controller
{
    public function getRoutesForStopId(string $stopId)
    {
        $results = DB::table('stop_times as st')
            ->join('trips as t', 'st.trip_id', '=', 't.id')
            ->join('routes as r', 't.route_id', '=', 'r.id')
            ->where('st.stop_id', $stopId)
            ->select([
                'r.id',
                'r.short_name',
                'r.type',
                'st.arrival_time',
                't.shape_id',
                't.trip_headsign'
            ])
            ->orderBy('r.short_name')
            ->orderBy('st.departure_time')
            ->get()
            ->groupBy('route_id')
            ->map(function ($group) {
                $first = $group->first();
    
                return [
                    'route_id'          => $first->id,
                    'short_name'  => $first->short_name,
                    'type'        => $first->type,
                    'trips' => $group->map(function ($item) {
                        return [
                            'arrival_time'    => $item->arrival_time,
                            'shape_id'        => $item->shape_id,
                            'trip_headsign'   => $item->trip_headsign,
                        ];
                    })->values()->all()
                ];
            })->values()->all();
    
        return response()->json([
            'data' => $results,
            'errors' => [], 200, [],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ]);
    }
}
