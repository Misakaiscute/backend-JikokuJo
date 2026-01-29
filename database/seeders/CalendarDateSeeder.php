<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalendarDateSeeder extends Seeder
{
    public function run(): void
    {        
        sanitize_input("calendar_dates.txt");

        $filePath = get_storage_path("calendar_dates.txt");
        $handle = fopen($filePath, 'r');

        if ($handle === false) 
        {
            $this->command->error("Nem sikerült megnyitni a calendar_dates.txt fájlt!");
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE calendar_dates');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $batch = [];
        $batchSize = 2000;
        $totalRows = 0;

        $skip = true;
        while (($line = fgets($handle, 65536)) !== false) 
        {
            if ($skip) 
            { 
                $skip = false; 
                continue; 
            }

            $item = explode(",", trim($line));

            if (count($item) < 3) 
            {
                continue;
            }

            $batch[] = 
            [
                "service_id"      => $item[0],
                "date"            => $item[1],
                "exception_type"  => $item[2],
            ];

            $totalRows++;

            if (count($batch) >= $batchSize) 
            {
                DB::table("calendar_dates")->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) 
        {
            DB::table("calendar_dates")->insert($batch);
        }
        fclose($handle);
    }
}
