<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    public function index()
    {
        $stats = [
            'semua' => 12,
            'belum_dibaca' => 3,
            'tugas' => 4,
            'pengumuman' => 3,
        ];

        $hariIni = [
            [
                'id' => 1,
                'judul' => 'Early Warning',
                'deskripsi' => 'Peringatan ada 5 siswa terdeteksi early warning',
                'waktu' => '08.45',
                'unread' => true,
                'tipe' => 'warning',
            ],
            [
                'id' => 2,
                'judul' => 'Jadwal Ujian',
                'deskripsi' => 'Ujian tengah semester untuk kelas VII & VIII akan dilaksanakan pada 5-10 Oktober 2026.',
                'waktu' => '08.45',
                'unread' => true,
                'tipe' => 'announcement',
            ],
        ];

        $kemarin = [
            [
                'id' => 3,
                'judul' => 'Input Nilai Belum Selesai',
                'deskripsi' => 'Input nilai belum berhasil',
                'waktu' => '08.45',
                'unread' => false,
                'tipe' => 'warning',
            ],
            [
                'id' => 4,
                'judul' => 'Input Nilai Tuntas',
                'deskripsi' => 'Input nilai sedang di proses',
                'waktu' => '07.50',
                'unread' => false,
                'tipe' => 'success',
            ],
            [
                'id' => 5,
                'judul' => 'Nilai',
                'deskripsi' => 'Nilai penilaian tengah semester sudah dapat di akses di menu rapor',
                'waktu' => '07.45',
                'unread' => false,
                'tipe' => 'success',
            ],
            [
                'id' => 6,
                'judul' => 'Nilai Bahasa Indonesia',
                'deskripsi' => 'Nilai Bahasa Indonesia TP1 kela VII A sudah di input.',
                'waktu' => '07.36',
                'unread' => false,
                'tipe' => 'success',
            ],
            [
                'id' => 7,
                'judul' => 'Early Warning',
                'deskripsi' => 'Peringtan ada 1 siswa terdeteksi early warning',
                'waktu' => '07.00',
                'unread' => false,
                'tipe' => 'warning',
            ],
        ];

        $total = $stats['semua'];
        $belumDibaca = $stats['belum_dibaca'];
        $tugas = $stats['tugas'];
        $pengumuman = $stats['pengumuman'];

        return view('notifikasi.index', compact('total', 'belumDibaca', 'tugas', 'pengumuman', 'hariIni', 'kemarin'));
    }
}
