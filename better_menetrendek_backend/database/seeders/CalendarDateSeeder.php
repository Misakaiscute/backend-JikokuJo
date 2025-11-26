<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarDateSeeder extends Seeder
{
    public function run(): void
    {        
        $handle = fopen(get_storage_path("calendar_dates.txt"), 'r');

        $batch = [];
        $batchSize = 1000;

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "service_id" => $item[0],
                "date" => $item[1],
                "exception_type" => $item[2],
            ];

            if (count($batch) >= $batchSize) {
                DB::table("calendar_dates")->upsert(
                    $batch,
                    ['mode', 'is_bidirectional', 'from_stop_id', 'to_stop_id', 'traversal_time']
                );
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table("calendar_dates")->upsert(
                $batch,
                ['mode', 'is_bidirectional', 'from_stop_id', 'to_stop_id', 'traversal_time']
            );
        }

        fclose($handle);
    }
}
