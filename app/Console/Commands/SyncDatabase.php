<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-db';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data from SQLite to MySQL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting data synchronization from SQLite to MySQL...');

        // Find the correct SQLite filename (production might use mwalimu.db)
        $sqlitePath = file_exists(database_path('database.sqlite')) 
            ? database_path('database.sqlite') 
            : database_path('mwalimu.db');

        $this->info("Using SQLite source: {$sqlitePath}");

        // Hardcode the SQLite path to avoid conflict with the MySQL DB_DATABASE in .env
        config(['database.connections.sqlite.database' => $sqlitePath]);

        // Disable foreign key constraints on MySQL to allow truncation/insertion
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=0;');

        // 1. Get all tables from SQLite
        $tables = DB::connection('sqlite')->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' AND name NOT LIKE 'migrations'");

        foreach ($tables as $table) {
            $tableName = $table->name;
            $this->comment("Syncing table: {$tableName}");

            // 2. Fetch all rows from SQLite
            $rows = DB::connection('sqlite')->table($tableName)->get();

            if ($rows->isEmpty()) {
                $this->info("Table {$tableName} is empty. Skipping.");
                continue;
            }

            // 3. Insert into MySQL (assuming the default connection is now MySQL)
            // We'll use chunking to prevent memory issues for large tables
            DB::connection('mysql')->table($tableName)->truncate();

            foreach ($rows->chunk(100) as $chunk) {
                // Convert each row to an array
                $data = $chunk->map(fn($row) => (array) $row)->toArray();
                DB::connection('mysql')->table($tableName)->insert($data);
            }

            $this->info("Successfully synced " . $rows->count() . " rows for {$tableName}.");
        }

        // Re-enable foreign key constraints
        DB::connection('mysql')->statement('SET FOREIGN_KEY_CHECKS=1;');

        $this->info('Database synchronization complete!');
    }
}
