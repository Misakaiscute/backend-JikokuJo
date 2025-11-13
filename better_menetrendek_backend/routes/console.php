<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


Schedule::call(DatabaseSeeder::populate_database())
    ->twiceDaily(0, 12)
    ->when(function () {
        $datesInDatabase = DB::table('feed_info')->pluck('feed_end_date');
        foreach ($datesInDatabase as $dateStr) {
            $date = Carbon::parse($dateStr);
            if ($date->isToday() || $date->isTomorrow()) {
                return true;
            }
        }
        return false;
    });
