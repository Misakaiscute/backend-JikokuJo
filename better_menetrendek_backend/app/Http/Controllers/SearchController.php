<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SearchController extends Controller
{
    public function queryables()
{
    $stops = DB::table('stops')
        ->select('name', DB::raw('GROUP_CONCAT(id) as ids'))
        ->groupBy('name')
        ->get();

    $routeNames = DB::table('routes')->pluck('short_name');

    if ($stops->isEmpty() && $routeNames->isEmpty()) {
        return response()->json([
            'data'   => [
                'stops'  => [],
                'routes' => []
            ],
            'errors' => [['error' => 'No data available']]
        ], 404);
    }

    return response()->json([
        'data'   => [
            'stops'  => $stops,
            'routes' => $routeNames
        ],
        'errors' => []
    ], 200);
}
}
