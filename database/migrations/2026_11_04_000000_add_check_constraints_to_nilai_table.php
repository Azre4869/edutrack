<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Menambahkan CHECK constraint batas nilai 0–100 pada kolom nilai
     * agar batasan 0–100 dijaga langsung di level database engine.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `nilai`
                ADD CONSTRAINT `chk_nilai_tp1` CHECK (`tp1` IS NULL OR (`tp1` >= 0 AND `tp1` <= 100)),
                ADD CONSTRAINT `chk_nilai_tp2` CHECK (`tp2` IS NULL OR (`tp2` >= 0 AND `tp2` <= 100)),
                ADD CONSTRAINT `chk_nilai_tp3` CHECK (`tp3` IS NULL OR (`tp3` >= 0 AND `tp3` <= 100)),
                ADD CONSTRAINT `chk_nilai_tp4` CHECK (`tp4` IS NULL OR (`tp4` >= 0 AND `tp4` <= 100)),
                ADD CONSTRAINT `chk_nilai_tp5` CHECK (`tp5` IS NULL OR (`tp5` >= 0 AND `tp5` <= 100)),
                ADD CONSTRAINT `chk_nilai_tp6` CHECK (`tp6` IS NULL OR (`tp6` >= 0 AND `tp6` <= 100)),
                ADD CONSTRAINT `chk_nilai_tp7` CHECK (`tp7` IS NULL OR (`tp7` >= 0 AND `tp7` <= 100)),
                ADD CONSTRAINT `chk_nilai_tp8` CHECK (`tp8` IS NULL OR (`tp8` >= 0 AND `tp8` <= 100)),
                ADD CONSTRAINT `chk_nilai_ns` CHECK (`ns` IS NULL OR (`ns` >= 0 AND `ns` <= 100)),
                ADD CONSTRAINT `chk_nilai_sas` CHECK (`sas` IS NULL OR (`sas` >= 0 AND `sas` <= 100)),
                ADD CONSTRAINT `chk_nilai_akhir` CHECK (`nilai_akhir` IS NULL OR (`nilai_akhir` >= 0 AND `nilai_akhir` <= 100))
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE `nilai`
                DROP CHECK `chk_nilai_tp1`,
                DROP CHECK `chk_nilai_tp2`,
                DROP CHECK `chk_nilai_tp3`,
                DROP CHECK `chk_nilai_tp4`,
                DROP CHECK `chk_nilai_tp5`,
                DROP CHECK `chk_nilai_tp6`,
                DROP CHECK `chk_nilai_tp7`,
                DROP CHECK `chk_nilai_tp8`,
                DROP CHECK `chk_nilai_ns`,
                DROP CHECK `chk_nilai_sas`,
                DROP CHECK `chk_nilai_akhir`
            ");
        }
    }
};
