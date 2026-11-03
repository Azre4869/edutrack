<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Jalankan Master Data (Wajib)
        $this->call([
            MasterDataSeeder::class,
        ]);

        // 2. Jalankan Dummy Data (Simulasi untuk keperluan demo/testing)
        $this->call([
            DummyDataSeeder::class,
        ]);
    }
}
