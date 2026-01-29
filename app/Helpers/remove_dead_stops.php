<?php

use Illuminate\Support\Facades\DB;


function remove_dead_stops()
{
    DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

    $deadStopIds = DB::table('stops')
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                  ->from('stop_times')
                  ->whereColumn('stop_times.stop_id', 'stops.id');
        })
        ->pluck('id')
        ->toArray();

    if (empty($deadStopIds)) 
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
        return;
    }

    DB::table('pathways')
        ->whereIn('from_stop_id', $deadStopIds)
        ->orWhereIn('to_stop_id', $deadStopIds)
        ->delete();

    DB::table('stops')
        ->whereIn('id', $deadStopIds)
        ->delete();

    DB::statement('SET FOREIGN_KEY_CHECKS = 1;');
}
