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

        // 2. Jalankan Dummy Data (Hanya untuk keperluan demo/testing di lingkungan non-produksi)
        if (!app()->environment('production')) {
            $this->call([
                DummyDataSeeder::class,
            ]);
        }
    }
}
