<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShapeSeeder extends Seeder
{
    public function run(): void
    {        
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::statement('TRUNCATE TABLE shapes');
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $handle = fopen(get_storage_path("shapes.txt"), 'r');

        $batch = [];
        $batchSize = 2000;

        $skip = true;
        while (($line = fgets($handle, 65536)) !== false) 
        {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            if (count($item) < 5) continue;

            $batch[] = 
            [
                "id" => ($item[0] === '' ? 0 : $item[0]),
                "pt_sequence" => ($item[1] === '' ? 0 : $item[1]),
                "pt_lat" => ($item[2] === '' ? 0 : $item[2]),
                "pt_lon" => ($item[3] === '' ? 0 : $item[3]),
                "dist_traveled" => ($item[4] === '' ? 0 : $item[4])
            ];

            if (count($batch) >= $batchSize) 
            {
                DB::table("shapes")->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) 
        {
            DB::table("shapes")->insert($batch);
        }

        fclose($handle);
    }
}
