<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        self::create_database();
        self::seed_database();
    }
    public function create_database()
    {
        $raw_sql = file_get_contents("database/seeders/statements.sql");
        $sql_statements = explode(";",$raw_sql);

        foreach($sql_statements as $sql_statement)
        {
            if(!empty($sql_statement))
            {
                DB::statement(trim($sql_statement) . ";");
            }
        }
    }
    
    public function seed_database()
    {
        self::refresh_data();
        $this->call([
            InfoSeeder::class,
            RouteSeeder::class,
            ShapeSeeder::class,
            TripSeeder::class,
            StopSeeder::class,
            StopTimeSeeder::class,
            PathwaySeeder::class,
            CalendarDateSeeder::class
        ]);
        remove_dead_stops();
    }

    public static function refresh_data()
    {
        $files = glob(get_storage_path("*"));
        foreach($files as $file)
        {
            if(is_file($file)) 
            {
                unlink($file);
            }
        }

        $in = fopen("https://bkk.hu/gtfs/budapest_gtfs.zip", "rb");
        $out = fopen(get_storage_path("budapest_gtfs.zip"), "wb");
        stream_copy_to_stream($in, $out);
        fclose($in);
        fclose($out);

        sleep(3);

        $zip = new \ZipArchive;
        if ($zip->open(get_storage_path("budapest_gtfs.zip")) === TRUE) 
        {
            $zip->extractTo(get_storage_path(""));
            $zip->close();
        } else {
            echo 'failed';
        }
    }
}
