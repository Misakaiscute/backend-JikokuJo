<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InfoSeeder extends Seeder
{
    public function run(): void
    {        
        $handle = fopen(get_storage_path("agency.txt"), 'r');

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "id" => ($item[0] === '' ? 0 : $item[0]),
                "name" => ($item[1] === '' ? 0 : $item[1]),
                "url" => ($item[2] === '' ? 0 : $item[2]),
                "time_zone" => ($item[3] === '' ? 0 : $item[3]),
                "lang" => ($item[4] === '' ? 0 : $item[4]),
                "phone" => ($item[5] === '' ? 0 : $item[5])

            ];
        }
        DB::table("agency")->upsert(
            $batch,
            ['id'],
            ['name', 'url', 'time_zone', 'lang', 'phone']
        );
        $batch = [];
        fclose($handle);



        $handle = fopen(get_storage_path("feed_info.txt"), 'r');

        $skip = true;
        while (($line = fgets($handle)) !== false) {
            if($skip) { $skip = false; continue; }
            $item = explode(",", trim($line));

            $batch[] = [
                "id" => $item[0],
                "publisher_name" => $item[1],
                "publisher_url" => $item[2],
                "lang" => $item[3],
                "start_date" => $item[4],
                "end_date" => $item[5],
                "version" => ($item[5] === '' ? 0 : $item[5])

            ];
        }
        DB::table("feed_info")->upsert(
            $batch,
            ['id'],
            ['publisher_name', 'publisher_url', 'lang', 'start_date', 'end_date', 'version']
        );

        fclose($handle);
    }
}
