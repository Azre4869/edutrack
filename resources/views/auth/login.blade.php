<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack - Login</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    {{-- Tailwind CSS CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
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
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-edu-blue flex items-center justify-center font-heading p-4">


    <div class="w-full max-w-md">
        <div class="bg-white rounded-[32px] shadow-2xl p-8 md:p-10 border border-white/20">

            {{-- Header Card Login --}}
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-orange-100 text-edu-orange text-3xl mb-3 shadow-inner">
                    <i class="fa-solid fa-graduation-cap"></i>
                </div>
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">EduTrack</h1>
                <p class="text-xs text-gray-400 mt-1 font-medium">Sistem Informasi Akademik SMPN 1 Sampang</p>
            </div>

            {{-- Pesan Flash Logout (jika ada) --}}
            @if(session('pesan'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-xs font-semibold">
                    {{ session('pesan') }}
                </div>
            @endif

            {{--
                Menampilkan Pesan Error:
                Variabel $errors otomatis disediakan Laravel jika validasi form gagal
                atau jika Controller mengirim ->withErrors(...)
            --}}
            @if($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-xs font-semibold">
                    {{ $errors->first() }}
                </div>
            @endif

            {{--
                FORM LOGIN:
                action="{{ route('login.proses') }}" -> Rute POST /login di routes/web.php
                method="POST"                        -> Metode HTTP POST
                @csrf                                -> Token keamanan wajib Laravel
            --}}
            <form action="{{ route('login.proses') }}" method="POST" class="space-y-4">
                @csrf

                {{-- Input Username --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Username</label>
                    {{--
                        old('username') berguna agar teks yang diketik tidak hilang
                        jika terjadi kesalahan login / validasi
                    --}}
                    <input type="text"
                           name="username"
                           value="{{ old('username', 'operator') }}"
                           placeholder="Contoh: operator"
                           required
                           class="w-full px-4 py-3.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange transition">
                </div>

                {{-- Input Password --}}
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Password</label>
                    <input type="password"
                           name="password"
                           value="password"
                           placeholder="Masukkan password"
                           required
                           class="w-full px-4 py-3.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange transition">
                </div>

                {{-- Tombol Submit Login --}}
                <button type="submit"
                        class="w-full bg-edu-orange hover:bg-edu-orangeHover text-white py-3.5 px-6 rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-orange-500/30 transition duration-200 mt-2">
                    Masuk ke Sistem
                </button>
            </form>

            {{-- Panduan Akun Skeleton untuk Belajar/Testing --}}
            <div class="mt-8 pt-5 border-t border-gray-100 text-center">
                <div class="grid grid-cols-3 gap-1.5 text-[11px] font-medium text-gray-600">
                    <div class="bg-gray-50 py-1.5 px-1 rounded-lg border border-gray-100">
                        <b class="text-gray-900 block">operator</b> (Admin)
                    </div>
                    <div class="bg-gray-50 py-1.5 px-1 rounded-lg border border-gray-100">
                        <b class="text-gray-900 block">walikelas</b> (Wali)
                    </div>
                    <div class="bg-gray-50 py-1.5 px-1 rounded-lg border border-gray-100">
                        <b class="text-gray-900 block">gurumapel</b> (Guru)
                    </div>
                </div>
            </div>

        </div>
    </div>

</body>
</html>
