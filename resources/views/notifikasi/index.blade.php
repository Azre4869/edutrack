@extends('layouts.app')

@section('title', 'Notifikasi')
@section('page_title', 'Notifikasi')
@section('page_subtitle', 'Dashboard > Notifikasi')

@section('content')
<div class="space-y-4">

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Semua --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                <i class="fas fa-bell text-2xl text-edu-blue"></i>
            </div>
            <div>
                <p class="text-edu-blue text-sm font-semibold">Semua</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $total ?? 12 }}</p>
            </div>
        </div>
        {{-- Belum dibaca --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fas fa-envelope text-2xl text-red-500"></i>
            </div>
            <div>
                <p class="text-red-500 text-sm font-semibold">Belum dibaca</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $belumDibaca ?? 3 }}</p>
            </div>
        </div>
        {{-- Tugas --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <i class="fas fa-clipboard-list text-2xl text-green-500"></i>
            </div>
            <div>
                <p class="text-green-600 text-sm font-semibold">Tugas</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $tugas ?? 4 }}</p>
            </div>
        </div>
        {{-- Pengumuman --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                <i class="fas fa-bullhorn text-2xl text-yellow-500"></i>
            </div>
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Penumuman</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $pengumuman ?? 3 }}</p>
            </div>
        </div>
    </div>

    {{-- Notification List --}}
    <div class="bg-white rounded-xl p-6 space-y-6">

        {{-- Hari ini --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                <h3 class="font-bold text-gray-700">Hari ini</h3>
            </div>
            <div class="space-y-2">
                @forelse($hariIni ?? [] as $notif)
                <a href="#" class="flex items-center gap-4 p-4 rounded-lg bg-blue-50 border border-blue-100 hover:bg-blue-100 transition group">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                        {{ ($notif['tipe'] ?? '') === 'warning' ? 'bg-red-100' : 'bg-blue-100' }}">
                        <i class="fas {{ ($notif['tipe'] ?? '') === 'warning' ? 'fa-triangle-exclamation text-red-500' : 'fa-bullhorn text-blue-500' }}"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 text-sm">{{ $notif['judul'] ?? '' }}</p>
                        <p class="text-gray-500 text-xs">{{ $notif['deskripsi'] ?? '' }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($notif['unread'] ?? false)
                            <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                        @endif
                        <span class="text-gray-400 text-xs">{{ $notif['waktu'] ?? '' }}</span>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    </div>
                </a>
                @empty
                {{-- Demo: Hari ini --}}
                <a href="#" class="flex items-center gap-4 p-4 rounded-lg bg-blue-50 border border-blue-100 hover:bg-blue-100 transition">
                    <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-triangle-exclamation text-red-500"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 text-sm">Early Warning</p>
                        <p class="text-gray-500 text-xs">Peringatan ada 5 siswa terdeteksi early warning</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                        <span class="text-gray-400 text-xs">08:45</span>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    </div>
                </a>
                <a href="#" class="flex items-center gap-4 p-4 rounded-lg bg-blue-50 border border-blue-100 hover:bg-blue-100 transition">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                        <i class="fas fa-bullhorn text-blue-500"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-800 text-sm">Jadwal Ujian</p>
                        <p class="text-gray-500 text-xs">Ujian tengah semester untuk kelas VII &amp; VIII akan dilaksanakan pada 5-10 Oktober 2026.</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="w-2 h-2 rounded-full bg-red-500 inline-block"></span>
                        <span class="text-gray-400 text-xs">08:45</span>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    </div>
                </a>
                @endforelse
            </div>
        </div>

        {{-- Kemarin --}}
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                <h3 class="font-bold text-gray-700">Kemarin</h3>
            </div>
            <div class="space-y-2">
                @forelse($kemarin ?? [] as $notif)
                <a href="#" class="flex items-center gap-4 p-4 rounded-lg border border-gray-100 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                        {{ ($notif['tipe'] ?? '') === 'warning' ? 'bg-red-100' : 'bg-blue-100' }}">
                        <i class="fas {{ ($notif['tipe'] ?? '') === 'warning' ? 'fa-triangle-exclamation text-red-500' : 'fa-circle-check text-blue-500' }}"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-700 text-sm">{{ $notif['judul'] ?? '' }}</p>
                        <p class="text-gray-400 text-xs">{{ $notif['deskripsi'] ?? '' }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-gray-400 text-xs">{{ $notif['waktu'] ?? '' }}</span>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    </div>
                </a>
                @empty
                @foreach([
                    ['fa-triangle-exclamation','text-red-500','bg-red-100','Input Nilai Belum Selesai','Input nilai belum berhasil','08:45'],
                    ['fa-circle-check','text-blue-500','bg-blue-100','Input Nilai Tuntas','Input nilai sedang di proses','07:50'],
                    ['fa-circle-check','text-blue-500','bg-blue-100','Nilai','Nilai penilaian tengah semester sudah dapat di akses di menu rapor','07:45'],
                    ['fa-circle-check','text-blue-500','bg-blue-100','Nilai Bahasa Indonesia','Nilai Bahasa indonesia TP1 kelas VII A sudah di input.','07:36'],
                    ['fa-triangle-exclamation','text-red-500','bg-red-100','Early Warning','Peringatan ada 1 siswa terdeteksi early warning','07:00'],
                ] as $row)
                <a href="#" class="flex items-center gap-4 p-4 rounded-lg border border-gray-100 hover:bg-gray-50 transition">
                    <div class="w-10 h-10 rounded-full {{ $row[2] }} flex items-center justify-center shrink-0">
                        <i class="fas {{ $row[0] }} {{ $row[1] }}"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-semibold text-gray-700 text-sm">{{ $row[3] }}</p>
                        <p class="text-gray-400 text-xs">{{ $row[4] }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="text-gray-400 text-xs">{{ $row[5] }}</span>
                        <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
                    </div>
                </a>
                @endforeach
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
