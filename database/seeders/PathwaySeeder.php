<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PathwaySeeder extends Seeder
{
    public function run(): void
    {
        sanitize_input("pathways.txt");
    
        $handle = fopen(get_storage_path("pathways.txt"), 'r');
        if ($handle === false) 
        {
            $this->command->error("Nem sikerült megnyitni a pathways.txt fájlt!");
            return;
        }
    
        DB::statement('TRUNCATE TABLE pathways');
    
        $batch = [];
        $batchSize = 2000;
    
        $skip = true;
        while (($line = fgets($handle, 65536)) !== false) 
        {
            if ($skip) { $skip = false; continue; }
            
            $item = explode(",", trim($line));
    
            if (count($item) < 6) continue;
    
            $batch[] = 
            [
                "id"               => $item[0],
                "mode"             => $item[1],
                "is_bidirectional" => $item[2],
                "from_stop_id"     => $item[3],
                "to_stop_id"       => $item[4],
                "traversal_time"   => $item[5],
            ];
    
            if (count($batch) >= $batchSize) 
            {
                DB::table("pathways")->insert($batch);
                $batch = [];
            }
        }
    
        if (!empty($batch)) 
        {
            DB::table("pathways")->insert($batch);
        }
    
        fclose($handle);
    }
}
