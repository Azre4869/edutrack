<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Membuat seluruh tabel sistem EduTrack sesuai skema basis data MySQL sisfortes.
     */
    public function up(): void
    {
        // 1. GURU
        Schema::create('guru', function (Blueprint $table) {
            $table->integer('id_guru', true); // Auto increment int(11) primary key
            $table->string('nip', 30);
            $table->string('nama_guru', 100);
            $table->enum('jenis_guru', ['Guru Tetap', 'Guru Honorer'])->default('Guru Tetap')->nullable();
            $table->string('jabatan', 100)->default('Guru Mata Pelajaran')->nullable();
            $table->enum('status', ['Aktif', 'Tidak Aktif'])->default('Aktif')->nullable();
        });

        // 2. KELAS
        Schema::create('kelas', function (Blueprint $table) {
            $table->integer('id_kelas', true);
            $table->string('nama_kelas', 50);
            $table->integer('tingkat');
            $table->string('tahun_ajaran', 20);
            $table->integer('id_wali_kelas')->nullable();

            $table->foreign('id_wali_kelas', 'fk_kelas_wali')
                ->references('id_guru')->on('guru')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        // 3. SEMESTER
        Schema::create('semester', function (Blueprint $table) {
            $table->integer('id_semester', true);
            $table->string('nama_semester', 20);
            $table->string('tahun_ajaran', 20);
        });

        // 4. MATA PELAJARAN
        Schema::create('mata_pelajaran', function (Blueprint $table) {
            $table->integer('id_mapel', true);
            $table->string('kode_mapel', 20)->nullable();
            $table->string('nama_mapel', 100);
            $table->enum('kategori', ['Muatan Nasional', 'Muatan Lokal', 'Pilihan/Mulok'])->default('Muatan Nasional')->nullable();
            $table->string('tingkat', 50)->default('VII, VIII, IX')->nullable();
            $table->integer('id_guru');

            $table->foreign('id_guru', 'fk_mapel_guru')
                ->references('id_guru')->on('guru')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 5. EKSTRAKURIKULER
        Schema::create('ekstrakurikuler', function (Blueprint $table) {
            $table->integer('id_ekskul', true);
            $table->string('nama_ekskul', 100);
            $table->integer('id_guru')->nullable();
            $table->string('jadwal', 100)->nullable();
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif')->nullable();
        });

        // 6. USERS
        Schema::create('users', function (Blueprint $table) {
            $table->integer('id_user', true);
            $table->string('username', 50)->unique();
            $table->string('password', 255);
            $table->enum('role', ['Operator', 'Guru Mapel', 'Wali Kelas']);
            $table->integer('id_guru')->nullable();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('id_guru', 'fk_user_guru')
                ->references('id_guru')->on('guru')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        // 7. SISWA
        Schema::create('siswa', function (Blueprint $table) {
            $table->integer('id_siswa', true);
            $table->string('nis', 30);
            $table->string('nama', 100);
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->string('tempat_lahir', 50)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('agama', 20)->default('Islam')->nullable();
            $table->enum('status_anak', ['Anak Kandung', 'Anak Angkat', 'Anak Tiri'])->default('Anak Kandung')->nullable();
            $table->integer('anak_ke')->default(1)->nullable();
            $table->text('alamat')->nullable();
            $table->string('no_telepon', 20)->nullable();
            $table->string('sekolah_asal', 100)->nullable();
            $table->enum('status_siswa', ['Aktif', 'Nonaktif'])->default('Aktif')->nullable();
            $table->integer('id_kelas');

            $table->foreign('id_kelas', 'fk_siswa_kelas')
                ->references('id_kelas')->on('kelas')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 8. KRITERIA ANALISIS
        Schema::create('kriteria_analisis', function (Blueprint $table) {
            $table->integer('id_kriteria', true);
            $table->string('nama_kriteria', 100);
            $table->enum('jenis', ['Nilai', 'Kehadiran']);
            $table->string('parameter', 255)->nullable();
            $table->decimal('nilai_ambang', 5, 2)->nullable();
            $table->boolean('aktif')->default(true);
        });

        // 9. ANALISIS LOG
        Schema::create('analisis_log', function (Blueprint $table) {
            $table->integer('id_log', true);
            $table->dateTime('waktu_jalan')->useCurrent();
            $table->enum('pemicu', ['Manual', 'Terjadwal'])->default('Manual');
            $table->integer('jumlah_siswa_diproses')->default(0)->nullable();
        });

        // 10. ANALISIS SISWA
        Schema::create('analisis_siswa', function (Blueprint $table) {
            $table->integer('id_analisis', true);
            $table->integer('id_siswa');
            $table->integer('id_semester');
            $table->integer('id_log')->nullable();
            $table->date('tanggal_analisis');
            $table->enum('status', ['Normal', 'Perlu Perhatian'])->default('Normal');
            $table->string('ringkasan', 500)->nullable();

            $table->foreign('id_siswa', 'fk_as_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_as_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_log', 'fk_as_log')
                ->references('id_log')->on('analisis_log')
                ->nullOnDelete()
                ->cascadeOnUpdate();
        });

        // 11. ANALISIS KRITERIA (Pivot)
        Schema::create('analisis_kriteria', function (Blueprint $table) {
            $table->integer('id_analisis');
            $table->integer('id_kriteria');

            $table->primary(['id_analisis', 'id_kriteria']);

            $table->foreign('id_analisis', 'fk_ak_analisis')
                ->references('id_analisis')->on('analisis_siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_kriteria', 'fk_ak_kriteria')
                ->references('id_kriteria')->on('kriteria_analisis')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 12. TINDAK LANJUT
        Schema::create('tindak_lanjut', function (Blueprint $table) {
            $table->integer('id_tindak_lanjut', true);
            $table->integer('id_analisis');
            $table->integer('id_user');
            $table->date('tanggal');
            $table->time('waktu')->nullable();
            $table->string('jenis_konseling', 100)->nullable();
            $table->string('peserta_diundang', 100)->nullable();
            $table->string('tempat_media', 100)->nullable();
            $table->string('tindakan', 500);
            $table->text('catatan_konseling')->nullable();
            $table->text('rencana_dukungan')->nullable();
            $table->enum('status', ['Belum Ditindak', 'Sedang Diproses', 'Selesai'])->default('Belum Ditindak');
            $table->date('tanggal_evaluasi')->nullable();

            $table->foreign('id_analisis', 'fk_tl_analisis')
                ->references('id_analisis')->on('analisis_siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_user', 'fk_tl_user')
                ->references('id_user')->on('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 13. PRESTASI
        Schema::create('prestasi', function (Blueprint $table) {
            $table->integer('id_prestasi', true);
            $table->integer('id_siswa');
            $table->integer('id_semester');
            $table->string('nama_prestasi', 100);
            $table->enum('jenis', ['Akademik', 'Non-Akademik']);
            $table->string('keterangan', 255)->nullable();

            $table->foreign('id_siswa', 'fk_prestasi_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_prestasi_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 14. NILAI
        Schema::create('nilai', function (Blueprint $table) {
            $table->integer('id_nilai', true);
            $table->integer('id_siswa');
            $table->integer('id_mapel');
            $table->integer('id_semester');
            $table->decimal('tp1', 5, 2)->nullable();
            $table->decimal('tp2', 5, 2)->nullable();
            $table->decimal('tp3', 5, 2)->nullable();
            $table->decimal('tp4', 5, 2)->nullable();
            $table->decimal('tp5', 5, 2)->nullable();
            $table->decimal('tp6', 5, 2)->nullable();
            $table->decimal('tp7', 5, 2)->nullable();
            $table->decimal('tp8', 5, 2)->nullable();
            $table->decimal('ns', 5, 2)->nullable();
            $table->decimal('sas', 5, 2)->nullable();
            $table->decimal('nilai_akhir', 5, 2);

            $table->foreign('id_siswa', 'fk_nilai_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_nilai_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_nilai_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 15. CAPAIAN KOMPETENSI
        Schema::create('capaian_kompetensi', function (Blueprint $table) {
            $table->integer('id_capaian', true);
            $table->integer('id_nilai');
            $table->text('deskripsi_1');
            $table->text('deskripsi_2');

            $table->foreign('id_nilai', 'fk_capaian_nilai')
                ->references('id_nilai')->on('nilai')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 16. NILAI EKSTRAKURIKULER
        Schema::create('nilai_ekstrakurikuler', function (Blueprint $table) {
            $table->integer('id_nilai_ekskul', true);
            $table->integer('id_siswa');
            $table->integer('id_ekskul');
            $table->integer('id_semester');
            $table->enum('predikat', ['Sangat Baik', 'Baik', 'Cukup', 'Kurang']);
            $table->string('deskripsi', 255)->nullable();

            $table->foreign('id_siswa', 'fk_ne_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_ekskul', 'fk_ne_ekskul')
                ->references('id_ekskul')->on('ekstrakurikuler')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_ne_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 17. NILAI HARIAN
        Schema::create('nilai_harian', function (Blueprint $table) {
            $table->integer('id_nilai_harian', true);
            $table->integer('id_siswa');
            $table->integer('id_mapel');
            $table->integer('id_semester');
            $table->date('tanggal');
            $table->decimal('nilai', 5, 2);
            $table->string('keterangan', 255)->nullable();

            $table->foreign('id_siswa', 'fk_nh_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_nh_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_nh_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 18. NOTIFIKASI
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->integer('id_notifikasi', true);
            $table->integer('id_analisis');
            $table->integer('id_user');
            $table->string('pesan', 500);
            $table->boolean('sudah_dibaca')->default(false);
            $table->dateTime('tanggal_kirim')->useCurrent();

            $table->foreign('id_analisis', 'fk_notif_analisis')
                ->references('id_analisis')->on('analisis_siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_user', 'fk_notif_user')
                ->references('id_user')->on('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });

        // 19. ABSENSI
        Schema::create('absensi', function (Blueprint $table) {
            $table->integer('id_absensi', true);
            $table->integer('id_siswa');
            $table->integer('id_mapel');
            $table->integer('id_semester');
            $table->date('tanggal');
            $table->enum('status', ['Hadir', 'Izin', 'Sakit', 'Alpha']);

            $table->foreign('id_siswa', 'fk_absensi_siswa')
                ->references('id_siswa')->on('siswa')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_mapel', 'fk_absensi_mapel')
                ->references('id_mapel')->on('mata_pelajaran')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('id_semester', 'fk_absensi_semester')
                ->references('id_semester')->on('semester')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensi');
        Schema::dropIfExists('notifikasi');
        Schema::dropIfExists('nilai_harian');
        Schema::dropIfExists('nilai_ekstrakurikuler');
        Schema::dropIfExists('capaian_kompetensi');
        Schema::dropIfExists('nilai');
        Schema::dropIfExists('prestasi');
        Schema::dropIfExists('tindak_lanjut');
        Schema::dropIfExists('analisis_kriteria');
        Schema::dropIfExists('analisis_siswa');
        Schema::dropIfExists('analisis_log');
        Schema::dropIfExists('kriteria_analisis');
        Schema::dropIfExists('siswa');
        Schema::dropIfExists('users');
        Schema::dropIfExists('ekstrakurikuler');
        Schema::dropIfExists('mata_pelajaran');
        Schema::dropIfExists('semester');
        Schema::dropIfExists('kelas');
        Schema::dropIfExists('guru');
    }
};
