<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Upgrade Tabel kriteria_analisis
        if (Schema::hasTable('kriteria_analisis')) {
            Schema::table('kriteria_analisis', function (Blueprint $table) {
                if (!Schema::hasColumn('kriteria_analisis', 'kode')) {
                    $table->string('kode', 50)->nullable()->unique()->after('id_kriteria');
                }
                if (!Schema::hasColumn('kriteria_analisis', 'bobot')) {
                    $table->decimal('bobot', 4, 2)->default(1.00)->after('nilai_ambang');
                }
                if (!Schema::hasColumn('kriteria_analisis', 'operator_perbandingan')) {
                    $table->string('operator_perbandingan', 10)->default('>=')->after('bobot');
                }
                if (!Schema::hasColumn('kriteria_analisis', 'satuan')) {
                    $table->string('satuan', 20)->nullable()->after('operator_perbandingan');
                }
            });
        }

        // 2. Upgrade Tabel analisis_log
        if (Schema::hasTable('analisis_log')) {
            Schema::table('analisis_log', function (Blueprint $table) {
                if (!Schema::hasColumn('analisis_log', 'waktu_selesai')) {
                    $table->dateTime('waktu_selesai')->nullable()->after('waktu_jalan');
                }
                if (!Schema::hasColumn('analisis_log', 'total_berisiko')) {
                    $table->integer('total_berisiko')->default(0)->after('jumlah_siswa_diproses');
                }
                if (!Schema::hasColumn('analisis_log', 'status')) {
                    $table->enum('status', ['Running', 'Success', 'Failed'])->default('Success')->after('total_berisiko');
                }
                if (!Schema::hasColumn('analisis_log', 'pesan_error')) {
                    $table->text('pesan_error')->nullable()->after('status');
                }
                if (!Schema::hasColumn('analisis_log', 'id_user')) {
                    $table->integer('id_user')->nullable()->after('pesan_error');
                    $table->foreign('id_user', 'fk_alog_user')
                        ->references('id_user')->on('users')
                        ->nullOnDelete();
                }
            });
        }

        // 3. Upgrade Tabel analisis_siswa
        if (Schema::hasTable('analisis_siswa')) {
            Schema::table('analisis_siswa', function (Blueprint $table) {
                if (!Schema::hasColumn('analisis_siswa', 'tingkat_risiko')) {
                    $table->enum('tingkat_risiko', ['Tinggi', 'Sedang', 'Kecil', 'Normal'])->default('Normal')->after('status');
                }
                if (!Schema::hasColumn('analisis_siswa', 'skor_risiko')) {
                    $table->decimal('skor_risiko', 5, 2)->default(0.00)->after('tingkat_risiko');
                }
                if (!Schema::hasColumn('analisis_siswa', 'data_snapshot')) {
                    $table->json('data_snapshot')->nullable()->after('ringkasan');
                }
            });
        }

        // 4. Upgrade Tabel tindak_lanjut
        if (Schema::hasTable('tindak_lanjut')) {
            Schema::table('tindak_lanjut', function (Blueprint $table) {
                if (!Schema::hasColumn('tindak_lanjut', 'hasil_evaluasi')) {
                    $table->text('hasil_evaluasi')->nullable()->after('tanggal_evaluasi');
                }
                if (!Schema::hasColumn('tindak_lanjut', 'skor_efektivitas')) {
                    $table->enum('skor_efektivitas', ['Membaik', 'Tetap', 'Memburuk', 'Belum Dievaluasi'])->default('Belum Dievaluasi')->after('hasil_evaluasi');
                }
            });
        }

        // 5. Upgrade Tabel notifikasi
        if (Schema::hasTable('notifikasi')) {
            Schema::table('notifikasi', function (Blueprint $table) {
                if (!Schema::hasColumn('notifikasi', 'tipe')) {
                    $table->string('tipe', 50)->default('EWS_ALERT')->after('id_user');
                }
                if (!Schema::hasColumn('notifikasi', 'metadata')) {
                    $table->json('metadata')->nullable()->after('pesan');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('notifikasi')) {
            Schema::table('notifikasi', function (Blueprint $table) {
                $table->dropColumn(['tipe', 'metadata']);
            });
        }

        if (Schema::hasTable('tindak_lanjut')) {
            Schema::table('tindak_lanjut', function (Blueprint $table) {
                $table->dropColumn(['hasil_evaluasi', 'skor_efektivitas']);
            });
        }

        if (Schema::hasTable('analisis_siswa')) {
            Schema::table('analisis_siswa', function (Blueprint $table) {
                $table->dropColumn(['tingkat_risiko', 'skor_risiko', 'data_snapshot']);
            });
        }

        if (Schema::hasTable('analisis_log')) {
            Schema::table('analisis_log', function (Blueprint $table) {
                $table->dropForeign('fk_alog_user');
                $table->dropColumn(['waktu_selesai', 'total_berisiko', 'status', 'pesan_error', 'id_user']);
            });
        }

        if (Schema::hasTable('kriteria_analisis')) {
            Schema::table('kriteria_analisis', function (Blueprint $table) {
                $table->dropColumn(['kode', 'bobot', 'operator_perbandingan', 'satuan']);
            });
        }
    }
};
