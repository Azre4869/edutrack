<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop view vw_detail_absensi yang bergantung pada kolom a.id_mapel
        DB::statement("DROP VIEW IF EXISTS `vw_detail_absensi`");

        if (Schema::hasTable('absensi')) {
            // Bersihkan data duplikat jika ada sebelum menerapkan unique baru
            DB::statement("
                DELETE a1 FROM absensi a1
                INNER JOIN absensi a2 
                WHERE a1.id_absensi < a2.id_absensi 
                  AND a1.id_siswa = a2.id_siswa 
                  AND a1.id_semester = a2.id_semester 
                  AND a1.tanggal = a2.tanggal
            ");

            // Drop foreign key fk_absensi_mapel terlebih dahulu
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                  AND TABLE_NAME = 'absensi' 
                  AND CONSTRAINT_TYPE = 'FOREIGN KEY'
            ");
            $fkNames = array_column($foreignKeys, 'CONSTRAINT_NAME');

            if (in_array('fk_absensi_mapel', $fkNames)) {
                Schema::table('absensi', function (Blueprint $table) {
                    $table->dropForeign('fk_absensi_mapel');
                });
            }

            // Pastikan id_siswa punya index sendiri agar foreign key fk_absensi_siswa tidak menahan index composite
            $indexes = DB::select("SHOW INDEX FROM `absensi`");
            $indexNames = array_unique(array_column($indexes, 'Key_name'));

            if (!in_array('fk_absensi_siswa', $indexNames) && !in_array('idx_absensi_siswa', $indexNames)) {
                Schema::table('absensi', function (Blueprint $table) {
                    $table->index('id_siswa', 'idx_absensi_siswa');
                });
            }

            // Buat unique constraint baru terlebih dahulu
            if (!in_array('uq_absensi_siswa_semester_tanggal', $indexNames)) {
                Schema::table('absensi', function (Blueprint $table) {
                    $table->unique(['id_siswa', 'id_semester', 'tanggal'], 'uq_absensi_siswa_semester_tanggal');
                });
            }

            // Sekarang drop unique lama jika ada
            if (in_array('uq_absensi_siswa_mapel_tanggal', $indexNames)) {
                Schema::table('absensi', function (Blueprint $table) {
                    $table->dropUnique('uq_absensi_siswa_mapel_tanggal');
                });
            }

            // Drop kolom id_mapel jika masih ada
            if (Schema::hasColumn('absensi', 'id_mapel')) {
                Schema::table('absensi', function (Blueprint $table) {
                    $table->dropColumn('id_mapel');
                });
            }
        }

        // 3. Buat ulang view vw_detail_absensi tanpa kolom id_mapel
        DB::statement("
            CREATE VIEW `vw_detail_absensi` AS 
            SELECT a.tanggal, s.nis, s.nama AS nama_siswa, sm.nama_semester, a.status 
            FROM absensi a 
            JOIN siswa s ON a.id_siswa = s.id_siswa 
            JOIN semester sm ON a.id_semester = sm.id_semester
        ");
    }

    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS `vw_detail_absensi`");

        if (Schema::hasTable('absensi')) {
            $indexes = DB::select("SHOW INDEX FROM `absensi`");
            $indexNames = array_unique(array_column($indexes, 'Key_name'));

            if (in_array('uq_absensi_siswa_semester_tanggal', $indexNames)) {
                Schema::table('absensi', function (Blueprint $table) {
                    $table->dropUnique('uq_absensi_siswa_semester_tanggal');
                });
            }

            if (!Schema::hasColumn('absensi', 'id_mapel')) {
                Schema::table('absensi', function (Blueprint $table) {
                    $table->integer('id_mapel')->nullable()->after('id_siswa');
                });
            }

            $firstMapel = DB::table('mata_pelajaran')->first();
            if ($firstMapel) {
                DB::table('absensi')->whereNull('id_mapel')->update(['id_mapel' => $firstMapel->id_mapel]);
            }

            Schema::table('absensi', function (Blueprint $table) {
                $table->unique(['id_siswa', 'id_mapel', 'id_semester', 'tanggal'], 'uq_absensi_siswa_mapel_tanggal');

                $table->foreign('id_mapel', 'fk_absensi_mapel')
                    ->references('id_mapel')->on('mata_pelajaran')
                    ->restrictOnDelete()
                    ->cascadeOnUpdate();
            });
        }

        DB::statement("
            CREATE VIEW `vw_detail_absensi` AS 
            SELECT a.tanggal, s.nis, s.nama AS nama_siswa, mp.nama_mapel, sm.nama_semester, a.status 
            FROM absensi a 
            JOIN siswa s ON a.id_siswa = s.id_siswa 
            JOIN mata_pelajaran mp ON a.id_mapel = mp.id_mapel 
            JOIN semester sm ON a.id_semester = sm.id_semester
        ");
    }
};
