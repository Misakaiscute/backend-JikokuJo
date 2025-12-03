<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StopController extends Controller
{
    public function index_stops()
    {
        // try
        // {
        //     $stops = DB::table("stops")->distinct()->pluck("name");
        //     return response()->json([
        //         'stops' => $stops
        //     ],
        //     []);
        // }
        // catch(\Exception $e)
        // {
        //     return response()->json([
        //         'stops' => $stops
        //     ],
        //     ['error' => $e->getMessage()]);
        // }
    }
}
