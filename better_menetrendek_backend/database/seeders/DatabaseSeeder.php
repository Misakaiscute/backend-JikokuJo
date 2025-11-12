<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        private function create_database()
        {
            $raw_sql = fopen('statements.sql', "r");
            $sql_statements = explode(";", $raw_sql);
            foreach $sql_statements as $sql_statements
            {
                DB:statement();
            }
            
        }
        Eloquent::unguard();

        // $this->call('UserTableSeeder');
        // $this->command->info('User table seeded!');

        $path = 'app/developer_docs/countries.sql';
        DB::unprepared(file_get_contents($path));
        $this->command->info('Country table seeded!');
    }
}
