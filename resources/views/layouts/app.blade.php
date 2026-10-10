<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{--
        @yield('title') akan diisi oleh halaman anak yang extends layout ini.
        Jika halaman anak tidak mengisinya, defaultnya adalah 'Sistem Akademik'
    --}}
    <title>EduTrack - @yield('title', 'Sistem Akademik')</title>

    {{-- Google Fonts: Montserrat (Heading) & Lora (Body) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800&display=swap" rel="stylesheet">

    {{-- Font Awesome (untuk ikon) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{--
        Tailwind CSS via CDN
        Inilah yang membuat class seperti bg-white, text-blue-500, flex, rounded-xl, dll. bekerja
        langsung di browser tanpa perlu file .css manual.
    --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Konfigurasi warna custom tema EduTrack
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        edu: {
                            blue:        '#2F5CAB',
                            orange:      '#E04F26',
                            orangeHover: '#CA421B',
                        }
                    },
                    fontFamily: {
                        heading: ['Montserrat', 'sans-serif'],
                        body:    ['Lora', 'serif'],
                    }
                }
            }
        }
    </script>
</head>

{{--
    Ini adalah template induk (parent layout) yang diwarisi oleh halaman-halaman lain.
    Halaman anak menggunakan: @extends('layouts.app')
    Lalu mengisi bagian-bagian @yield() yang ada di bawah ini.
--}}
<body class="bg-edu-blue min-h-screen flex text-gray-900 font-heading antialiased">

    {{-- =============================== SIDEBAR =============================== --}}
    <aside class="w-64 bg-white min-h-screen flex flex-col justify-between shadow-xl fixed left-0 top-0 bottom-0 z-40">
        <div>
            {{-- Logo EduTrack --}}
            <div class="px-6 py-6 border-b border-gray-100 flex items-center justify-center">
                <span class="text-2xl font-extrabold text-edu-blue tracking-tight">EduTrack</span>
            </div>

            {{-- Navigasi Menu Sidebar --}}
            <nav class="mt-4 px-3 space-y-1">
                <a href="{{ route('dashboard') }}"
                   class="flex items-center px-4 py-3 rounded-xl text-sm font-semibold bg-edu-blue text-white shadow-md">
                    <i class="fa-solid fa-house w-6 text-center mr-2"></i>
                    <span>Dashboard</span>
                </a>
            </nav>
        </div>

        {{-- Profil Pengguna di Bagian Bawah Sidebar --}}
        <div class="p-4 border-t border-gray-100 bg-gray-50/70">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-edu-orange flex items-center justify-center text-white font-bold text-lg shadow-sm">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="flex-1 min-w-0">
                    {{-- Mengambil data session user yang login --}}
                    <p class="text-xs font-bold text-gray-900 truncate">
                        {{ session('user_login.username', 'Pengguna') }}
                    </p>
                    <p class="text-[11px] text-gray-500 font-medium truncate">
                        {{ session('user_login.role', 'Role') }}
                    </p>
                </div>
            </div>
        </div>
    </aside>

    {{-- =============================== KONTEN UTAMA =============================== --}}
    <main class="ml-64 flex-1 min-w-0 p-8 min-h-screen">

        {{-- Header Atas (Judul Halaman, Breadcrumb & Tombol Logout) --}}
        <header class="flex items-center justify-between mb-6">
            <div>
                {{-- @yield('page_title') diisi oleh halaman anak --}}
                <h1 class="text-3xl font-black text-white font-heading tracking-wide">
                    @yield('page_title', 'Dashboard')
                </h1>

                {{-- @yield('breadcrumb') diisi oleh halaman anak --}}
                <nav class="mt-0.5 text-sm font-medium text-blue-100">
                    @yield('breadcrumb', 'Dashboard')
                </nav>
            </div>

            <div class="flex items-center space-x-4">
                <span class="text-white text-sm font-semibold tracking-wider">Kamis, 1 Oktober 2026</span>

                {{-- Tombol Logout: Mengirim form POST ke rute logout --}}
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit"
                            title="Keluar / Logout"
                            class="w-10 h-10 rounded-xl bg-edu-orange hover:bg-edu-orangeHover text-white flex items-center justify-center transition shadow-md">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    </button>
                </form>
            </div>
        </header>

        {{-- Flash Message (Pesan sukses jika ada redirect) --}}
        @if(session('pesan'))
            <div class="mb-4 bg-emerald-500 text-white px-4 py-3 rounded-xl text-sm font-medium shadow-md">
                {{ session('pesan') }}
            </div>
        @endif

        {{--
            COLOKAN UTAMA: @yield('content')
            Semua kode yang ada di dalam @section('content') pada halaman anak
            akan otomatis ditempelkan tepat di sini!
        --}}
        @yield('content')

    </main>

</body>
</html>
