<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class CreateDatabase extends Command
{
    protected $signature = 'db:init';
    protected $description = 'Create database if it does not exist, reconnect, run migrations and seeders.';

    public function handle()
    {
        $database = config('database.connections.mysql.database');

        config(['database.connections.mysql.database' => null]);

        try {
            DB::statement("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $this->info("Database `$database` created or already exists.");
        } catch (\Exception $e) {
            $this->error("Failed to create database: " . $e->getMessage());
            return SymfonyCommand::FAILURE;
        }
        config(['database.connections.mysql.database' => $database]);

        DB::purge('mysql');
        DB::reconnect('mysql');

        $this->info("Database connection refreshed and now using `$database`.");

        $this->call('db:seed', ['--force' => true]);

        $this->call('migrate', ['--force' => true]);

        $this->info("Migrations and seeders completed successfully.");

        return SymfonyCommand::SUCCESS;
    }
}
