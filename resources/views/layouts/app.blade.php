<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack - @yield('title', 'Sistem Akademik SMPN 1 Sampang')</title>
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Google Fonts: Montserrat (Heading) & Lora (Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&family=Montserrat:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400&display=swap" rel="stylesheet">

    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS CDN with Custom Theme Config -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        edu: {
                            blue: '#2F5CAB',
                            darkblue: '#24488A',
                            lightblue: '#3B6EC9',
                            bg: '#335EAB',
                            orange: '#E04F26',
                            orangeHover: '#CA421B',
                            lightorange: '#FFF2EE',
                            gray1: '#333333',
                            gray2: '#4F4F4F',
                            gray3: '#828282',
                            gray4: '#BDBDBD',
                            gray5: '#E0E0E0',
                            info: '#2F80ED',
                            success: '#27AE60',
                            warning: '#E2B93B',
                            error: '#EB5757',
                        }
                    },
                    fontFamily: {
                        heading: ['Montserrat', 'sans-serif'],
                        body: ['Lora', 'serif'],
                        sans: ['Montserrat', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- SheetJS CDN (XLSX) for native Excel export -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <style>
        body {
            font-family: 'Lora', serif;
            background-color: #2F5CAB;
            color: #1D1D1D;
        }
        h1, h2, h3, h4, h5, h6, .font-heading, button, input, select, textarea, .nav-item {
            font-family: 'Montserrat', sans-serif;
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #E0E0E0;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: #BDBDBD;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #828282;
        }
        .excel-table th, .excel-table td {
            border: 1px solid #1D1D1D;
        }
    </style>
</head>
<body class="bg-edu-blue min-h-screen flex text-gray-900 antialiased overflow-x-hidden">

    @php
        $currentUser = auth()->user() ?? (object)[
            'role' => 'Wali Kelas',
            'displayName' => 'Endang Sulistian, S.Pd.',
            'username' => 'walikelas'
        ];
        $role = $currentUser->role;
        $currentRoute = Route::currentRouteName();
    @endphp

    <!-- SIDEBAR -->
    <aside class="w-64 bg-white min-h-screen flex flex-col justify-between shadow-xl fixed left-0 top-0 bottom-0 z-40">
        <div>
            <!-- LOGO HEADER -->
            <div class="px-6 py-6 border-b border-gray-100 flex items-center justify-center">
                <a href="{{ url('/') }}" class="flex items-center space-x-3">
                    <img src="{{ asset('images/logo-edutrack.png') }}" alt="EduTrack Logo" class="h-14 object-contain">
                </a>
            </div>

            <!-- NAVIGATION ITEMS -->
            <nav class="mt-4 px-3 space-y-1">

                @if($role === 'Operator')
                    <!-- OPERATOR MENU -->
                    <a href="{{ route('operator.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('operator.dashboard') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-house w-6 text-center mr-2 {{ request()->routeIs('operator.dashboard') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Submenu Data Master -->
                    <div x-data="{ open: true }" class="space-y-1">
                        <button onclick="document.getElementById('submenu-data-op').classList.toggle('hidden')" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            <span class="flex items-center">
                                <i class="fa-solid fa-folder w-6 text-center mr-2 text-edu-orange"></i>
                                <span>Data</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div id="submenu-data-op" class="pl-6 space-y-1 {{ request()->is('operator/siswa*') || request()->is('operator/guru*') || request()->is('operator/kelas*') || request()->is('operator/mapel*') || request()->is('operator/ekskul*') ? '' : 'hidden' }}">
                            <a href="{{ route('operator.siswa') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('operator.siswa*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Siswa</a>
                            <a href="{{ route('operator.guru') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('operator.guru*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Guru</a>
                            <a href="{{ route('operator.kelas') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('operator.kelas*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Kelas</a>
                            <a href="{{ route('operator.mapel') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('operator.mapel*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Mata Pelajaran</a>
                            <a href="{{ route('operator.ekskul') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('operator.ekskul*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Ekstrakulikuler</a>
                        </div>
                    </div>

                    <!-- Nilai Siswa (Operator) -->
                    <a href="{{ route('operator.nilai') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('operator.nilai*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-book-open w-6 text-center mr-2 {{ request()->routeIs('operator.nilai*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Nilai Siswa</span>
                    </a>

                    <!-- Presensi Siswa (Operator) -->
                    <a href="{{ route('operator.presensi-kelas') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('operator.presensi-kelas*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-clipboard-user w-6 text-center mr-2 {{ request()->routeIs('operator.presensi-kelas*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Presensi Siswa</span>
                    </a>

                    <!-- Ekstrakurikuler (Operator) -->
                    <a href="{{ route('operator.presensi-ekskul') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('operator.presensi-ekskul*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-medal w-6 text-center mr-2 {{ request()->routeIs('operator.presensi-ekskul*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Ekstrakurikuler</span>
                    </a>

                    <a href="{{ route('operator.rapor') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('operator.rapor*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-book-bookmark w-6 text-center mr-2 {{ request()->routeIs('operator.rapor*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Rapor</span>
                    </a>

                    <!-- Submenu Early Warning (Operator) -->
                    <div>
                        <button onclick="document.getElementById('submenu-ew-op').classList.toggle('hidden')" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            <span class="flex items-center">
                                <i class="fa-solid fa-triangle-exclamation w-6 text-center mr-2 text-edu-orange"></i>
                                <span>Early Warning</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div id="submenu-ew-op" class="pl-6 space-y-1 {{ request()->is('operator/early-warning*') || request()->is('operator/tindak-lanjut*') ? '' : 'hidden' }}">
                            <a href="{{ route('operator.early-warning') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('operator.early-warning*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Early Warning Home</a>
                            <a href="{{ route('operator.tindak-lanjut') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('operator.tindak-lanjut*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Tindak Lanjut</a>
                        </div>
                    </div>

                    <a href="{{ route('operator.laporan') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('operator.laporan*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-chart-column w-6 text-center mr-2 {{ request()->routeIs('operator.laporan*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Laporan</span>
                    </a>

                    <a href="{{ route('operator.notifikasi') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('operator.notifikasi*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-bell w-6 text-center mr-2 {{ request()->routeIs('operator.notifikasi*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Notifikasi</span>
                    </a>

                @elseif($role === 'Wali Kelas')
                    <!-- WALI KELAS MENU -->
                    <a href="{{ route('wali.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('wali.dashboard') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-house w-6 text-center mr-2 {{ request()->routeIs('wali.dashboard') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Submenu Data -->
                    <div>
                        <button onclick="document.getElementById('submenu-data-wali').classList.toggle('hidden')" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            <span class="flex items-center">
                                <i class="fa-solid fa-folder w-6 text-center mr-2 text-edu-orange"></i>
                                <span>Data</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div id="submenu-data-wali" class="pl-6 space-y-1 {{ request()->routeIs('wali.siswa*') ? '' : 'hidden' }}">
                            <a href="{{ route('wali.siswa') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('wali.siswa*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Siswa</a>
                        </div>
                    </div>

                    <!-- Prestasi -->
                    <a href="{{ route('wali.prestasi') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('wali.prestasi*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-trophy w-6 text-center mr-2 {{ request()->routeIs('wali.prestasi*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Input Prestasi</span>
                    </a>

                    <!-- Presensi Siswa (Wali Kelas) -->
                    <a href="{{ route('wali.presensi-kelas') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('wali.presensi-kelas*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-clipboard-user w-6 text-center mr-2 {{ request()->routeIs('wali.presensi-kelas*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Presensi Siswa</span>
                    </a>

                    <!-- Ekstrakurikuler (Wali Kelas) -->
                    <a href="{{ route('wali.presensi-ekskul') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('wali.presensi-ekskul*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-medal w-6 text-center mr-2 {{ request()->routeIs('wali.presensi-ekskul*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Ekstrakurikuler</span>
                    </a>

                    <!-- Rapor -->
                    <a href="{{ route('wali.rapor') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('wali.rapor*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-book-bookmark w-6 text-center mr-2 {{ request()->routeIs('wali.rapor*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Rapor</span>
                    </a>

                    <!-- Submenu Early Warning -->
                    <div>
                        <button onclick="document.getElementById('submenu-ew-wali').classList.toggle('hidden')" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            <span class="flex items-center">
                                <i class="fa-solid fa-triangle-exclamation w-6 text-center mr-2 text-edu-orange"></i>
                                <span>Early Warning</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div id="submenu-ew-wali" class="pl-6 space-y-1 {{ request()->is('wali/early-warning*') || request()->is('wali/tindak-lanjut*') ? '' : 'hidden' }}">
                            <a href="{{ route('wali.early-warning') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('wali.early-warning*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Early Warning Home</a>
                            <a href="{{ route('wali.tindak-lanjut') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('wali.tindak-lanjut*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Tindak Lanjut</a>
                        </div>
                    </div>

                    <!-- Notifikasi -->
                    <a href="{{ route('wali.notifikasi') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('wali.notifikasi*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-bell w-6 text-center mr-2 {{ request()->routeIs('wali.notifikasi*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Notifikasi</span>
                    </a>

                @else
                    <!-- GURU MAPEL MENU -->
                    <a href="{{ route('guru.dashboard') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('guru.dashboard') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-house w-6 text-center mr-2 {{ request()->routeIs('guru.dashboard') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Submenu Data -->
                    <div>
                        <button onclick="document.getElementById('submenu-data-guru').classList.toggle('hidden')" class="w-full flex items-center justify-between px-4 py-3 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-100">
                            <span class="flex items-center">
                                <i class="fa-solid fa-folder w-6 text-center mr-2 text-edu-orange"></i>
                                <span>Data</span>
                            </span>
                            <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
                        </button>
                        <div id="submenu-data-guru" class="pl-6 space-y-1 {{ request()->routeIs('guru.siswa*') ? '' : 'hidden' }}">
                            <a href="{{ route('guru.siswa') }}" class="block px-4 py-2 text-xs font-semibold rounded-md transition-colors {{ request()->routeIs('guru.siswa*') ? 'bg-edu-orange text-white' : 'text-gray-600 hover:bg-blue-50' }}">Siswa</a>
                        </div>
                    </div>

                    <!-- Nilai Siswa (Guru Mapel) -->
                    <a href="{{ route('guru.nilai') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('guru.nilai*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-book-open w-6 text-center mr-2 {{ request()->routeIs('guru.nilai*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Nilai Siswa</span>
                    </a>

                    <!-- Notifikasi -->
                    <a href="{{ route('guru.notifikasi') }}" class="flex items-center px-4 py-3 rounded-lg text-sm font-semibold transition-colors {{ request()->routeIs('guru.notifikasi*') ? 'bg-edu-blue text-white shadow' : 'text-gray-700 hover:bg-gray-100' }}">
                        <i class="fa-solid fa-bell w-6 text-center mr-2 {{ request()->routeIs('guru.notifikasi*') ? 'text-white' : 'text-edu-orange' }}"></i>
                        <span>Notifikasi</span>
                    </a>
                @endif

            </nav>
        </div>

        <!-- PROFILE AT BOTTOM OF SIDEBAR -->
        <div class="p-4 border-t border-gray-100 bg-gray-50/70">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-full bg-edu-orange flex items-center justify-center text-white font-bold text-lg shadow-sm">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-gray-900 truncate font-heading">{{ $currentUser->displayName ?? 'User EduTrack' }}</p>
                    <p class="text-[11px] text-gray-500 font-medium truncate">{{ $role }}</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT AREA -->
    <main class="ml-64 flex-1 min-w-0 p-8 min-h-screen">
        <!-- TOP HEADER -->
        <header class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-black text-white font-heading tracking-wide">@yield('page_title', 'Dashboard')</h1>
                <nav class="mt-0.5 flex items-center flex-wrap gap-1 text-sm font-medium font-heading text-blue-100" aria-label="Breadcrumb">
                    @hasSection('breadcrumb')
                        @yield('breadcrumb')
                    @elseif(View::hasSection('page_subtitle'))
                        @php
                            $rawSub = trim(View::yieldContent('page_subtitle'));
                            $decodedSub = html_entity_decode($rawSub, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                            $isBreadcrumb = str_contains($decodedSub, '>');
                        @endphp
                        @if($isBreadcrumb)
                            @php
                                $crumbs = array_map('trim', explode('>', $decodedSub));
                                $totalCrumbs = count($crumbs);
                                $rolePrefix = $role === 'Operator' ? 'operator' : ($role === 'Wali Kelas' ? 'wali' : 'guru');
                            @endphp
                            @foreach($crumbs as $cIdx => $crumb)
                                @php
                                    $isLast = ($cIdx === $totalCrumbs - 1);
                                    $crumbLower = strtolower($crumb);
                                    $targetUrl = null;

                                    if ($crumbLower === 'dashboard') {
                                        $targetUrl = route($rolePrefix . '.dashboard');
                                    } elseif ($crumbLower === 'data') {
                                        $targetUrl = route($rolePrefix . '.siswa');
                                    } elseif ($crumbLower === 'siswa' || $crumbLower === 'siswa kelas') {
                                        $targetUrl = route($rolePrefix . '.siswa');
                                    } elseif ($crumbLower === 'guru') {
                                        $targetUrl = route('operator.guru');
                                    } elseif ($crumbLower === 'kelas') {
                                        $targetUrl = route('operator.kelas');
                                    } elseif ($crumbLower === 'mata pelajaran' || $crumbLower === 'mapel') {
                                        $targetUrl = route('operator.mapel');
                                    } elseif ($crumbLower === 'ekstrakulikuler' || $crumbLower === 'ekstrakurikuler') {
                                        $targetUrl = $role === 'Operator' ? route('operator.ekskul') : route('wali.presensi-ekskul');
                                    } elseif ($crumbLower === 'nilai' || $crumbLower === 'input nilai') {
                                        if (Route::has($rolePrefix . '.nilai')) {
                                            $targetUrl = route($rolePrefix . '.nilai');
                                        } elseif (Route::has($rolePrefix . '.prestasi')) {
                                            $targetUrl = route($rolePrefix . '.prestasi');
                                        }
                                    } elseif ($crumbLower === 'capaian' || $crumbLower === 'input capaian') {
                                        $targetUrl = Route::has($rolePrefix . '.capaian') ? route($rolePrefix . '.capaian') : null;
                                    } elseif ($crumbLower === 'prestasi' || $crumbLower === 'input prestasi') {
                                        $targetUrl = route('wali.prestasi');
                                    } elseif ($crumbLower === 'presensi' || $crumbLower === 'presensi kelas') {
                                        $targetUrl = Route::has($rolePrefix . '.presensi-kelas') ? route($rolePrefix . '.presensi-kelas') : null;
                                    } elseif ($crumbLower === 'early warning' || $crumbLower === 'early warning home') {
                                        $targetUrl = Route::has($rolePrefix . '.early-warning') ? route($rolePrefix . '.early-warning') : null;
                                    } elseif ($crumbLower === 'tindak lanjut') {
                                        $targetUrl = Route::has($rolePrefix . '.tindak-lanjut') ? route($rolePrefix . '.tindak-lanjut') : null;
                                    } elseif ($crumbLower === 'rapor') {
                                        $targetUrl = route($rolePrefix . '.rapor');
                                    } elseif ($crumbLower === 'laporan') {
                                        $targetUrl = route('operator.laporan');
                                    } elseif ($crumbLower === 'notifikasi') {
                                        $targetUrl = route($rolePrefix . '.notifikasi');
                                    }
                                @endphp

                                @if($isLast)
                                    <span class="text-white font-bold tracking-wide" aria-current="page">{{ $crumb }}</span>
                                @elseif($targetUrl)
                                    <a href="{{ $targetUrl }}" class="text-blue-100 hover:text-white hover:underline transition-colors font-medium">{{ $crumb }}</a>
                                    <i class="fa-solid fa-chevron-right text-[10px] mx-1.5 text-blue-300"></i>
                                @else
                                    <span class="text-blue-200">{{ $crumb }}</span>
                                    <i class="fa-solid fa-chevron-right text-[10px] mx-1.5 text-blue-300"></i>
                                @endif
                            @endforeach
                        @else
                            <span class="text-blue-200">{{ $decodedSub }}</span>
                        @endif
                    @else
                        <span class="text-blue-200">Selamat Datang, {{ $currentUser->displayName ?? '' }}</span>
                    @endif
                </nav>
            </div>

            <div class="flex items-center space-x-6">
                <!-- Date in Indonesian -->
                <div class="text-white text-sm font-semibold font-heading tracking-wider">
                    Kamis, 1 Oktober 2026
                </div>

                <!-- LOGOUT BUTTON -->
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" title="Logout" class="w-10 h-10 rounded-lg bg-edu-orange hover:bg-edu-orangeHover text-white flex items-center justify-center transition shadow-md">
                        <i class="fa-solid fa-arrow-right-from-bracket text-lg"></i>
                    </button>
                </form>
            </div>
        </header>

        <!-- FLASH NOTIFICATION ALERTS -->
        @if(session('success'))
            <div class="mb-4 bg-emerald-500 text-white px-4 py-3 rounded-xl shadow-md flex items-center justify-between animate-fadeIn">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-check text-lg"></i>
                    <span class="text-sm font-medium font-heading">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-100"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-4 bg-red-500 text-white px-4 py-3 rounded-xl shadow-md flex items-center justify-between animate-fadeIn">
                <div class="flex items-center space-x-2">
                    <i class="fa-solid fa-circle-exclamation text-lg"></i>
                    <span class="text-sm font-medium font-heading">{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-100"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif

        <!-- PAGE CONTENT -->
        @yield('content')
    </main>

    <script>
        function exportTableToExcel(tableSelector = '.excel-table', filename = 'Data_Export') {
            const table = document.querySelector(tableSelector);
            if (!table) {
                alert('Tabel tidak ditemukan untuk diekspor.');
                return;
            }

            const cleanFilename = filename.replace(/\.(xlsx|xls|csv)$/i, '');

            // Clone table agar bisa menghapus kolom "Aksi" tanpa mengganggu UI di halaman
            const clonedTable = table.cloneNode(true);

            // Deteksi kolom "Aksi" dari header
            const thead = clonedTable.querySelector('thead');
            const headerCells = thead ? Array.from(thead.querySelectorAll('th')) : [];
            const skipIndexes = [];
            headerCells.forEach((th, i) => {
                const text = th.innerText.trim().toLowerCase();
                if (text.includes('aksi') || text === '') {
                    // Cek jika kolom ini memang kolom tombol/aksi
                    if (text.includes('aksi') || th.querySelector('button, a, input[type="checkbox"]')) {
                        skipIndexes.push(i);
                    }
                }
            });

            // Urutkan index dari belakang agar penghapusan tidak merusak urutan kolom lain
            skipIndexes.sort((a, b) => b - a);

            clonedTable.querySelectorAll('tr').forEach(row => {
                skipIndexes.forEach(idx => {
                    if (row.children[idx]) {
                        row.removeChild(row.children[idx]);
                    }
                });
            });

            // 1. Jika SheetJS (XLSX) tersedia, ekspor file asli .xlsx (Excel Workbook murni)
            if (typeof XLSX !== 'undefined') {
                const wb = XLSX.utils.table_to_book(clonedTable, { sheet: "Sheet1", raw: true });
                XLSX.writeFile(wb, cleanFilename + '.xlsx');
                return;
            }

            // 2. Fallback: HTML Spreadsheet Table format (.xls)
            const htmlTable = clonedTable.outerHTML;
            const excelTemplate = `
                <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                <head><meta charset="utf-8"></head>
                <body>${htmlTable}</body>
                </html>`;
            const blob = new Blob([excelTemplate], { type: 'application/vnd.ms-excel;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = cleanFilename + '.xls';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
    @stack('scripts')
</body>
</html>
