<?php

namespace App\Http\Controllers;

use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class NotifikasiController extends Controller
{
    public function index()
    {
        $userId = auth()->id();

        $query = Notifikasi::where('id_user', $userId)->orderBy('tanggal_kirim', 'desc');
        $allNotif = $query->get();

        $isDemoData = false;

        if ($allNotif->isEmpty()) {
            // Jika user belum memiliki notifikasi, ambil notifikasi demo jika ada, atau sediakan fallback
            $isDemoData = true;
        }

        $total = $allNotif->count();
        $belumDibaca = $allNotif->where('sudah_dibaca', 0)->count();

        $tugas = $allNotif->filter(function ($item) {
            $pesan = strtolower($item->pesan);
            return str_contains($pesan, 'tugas') || str_contains($pesan, 'nilai') || str_contains($pesan, 'input');
        })->count();

        $pengumuman = $allNotif->filter(function ($item) {
            $pesan = strtolower($item->pesan);
            return str_contains($pesan, 'ujian') || str_contains($pesan, 'jadwal') || str_contains($pesan, 'pengumuman');
        })->count();

        // Format data notifikasi
        $mapNotif = function ($item) {
            $pesan = $item->pesan;
            $pesanLower = strtolower($pesan);

            $tipe = 'info';
            $judul = 'Notifikasi';

            if (str_contains($pesanLower, 'warning') || str_contains($pesanLower, 'peringatan') || str_contains($pesanLower, 'early warning')) {
                $tipe = 'warning';
                $judul = 'Early Warning';
            } elseif (str_contains($pesanLower, 'ujian') || str_contains($pesanLower, 'jadwal') || str_contains($pesanLower, 'pengumuman')) {
                $tipe = 'announcement';
                $judul = 'Jadwal & Pengumuman';
            } elseif (str_contains($pesanLower, 'tuntas') || str_contains($pesanLower, 'selesai') || str_contains($pesanLower, 'berhasil')) {
                $tipe = 'success';
                $judul = 'Status Input Nilai';
            } elseif (str_contains($pesanLower, 'nilai')) {
                $tipe = 'info';
                $judul = 'Aktivitas Nilai';
            }

            $waktuKirim = $item->tanggal_kirim ? Carbon::parse($item->tanggal_kirim) : now();

            return [
                'id' => $item->id_notifikasi,
                'judul' => $judul,
                'deskripsi' => $pesan,
                'waktu' => $waktuKirim->format('H.i'),
                'unread' => !$item->sudah_dibaca,
                'tipe' => $tipe,
                'tanggal_kirim' => $waktuKirim,
            ];
        };

        $today = Carbon::today();

        $hariIni = $allNotif->filter(function ($item) use ($today) {
            return $item->tanggal_kirim && Carbon::parse($item->tanggal_kirim)->isSameDay($today);
        })->map($mapNotif)->values()->toArray();

        $kemarin = $allNotif->filter(function ($item) use ($today) {
            return !$item->tanggal_kirim || !Carbon::parse($item->tanggal_kirim)->isSameDay($today);
        })->map($mapNotif)->values()->toArray();

        return view('notifikasi.index', compact(
            'total',
            'belumDibaca',
            'tugas',
            'pengumuman',
            'hariIni',
            'kemarin',
            'isDemoData'
        ));
    }

    public function markAsRead($id)
    {
        $userId = auth()->id();
        $notif = Notifikasi::where('id_user', $userId)->where('id_notifikasi', $id)->firstOrFail();

        $notif->sudah_dibaca = 1;
        $notif->save();

        return back()->with('success', 'Notifikasi ditandai telah dibaca.');
    }
}
