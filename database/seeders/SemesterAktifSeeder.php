<?php

namespace Database\Seeders;

use App\Models\Semester;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SemesterAktifSeeder extends Seeder
{
    /**
     * Tandai satu semester sebagai aktif.
     */
    public function run(): void
    {
        // Nonaktifkan semua semester terlebih dahulu agar hanya satu yang aktif
        Semester::query()->update(['is_active' => false]);

        // Prioritaskan semester Genap 2026/2027 (id_semester = 2), atau semester terbaru
        $target = Semester::find(2) ?? Semester::orderBy('id_semester', 'desc')->first();

        if ($target) {
            $target->update(['is_active' => true]);
        }
    }
}
