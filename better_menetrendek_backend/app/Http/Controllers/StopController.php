<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StopController extends Controller
{
    public function index_stops()
    {
        $stops = DB::table("stops")->distinct()->pluck("name");
        return response()->json([
            'stops' => $stops
        ]);
    }
}
