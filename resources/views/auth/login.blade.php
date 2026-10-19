<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduTrack - Login</title>

    <!-- Google Fonts: Montserrat (Heading) & Lora (Body) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700&family=Montserrat:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        edu: {
                            blue: '#2F5CAB',
                            orange: '#E04F26',
                            orangeHover: '#CA421B',
                        }
                    },
                    fontFamily: {
                        heading: ['Montserrat', 'sans-serif'],
                        body: ['Lora', 'serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen relative flex items-center justify-center bg-cover bg-center font-heading" style="background-image: url('{{ asset('images/login-bg.png') }}');">

    <!-- DARK OVERLAY -->
    <div class="absolute inset-0 bg-black/45 backdrop-blur-[2px]"></div>

    <!-- LOGIN CONTAINER CARD -->
    <div class="relative z-10 w-full max-w-lg mx-4">
        <div class="bg-white rounded-[36px] shadow-2xl p-10 md:p-12 border border-white/40">
            <!-- LOGO BRAND -->
            <div class="flex justify-center mb-6">
                <img src="{{ asset('images/logo-edutrack.png') }}" alt="EduTrack Logo" class="h-16 object-contain">
            </div>

            <!-- TITLE -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Welcome</h1>
                <p class="text-xs text-gray-400 mt-1.5 font-medium">Login to continue to your account</p>
            </div>

            @if($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl text-xs font-semibold">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- LOGIN FORM -->
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf

                <!-- ROLE SELECTION ACCORDION / SELECTOR -->
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Pilih Peran Pengguna</label>
                    <div class="relative">
                        <select id="role-select" name="role" onchange="autoFillCredentials(this.value)" class="w-full px-4 py-3.5 bg-gray-50 border border-gray-300 rounded-xl text-sm font-semibold text-gray-800 focus:outline-none focus:ring-2 focus:ring-edu-orange focus:border-transparent transition appearance-none">
                            <option value="Operator-Web" {{ old('role') == 'Operator-Web' ? 'selected' : '' }}>Operator-Web</option>
                            <option value="Guru-Wali" {{ old('role') == 'Guru-Wali' || !old('role') ? 'selected' : '' }}>Guru-Wali</option>
                            <option value="Guru-Mapel" {{ old('role') == 'Guru-Mapel' ? 'selected' : '' }}>Guru-Mapel</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>

                <!-- USERNAME FIELD -->
                <div>
                    <input type="text" id="username" name="username" value="{{ old('username', 'walikelas') }}" placeholder="Username" required class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange focus:border-transparent transition">
                </div>

                <!-- PASSWORD FIELD -->
                <div>
                    <input type="password" id="password" name="password" value="password" placeholder="Password" required class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange focus:border-transparent transition">
                </div>

                <!-- SHOW PASSWORD & FORGOT PASSWORD -->
                <div class="flex items-center justify-between text-xs pt-1 pb-2">
                    <label class="flex items-center space-x-2 text-gray-500 cursor-pointer select-none">
                        <input type="checkbox" id="show-password" onchange="togglePassword()" class="rounded border-gray-300 text-edu-orange focus:ring-edu-orange">
                        <span>Show Password</span>
                    </label>
                    <a href="javascript:alert('Silakan hubungi administrator sekolah untuk reset password.')" class="text-edu-orange hover:underline font-semibold">
                        Forgot Password
                    </a>
                </div>

                <!-- LOGIN BUTTON -->
                <button type="submit" class="w-full bg-edu-orange hover:bg-edu-orangeHover text-white py-3.5 px-6 rounded-xl font-bold text-sm tracking-wide shadow-lg shadow-orange-500/30 transition duration-200 transform active:scale-[0.99]">
                    Login
                </button>
            </form>

            <!-- DEMO SHORTCUT BUTTONS & CREDENTIALS INFO -->
            <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wider mb-3">Akses Cepat (Demo Mode)</p>
                <div class="grid grid-cols-3 gap-2">
                    <a href="{{ route('quick-login', 'operator') }}" class="py-2 px-2 bg-blue-50 hover:bg-blue-100 text-edu-blue rounded-lg text-xs font-bold transition text-center">
                        <i class="fa-solid fa-user-shield block mb-1"></i> Operator
                    </a>
                    <a href="{{ route('quick-login', 'wali') }}" class="py-2 px-2 bg-orange-50 hover:bg-orange-100 text-edu-orange rounded-lg text-xs font-bold transition text-center">
                        <i class="fa-solid fa-chalkboard-user block mb-1"></i> Wali Kelas
                    </a>
                    <a href="{{ route('quick-login', 'guru') }}" class="py-2 px-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-600 rounded-lg text-xs font-bold transition text-center">
                        <i class="fa-solid fa-book-open-reader block mb-1"></i> Guru Mapel
                    </a>
                </div>

                <div class="mt-4 text-[11px] text-gray-500 bg-gray-50/80 p-3 rounded-xl text-left border border-gray-200">
                    <p class="font-bold text-gray-700 mb-1.5 flex items-center">
                        <i class="fa-solid fa-circle-info text-edu-orange mr-1.5"></i>
                        <span>Akun Testing (Password: <code class="bg-gray-200 px-1 py-0.5 rounded text-edu-orange font-bold">password</code> / <code class="bg-gray-200 px-1 py-0.5 rounded text-edu-orange font-bold">123456</code>)</span>
                    </p>
                    <div class="grid grid-cols-3 gap-1 text-[10px] text-gray-600 font-medium">
                        <div>• OP: <b class="text-gray-900">operator</b></div>
                        <div>• Wali: <b class="text-gray-900">walikelas</b></div>
                        <div>• Guru: <b class="text-gray-900">gurumapel</b></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
        }

        function autoFillCredentials(role) {
            const username = document.getElementById('username');
            if (role === 'Operator-Web') {
                username.value = 'operator';
            } else if (role === 'Guru-Wali') {
                username.value = 'walikelas';
            } else if (role === 'Guru-Mapel') {
                username.value = 'gurumapel';
            }
        }
    </script>
</body>
</html>
