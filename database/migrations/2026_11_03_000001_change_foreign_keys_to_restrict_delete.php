<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Mengubah foreign key yang sebelumnya cascadeOnDelete menjadi restrictOnDelete
     * untuk mencegah penghapusan data secara tidak sengaja (data loss guards).
     */
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign('fk_siswa_kelas');
            $table->foreign('id_kelas', 'fk_siswa_kelas')
                ->references('id_kelas')->on('kelas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropForeign('fk_nilai_siswa');
            $table->dropForeign('fk_nilai_mapel');

            $table->foreign('id_siswa', 'fk_nilai_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_nilai_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->dropForeign('fk_absensi_siswa');
            $table->dropForeign('fk_absensi_mapel');

            $table->foreign('id_siswa', 'fk_absensi_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_absensi_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('nilai_ekstrakurikuler', function (Blueprint $table) {
            $table->dropForeign('fk_ne_siswa');
            $table->dropForeign('fk_ne_ekskul');

            $table->foreign('id_siswa', 'fk_ne_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_ekskul', 'fk_ne_ekskul')
                ->references('id_ekskul')->on('ekstrakurikuler')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('nilai_harian', function (Blueprint $table) {
            $table->dropForeign('fk_nh_siswa');
            $table->dropForeign('fk_nh_mapel');

            $table->foreign('id_siswa', 'fk_nh_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_nh_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('prestasi', function (Blueprint $table) {
            $table->dropForeign('fk_prestasi_siswa');

            $table->foreign('id_siswa', 'fk_prestasi_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('catatan_wali', function (Blueprint $table) {
            $table->dropForeign('fk_cw_siswa');

            $table->foreign('id_siswa', 'fk_cw_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('analisis_siswa', function (Blueprint $table) {
            $table->dropForeign('fk_as_siswa');

            $table->foreign('id_siswa', 'fk_as_siswa')
                ->references('id_siswa')->on('siswa')
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('analisis_siswa', function (Blueprint $table) {
            $table->dropForeign('fk_as_siswa');
            $table->foreign('id_siswa', 'fk_as_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('catatan_wali', function (Blueprint $table) {
            $table->dropForeign('fk_cw_siswa');
            $table->foreign('id_siswa', 'fk_cw_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('prestasi', function (Blueprint $table) {
            $table->dropForeign('fk_prestasi_siswa');
            $table->foreign('id_siswa', 'fk_prestasi_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('nilai_harian', function (Blueprint $table) {
            $table->dropForeign('fk_nh_siswa');
            $table->dropForeign('fk_nh_mapel');

            $table->foreign('id_siswa', 'fk_nh_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_nh_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('nilai_ekstrakurikuler', function (Blueprint $table) {
            $table->dropForeign('fk_ne_siswa');
            $table->dropForeign('fk_ne_ekskul');

            $table->foreign('id_siswa', 'fk_ne_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_ekskul', 'fk_ne_ekskul')
                ->references('id_ekskul')->on('ekstrakurikuler')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('absensi', function (Blueprint $table) {
            $table->dropForeign('fk_absensi_siswa');
            $table->dropForeign('fk_absensi_mapel');

            $table->foreign('id_siswa', 'fk_absensi_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_absensi_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('nilai', function (Blueprint $table) {
            $table->dropForeign('fk_nilai_siswa');
            $table->dropForeign('fk_nilai_mapel');

            $table->foreign('id_siswa', 'fk_nilai_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_nilai_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropForeign('fk_siswa_kelas');
            $table->foreign('id_kelas', 'fk_siswa_kelas')
                ->references('id_kelas')->on('kelas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }
};
