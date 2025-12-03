<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class SearchController extends Controller
{
    public function searchable()
    {
        try {
            $stops = DB::table('stops')
                ->select('name', DB::raw('GROUP_CONCAT(id) as ids'))
                ->groupBy('name')
                ->get();
        
            $routeNames = DB::table("routes")->pluck("short_name");
        
            return response()->json([
                'data' => [
                    'stops' => $stops,
                    'routes'=> $routeNames,
                ],
                'errors' => []
            ], 200);          
        }
        catch(\Exception $e) 
        {
            $stops = DB::table("stops")->distinct()->pluck("name", "id");
            $routeNames = DB::table("routes")->distinct()->pluck("short_name");
        
            return response()->json([
                'data' => [
                    'stops' => $stops,
                    'routes'=> $routeNames,
                ],
                'errors' => [
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }
}
