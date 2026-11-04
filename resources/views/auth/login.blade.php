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

    <!-- Vite Assets (Tailwind CSS) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen relative flex items-center justify-center bg-cover bg-center font-heading" style="background-image: url('{{ asset('images/login-bg.jpg') }}?v={{ filemtime(public_path('images/login-bg.jpg')) }}');">

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

                <!-- USERNAME FIELD -->
                <div>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" placeholder="Username" required maxlength="50" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange focus:border-transparent transition">
                </div>

                <!-- PASSWORD FIELD -->
                <div>
                    <input type="password" id="password" name="password" placeholder="Password" required maxlength="255" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl text-sm text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange focus:border-transparent transition">
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
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
        }
    </script>
</body>
</html>
