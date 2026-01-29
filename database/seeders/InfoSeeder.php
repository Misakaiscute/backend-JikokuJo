<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InfoSeeder extends Seeder
{
    public function run(): void
    {        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE agency');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $handle = fopen(get_storage_path("agency.txt"), 'r');

        $batch = [];
        $batchSize = 2000;

        $skip = true;
        while (($line = fgets($handle, 65536)) !== false) 
        {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            if (count($item) < 6) continue;

            $batch[] = 
            [
                "id" => ($item[0] === '' ? 0 : $item[0]),
                "name" => ($item[1] === '' ? 0 : $item[1]),
                "url" => ($item[2] === '' ? 0 : $item[2]),
                "time_zone" => ($item[3] === '' ? 0 : $item[3]),
                "lang" => ($item[4] === '' ? 0 : $item[4]),
                "phone" => ($item[5] === '' ? 0 : $item[5])
            ];

            if (count($batch) >= $batchSize) 
            {
                DB::table("agency")->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) 
        {
            DB::table("agency")->insert($batch);
        }

        fclose($handle);



        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE feed_info');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $handle = fopen(get_storage_path("feed_info.txt"), 'r');

        $batch = [];
        $batchSize = 2000;

        $skip = true;
        while (($line = fgets($handle, 65536)) !== false) 
        {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            if (count($item) < 7) continue;

            $batch[] = 
            [
                "id" => $item[0],
                "publisher_name" => $item[1],
                "publisher_url" => $item[2],
                "lang" => $item[3],
                "start_date" => $item[4],
                "end_date" => $item[5],
                "version" => ($item[6] === '' ? 0 : $item[6])
            ];

            if (count($batch) >= $batchSize) 
            {
                DB::table("feed_info")->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) 
        {
            DB::table("feed_info")->insert($batch);
        }

        fclose($handle);
    }
}
