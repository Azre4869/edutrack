@extends('layouts.app')

@section('title', 'Early Warning System')
@section('page_title', 'Early Warning')
@section('page_subtitle', 'Dashboard > Early Warning')

@section('content')
<!-- 4 TOP STAT CARDS -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6 font-heading">
    <!-- Tinggi -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex items-center space-x-4">
        <div class="w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-2xl flex-shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-red-600">Siswa Berisiko Tinggi</p>
            <h3 class="text-2xl font-black text-gray-900 mt-1">
                {{ $stats['tinggi']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['tinggi']['pct'] }})</span>
            </h3>
        </div>
    </div>

    <!-- Sedang -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex items-center space-x-4">
        <div class="w-14 h-14 rounded-full bg-amber-100 text-amber-500 flex items-center justify-center text-2xl flex-shrink-0">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-amber-500">Siswa Berisiko Sedang</p>
            <h3 class="text-2xl font-black text-gray-900 mt-1">
                {{ $stats['sedang']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['sedang']['pct'] }})</span>
            </h3>
        </div>
    </div>

    <!-- Kecil -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex items-center space-x-4">
        <div class="w-14 h-14 rounded-full bg-blue-100 text-edu-blue flex items-center justify-center text-2xl flex-shrink-0">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-edu-blue">Siswa Berisiko Kecil</p>
            <h3 class="text-2xl font-black text-gray-900 mt-1">
                {{ $stats['kecil']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['kecil']['pct'] }})</span>
            </h3>
        </div>
    </div>

    <!-- Normal -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex items-center space-x-4">
        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-2xl flex-shrink-0">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-emerald-600">Status Normal</p>
            <h3 class="text-2xl font-black text-gray-900 mt-1">
                {{ $stats['normal']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['normal']['pct'] }})</span>
            </h3>
        </div>
    </div>
</div>

<!-- FILTER BAR -->
<div class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 mb-6 font-heading">
    <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4 flex-1">
            <!-- Tahun Ajaran -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="2026/2027" selected>2026/2027</option>
                    <option value="2025/2026">2025/2026</option>
                </select>
            </div>

            <!-- Semester -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                <select name="semester_id" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    @foreach($semesterList as $sm)
                        <option value="{{ $sm->id_semester }}" {{ $selectedSemesterId == $sm->id_semester ? 'selected' : '' }}>{{ $sm->nama_semester }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Kelas -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas</label>
                <select name="kelas_id" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search -->
            <div class="pt-4 flex-1 min-w-[240px]">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-edu-orange text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau kelas siswa......" class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                </div>
            </div>
        </div>

        <!-- Reset Filter Button -->
        <div class="pt-4 flex space-x-2">
            <a href="{{ url()->current() }}" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-6 rounded-xl shadow-md flex items-center space-x-2 transition">
                <i class="fa-solid fa-rotate-right"></i>
                <span>Reset Filter</span>
            </a>
        </div>
    </form>
</div>

<!-- MAIN GRID: TABLE (LEFT) & DETAIL SISWA (RIGHT) -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- LEFT TABLE (SPAN 8) -->
    <div class="lg:col-span-8 bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto max-h-[640px]">
            <table class="w-full excel-table border-collapse text-xs text-left">
                <thead>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                        <th class="p-3 w-12 border border-black">No.</th>
                        <th class="p-3 w-24 border border-black">NIS/NISN</th>
                        <th class="p-3 min-w-[180px] border border-black">NAMA</th>
                        <th class="p-3 w-16 border border-black">Kelas</th>
                        <th class="p-3 min-w-[140px] border border-black">Jenis Peringatan</th>
                        <th class="p-3 min-w-[200px] border border-black">Indikator</th>
                        <th class="p-3 w-28 border border-black">Tingkat Risiko</th>
                        <th class="p-3 w-16 border border-black">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black bg-white font-body">
                    @forelse($siswaEarlyWarning as $idx => $sw)
                        @php
                            $isSelected = $sw['id_siswa'] == $selectedSiswa['id_siswa'];
                        @endphp
                        <tr class="transition cursor-pointer {{ $isSelected ? 'bg-orange-50/70 font-semibold' : 'hover:bg-blue-50/40' }}" onclick="window.location.href='?selected_id={{ $sw['id_siswa'] }}'">
                            <td class="p-2.5 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2.5 text-center border border-black font-medium">{{ $sw['nis'] }}</td>
                            <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $sw['nama'] }}</td>
                            <td class="p-2.5 text-center border border-black">{{ $sw['kelas'] }}</td>
                            <td class="p-2.5 border border-black">{{ $sw['jenis_peringatan'] }}</td>
                            <td class="p-2.5 border border-black">{{ $sw['indikator'] }}</td>
                            <td class="p-2.5 text-center border border-black">
                                @if($sw['tingkat_risiko'] === 'Tinggi')
                                    <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full bg-red-100 text-red-600">
                                        <i class="fa-solid fa-circle-exclamation mr-1 text-[10px]"></i> Tinggi
                                    </span>
                                @elseif($sw['tingkat_risiko'] === 'Sedang')
                                    <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full bg-amber-100 text-amber-600">
                                        <i class="fa-solid fa-circle-exclamation mr-1 text-[10px]"></i> Sedang
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 text-[11px] font-bold rounded-full bg-blue-100 text-edu-blue">
                                        <i class="fa-solid fa-circle-exclamation mr-1 text-[10px]"></i> Kecil
                                    </span>
                                @endif
                            </td>
                            <td class="p-2.5 text-center border border-black">
                                <a href="?selected_id={{ $sw['id_siswa'] }}" class="w-6 h-6 rounded bg-edu-blue hover:bg-blue-700 text-white inline-flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500 border border-black">Tidak ada data peringatan dini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- RIGHT SIDE PANEL: DETAIL SISWA (SPAN 4) -->
    <div class="lg:col-span-4 bg-white rounded-3xl p-6 shadow-xl border border-gray-200 flex flex-col justify-between">
        <div>
            <!-- Header Detail Siswa -->
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <h3 class="text-xl font-black text-edu-blue font-heading">Detail Siswa</h3>
            </div>

            <!-- Student Profile Badge -->
            <div class="mt-4 flex items-center space-x-3.5">
                <div class="w-14 h-14 rounded-full bg-edu-orange text-white flex items-center justify-center text-2xl shadow-sm flex-shrink-0">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <h4 class="text-base font-extrabold text-gray-900 font-heading">{{ $selectedSiswa['nama'] }}</h4>
                    <p class="text-xs text-gray-500 font-medium">NISN {{ $selectedSiswa['nis'] }} | {{ $selectedSiswa['kelas'] }}</p>
                    <span class="inline-block mt-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-600">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Risiko {{ $selectedSiswa['tingkat_risiko'] }}
                    </span>
                </div>
            </div>

            <!-- Red Alert Banner -->
            <div class="mt-5 p-3.5 bg-red-50 border border-red-200 rounded-2xl flex items-start space-x-3">
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-lg mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-red-700 font-medium leading-relaxed">
                    Pastikan semua data nilai, kehadiran, pengisian ekstrakulikuler, dan prestasi sudah lengkap sebelum cetak rapor.
                </p>
            </div>

            <!-- Ringkasan Stats (3 Mini Cards) -->
            <div class="mt-5">
                <h5 class="text-xs font-bold text-gray-700 font-heading mb-2">Ringkasan</h5>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2.5 rounded-xl bg-blue-50/60 border border-blue-100">
                        <i class="fa-solid fa-chart-line text-edu-blue text-xs block mb-1"></i>
                        <span class="text-[10px] text-gray-500 block">Rata-rata Nilai</span>
                        <strong class="text-xs text-gray-900 font-black font-heading">{{ $selectedSiswa['rata_nilai'] }}</strong>
                        <span class="text-[9px] text-red-500 font-bold block">&darr; {{ $selectedSiswa['penurunan_nilai'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-blue-50/60 border border-blue-100">
                        <i class="fa-regular fa-calendar-check text-edu-blue text-xs block mb-1"></i>
                        <span class="text-[10px] text-gray-500 block">Kehadiran</span>
                        <strong class="text-xs text-gray-900 font-black font-heading">{{ $selectedSiswa['kehadiran'] }}</strong>
                        <span class="text-[9px] text-red-500 font-bold block">&darr; {{ $selectedSiswa['penurunan_kehadiran'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-xl bg-blue-50/60 border border-blue-100">
                        <i class="fa-solid fa-star text-edu-blue text-xs block mb-1"></i>
                        <span class="text-[10px] text-gray-500 block">Ekstrakulikuler</span>
                        <strong class="text-xs text-gray-900 font-black font-heading">{{ $selectedSiswa['ekskul_status'] }}</strong>
                        <span class="text-[9px] text-emerald-600 font-bold block">Terdaftar</span>
                    </div>
                </div>
            </div>

            <!-- Detail Indikator Breakdown -->
            <div class="mt-5">
                <h5 class="text-xs font-bold text-gray-700 font-heading mb-2">Detail Indikator</h5>
                <div class="space-y-2 text-xs">
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-chart-column text-red-500 mt-0.5 text-xs"></i>
                        <div>
                            <span class="font-bold text-gray-800">Rata-rata Nilai:</span>
                            <span class="text-gray-600">{{ $selectedSiswa['rata_nilai'] }} (turun {{ $selectedSiswa['penurunan_nilai'] }})</span>
                        </div>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-calendar-xmark text-red-500 mt-0.5 text-xs"></i>
                        <div>
                            <span class="font-bold text-gray-800">Kehadiran:</span>
                            <span class="text-gray-600">{{ $selectedSiswa['kehadiran'] }} ({{ $selectedSiswa['alpha'] }})</span>
                        </div>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-book-open text-amber-500 mt-0.5 text-xs"></i>
                        <div>
                            <span class="font-bold text-gray-800">Capaian Kompetensi:</span>
                            <span class="text-gray-600">{{ $selectedSiswa['kkm_info'] }}</span>
                        </div>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-user-shield text-emerald-500 mt-0.5 text-xs"></i>
                        <div>
                            <span class="font-bold text-gray-800">Perilaku:</span>
                            <span class="text-gray-600">{{ $selectedSiswa['perilaku'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tindakan Yang Disarankan Box -->
            <div class="mt-5 p-3.5 bg-blue-50 border border-blue-200 rounded-2xl">
                <div class="flex items-start space-x-2.5">
                    <i class="fa-solid fa-circle-exclamation text-edu-blue text-sm mt-0.5 flex-shrink-0"></i>
                    <div>
                        <h6 class="text-xs font-bold text-edu-blue font-heading">Tindakan yang disarankan</h6>
                        <ul class="mt-1 text-[11px] text-gray-700 list-disc list-inside space-y-0.5">
                            <li>Lakukan konseling dengan siswa dan orang tua</li>
                            <li>Pantau perkembangan nilai dan kehadiran</li>
                            <li>Berikan motivasi dan pendampingan belajar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ACTION BUTTON KE TINDAK LANJUT -->
        <div class="mt-6 pt-4 border-t border-gray-100">
            <a href="{{ route('wali.tindak-lanjut', $selectedSiswa['id_siswa']) }}" class="w-full bg-edu-blue hover:bg-blue-800 text-white py-3 px-4 rounded-xl text-xs font-bold font-heading flex items-center justify-center space-x-2 shadow-md transition">
                <span>Tindak Lanjut Siswa Ini</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>
    </div>
</div>
@endsection
