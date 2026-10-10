{{--
    LANGKAH BELAJAR BLADE:
    1. @extends('layouts.app')
       Memerintahkan Blade untuk menggunakan kerangka induk layouts/app.blade.php.
       Kita tidak perlu lagi mengetik <html>, <head>, <body>, dan sidebar berulang kali!
--}}
@extends('layouts.app')

{{-- 2. @section('title', 'Dashboard') -> Mengisi judul tab browser --}}
@section('title', 'Dashboard')

{{-- 3. @section('page_title', 'Dashboard') -> Mengisi teks header h1 di atas --}}
@section('page_title', 'Dashboard')

{{-- 4. @section('breadcrumb') -> Mengisi teks navigasi kecil di bawah judul --}}
@section('breadcrumb')
    <span>Selamat Datang, </span>
    {{-- Variabel $userLogin dikirim dari DashboardController@index via compact() --}}
    <b class="text-white">{{ $userLogin['username'] }}</b>
    <span class="text-blue-200">({{ $userLogin['role'] }})</span>
@endsection

{{--
    5. @section('content') ... @endsection
       Inilah KONTEN UTAMA halaman.
       Seluruh kode di dalam blok ini akan disuntikkan ke posisi @yield('content')
       yang ada di dalam file layouts/app.blade.php.
--}}
@section('content')

    {{-- ======================================================== --}}
    {{-- BAGIAN 1: KARTU INDIKATOR STATISTIK (4 KOTAK ANGKA)       --}}
    {{-- Menggunakan data dari array $dataDashboard                --}}
    {{-- ======================================================== --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8 font-heading">

        {{-- Kartu 1: Total Siswa --}}
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Total Siswa</p>
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-edu-orange text-2xl">
                    <i class="fa-solid fa-users"></i>
                </div>
                <span class="text-3xl font-black text-gray-900">
                    {{ $dataDashboard['total_siswa'] }}
                </span>
            </div>
        </div>

        {{-- Kartu 2: Kehadiran Hari ini --}}
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Kehadiran Hari ini</p>
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-edu-orange text-2xl">
                    <i class="fa-solid fa-calendar-check"></i>
                </div>
                <span class="text-3xl font-black text-gray-900">
                    {{ $dataDashboard['kehadiran_hari'] }}
                </span>
            </div>
        </div>

        {{-- Kartu 3: Rata-rata Nilai --}}
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Rata-rata Nilai Kelas</p>
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-edu-orange text-2xl">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <span class="text-3xl font-black text-gray-900">
                    {{ $dataDashboard['rata_nilai'] }}
                </span>
            </div>
        </div>

        {{-- Kartu 4: Perlu Perhatian (Early Warning Alert) --}}
        <div class="bg-white rounded-3xl p-6 shadow-xl border border-gray-100 flex flex-col justify-between">
            <p class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Perlu Perhatian</p>
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 rounded-2xl bg-orange-100 flex items-center justify-center text-edu-orange text-2xl">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                </div>
                <span class="text-3xl font-black text-gray-900">
                    {{ $dataDashboard['perlu_perhatian'] }}
                </span>
            </div>
        </div>

    </div>

    {{-- ======================================================== --}}
    {{-- BAGIAN 2: DAFTAR SISWA PERLU PERHATIAN                    --}}
    {{-- Menampilkan perulangan (@foreach) dari Controller         --}}
    {{-- ======================================================== --}}
    <div class="bg-white rounded-3xl p-6 md:p-8 shadow-xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between mb-6 pb-4 border-b border-gray-100">
            <div>
                <h2 class="text-xl font-extrabold text-gray-900">Siswa Perlu Perhatian</h2>
                <p class="text-xs text-gray-400 mt-1">Daftar siswa yang memerlukan pendampingan akademik atau kehadiran</p>
            </div>
            <span class="bg-orange-50 text-edu-orange text-xs font-bold px-3 py-1.5 rounded-full border border-orange-200">
                Early Warning System
            </span>
        </div>

        {{--
            PENGGUNAAN @foreach:
            $siswaPerhatian adalah array yang dikirim dari DashboardController.
            Blade akan mengulang baris div di bawah ini untuk setiap siswa.
        --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($siswaPerhatian as $s)
                <div class="flex items-center justify-between p-4 bg-gray-50 hover:bg-orange-50/50 rounded-2xl border border-gray-200 transition">
                    <div class="flex items-center space-x-3.5">
                        {{-- Avatar Inisial Nama --}}
                        <div class="w-11 h-11 rounded-2xl bg-edu-orange flex items-center justify-center text-white font-bold text-base shadow-sm">
                            {{ strtoupper(substr($s['nama'], 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-gray-900">{{ $s['nama'] }}</p>
                            <p class="text-xs font-medium text-gray-500">Kelas {{ $s['kelas'] }}</p>
                        </div>
                    </div>

                    <span class="text-[11px] font-semibold text-edu-orange bg-orange-100 px-2.5 py-1 rounded-lg">
                        Pantau
                    </span>
                </div>
            @endforeach
        </div>
    </div>

@endsection
