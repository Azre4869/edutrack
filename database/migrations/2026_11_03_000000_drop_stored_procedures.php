<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menghapus stored procedure MySQL yang sudah digantikan oleh query Eloquent.
 *
 * Alasan: stored procedure tidak portabel (tidak bisa dijalankan di SQLite) dan
 * menyulitkan CI. Rekap presensi kini dikerjakan langsung oleh RaporController
 * menggunakan Eloquent + selectRaw — lihat Push 2 (chore/ci-portable-db).
 */
return new class extends Migration
{
    /**
     * Drop semua stored procedure yang tidak lagi digunakan.
     * View tetap dipertahankan karena tidak menghalangi portabilitas tes.
     */
    public function up(): void
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS `rekap_presensi`');
        DB::unprepared('DROP PROCEDURE IF EXISTS `rata_nilai`');
        DB::unprepared('DROP PROCEDURE IF EXISTS `get_nilai_siswa`');
    }

    /**
     * Tidak meng-recreate prosedur — gunakan migration asal untuk rollback penuh.
     */
    public function down(): void
    {
        // Prosedur ini sengaja tidak dibuat ulang.
        // Untuk rollback ke state awal, jalankan:
        //   php artisan migrate:rollback --step=<jumlah_migration>
        // hingga migration 2026_10_31_000000_create_edutrack_tables kena.
    }
};
