<?php

use Illuminate\Support\Facades\Artisan;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schedule;


// Schedule::call(DatabaseSeeder::seed_database())
//     ->twiceDaily(0, 12)
//     ->when(function () {
//         $datesInDatabase = DB::table('feed_info')->pluck('feed_end_date');
//         foreach ($datesInDatabase as $dateStr) {
//             $date = Carbon::parse($dateStr);
//             if ($date->isToday() || $date->isTomorrow()) {
//                 return true;
//             }
//         }
//         return false;
//     });

Schedule::command('sanctum:prune-expired --hours=24')->daily();
