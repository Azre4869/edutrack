@extends('layouts.app')

@section('title', 'Early Warning System')
@section('page_title', 'Early Warning')
@section('page_subtitle', 'Dashboard > Early Warning')

@section('content')
@php
    $rolePrefix = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'wali';
    $isWali = auth()->user()->isWaliKelas();

    // Helper closure: ringkas teks indikator agar tidak memenuhi kolom
    $ringkasIndikator = function(string $raw): string {
        return preg_replace_callback(
            '/Di bawah KKM \((\d+) mapel:[^)]+\)/i',
            fn($m) => "{$m[1]} mapel di bawah KKM",
            $raw
        );
    };
@endphp

{{-- ======= 4 TOP STAT CARDS ======= --}}
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 font-heading">
    <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-md border border-gray-100 flex items-center space-x-3">
        <div class="w-12 h-12 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-red-600">Risiko Tinggi</p>
            <h3 class="text-xl sm:text-2xl font-black text-gray-900 mt-0.5">
                {{ $stats['tinggi']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['tinggi']['pct'] }})</span>
            </h3>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-md border border-gray-100 flex items-center space-x-3">
        <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-500 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-circle-exclamation"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-amber-500">Risiko Sedang</p>
            <h3 class="text-xl sm:text-2xl font-black text-gray-900 mt-0.5">
                {{ $stats['sedang']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['sedang']['pct'] }})</span>
            </h3>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-md border border-gray-100 flex items-center space-x-3">
        <div class="w-12 h-12 rounded-full bg-blue-100 text-edu-blue flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-circle-info"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-edu-blue">Risiko Kecil</p>
            <h3 class="text-xl sm:text-2xl font-black text-gray-900 mt-0.5">
                {{ $stats['kecil']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['kecil']['pct'] }})</span>
            </h3>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-4 sm:p-5 shadow-md border border-gray-100 flex items-center space-x-3">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <p class="text-xs font-bold text-emerald-600">Status Normal</p>
            <h3 class="text-xl sm:text-2xl font-black text-gray-900 mt-0.5">
                {{ $stats['normal']['count'] }} <span class="text-xs font-semibold text-gray-400">({{ $stats['normal']['pct'] }})</span>
            </h3>
        </div>
    </div>
</div>

{{-- ======= FILTER BAR ======= --}}
<div class="bg-white rounded-3xl p-4 sm:p-5 shadow-lg border border-gray-100 mb-6 font-heading">
    <form action="{{ url()->current() }}" method="GET" class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:flex lg:flex-wrap items-end gap-3 sm:gap-4 flex-1">
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran" onchange="this.form.submit()" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($tahunAjaranList ?? \App\Models\Semester::getTahunAjaranList() as $ta)
                        <option value="{{ $ta }}" {{ ($selectedTahunAjaran ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                <select name="semester_id" onchange="this.form.submit()" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($semesterList as $sm)
                        <option value="{{ $sm->id_semester }}" {{ $selectedSemesterId == $sm->id_semester ? 'selected' : '' }}>{{ $sm->nama_semester }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                    Kelas
                    @if($isWali)
                        <span class="text-[10px] text-edu-blue font-normal"><i class="fa-solid fa-lock text-[9px] mr-0.5"></i>Binaan</span>
                    @endif
                </label>
                <select name="kelas_id" onchange="this.form.submit()" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-span-2 sm:col-span-3 lg:flex-1 min-w-[200px]">
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Cari Siswa</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-edu-orange text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama, NIS, atau kelas..."
                           class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                </div>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto">
            <a href="{{ route($rolePrefix . '.early-warning.ai', array_filter(['semester_id' => $selectedSemesterId, 'kelas_id' => $selectedKelasId])) }}"
               class="flex-1 sm:flex-none justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md flex items-center space-x-1.5 transition"
               style="background-color: #4f46e5; color: #ffffff;">
                <i class="fa-solid fa-brain text-white"></i>
                <span class="text-white">Analisis AI</span>
            </a>
            @if(!$isWali)
            <a href="{{ route('operator.early-warning.kriteria') }}"
               class="flex-1 sm:flex-none justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 px-4 rounded-xl shadow-sm flex items-center space-x-1.5 transition">
                <i class="fa-solid fa-sliders"></i>
                <span>Kelola Kriteria</span>
            </a>
            @endif
            <button type="submit" form="form-run-ews"
                    onclick="return confirm('Jalankan analisis Early Warning sekarang?')"
                    class="flex-1 sm:flex-none justify-center bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md flex items-center space-x-1.5 transition cursor-pointer"
                    style="background-color: #059669; color: #ffffff;">
                <i class="fa-solid fa-bolt text-white"></i>
                <span class="text-white">Jalankan Analisis</span>
            </button>
            <a href="{{ url()->current() }}"
               class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-4 rounded-xl shadow-md flex items-center justify-center space-x-1.5 transition"
               style="background-color: #E04F26; color: #ffffff;">
                <i class="fa-solid fa-rotate-right text-white"></i>
                <span class="text-white">Reset</span>
            </a>
        </div>
    </form>
                <i class="fa-solid fa-rotate-right text-white"></i>
                <span class="text-white">Reset</span>
            </a>
        </div>
    </form>
    <form id="form-run-ews" action="{{ route($rolePrefix.'.early-warning.run') }}" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
        <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">
    </form>
</div>

{{-- ======= LAST LOG BANNER ======= --}}
@if(isset($lastLog) && $lastLog)
<div class="mb-6 px-4 py-2.5 bg-blue-50/70 border border-blue-100 rounded-2xl flex flex-wrap items-center justify-between text-xs text-blue-900 font-heading gap-2">
    <div class="flex items-center space-x-2">
        <i class="fa-solid fa-clock-rotate-left text-edu-blue"></i>
        <span>
            <strong>Analisis Terakhir:</strong> {{ \Carbon\Carbon::parse($lastLog->waktu_jalan)->translatedFormat('d M Y, H:i') }}
            &bull; Pemicu: <span class="font-semibold">{{ $lastLog->pemicu }}</span>
            &bull; Hasil: {{ $lastLog->jumlah_siswa_diproses }} siswa diproses, <strong>{{ $lastLog->total_berisiko }} berisiko</strong>
        </span>
    </div>
    <span class="text-[10px] px-2.5 py-0.5 rounded-full {{ $lastLog->status === 'Success' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }} font-bold">
        {{ $lastLog->status }}
    </span>
</div>
@endif

{{-- ======= MAIN GRID: TABLE + DETAIL PANEL ======= --}}
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

    {{-- LEFT TABLE (SPAN 8) --}}
    <div class="lg:col-span-8 bg-white rounded-3xl p-3 sm:p-4 shadow-xl border border-gray-200 overflow-hidden">
        <!-- Petunjuk Geser Tabel Mobile -->
        <div class="block lg:hidden mb-2 text-[11px] text-gray-500 font-medium text-right">
            <i class="fa-solid fa-arrows-left-right mr-1 text-edu-orange"></i>Geser tabel untuk melihat seluruh data &rarr;
        </div>
        <div class="overflow-x-auto max-h-[640px]">
            {{-- table-fixed + kolom lebar tetap mencegah scroll horizontal di 1366px --}}
            <table class="w-full min-w-[720px] table-fixed border-collapse text-xs text-left excel-table">
                <thead>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase sticky top-0 z-10">
                        <th class="p-3 w-10 border border-black">No.</th>
                        <th class="p-3 w-20 border border-black">NIS</th>
                        <th class="p-3 border border-black" style="width:148px">NAMA</th>
                        <th class="p-3 w-14 border border-black">Kelas</th>
                        <th class="p-3 border border-black" style="width:110px">Jenis Peringatan</th>
                        <th class="p-3 border border-black" style="width:130px">Indikator</th>
                        <th class="p-3 w-20 border border-black">Risiko</th>
                        <th class="p-3 border border-black" style="width:100px">AI</th>
                        <th class="p-3 w-12 border border-black">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black font-body">
                    @forelse($siswaEarlyWarning as $idx => $sw)
                        @php
                            $isSelected    = $selectedSiswa && $sw['id_siswa'] == ($selectedSiswa['id_siswa'] ?? null);
                            $rowQuery      = array_merge(request()->query(), ['selected_id' => $sw['id_siswa']]);
                            $rowUrl        = route($rolePrefix . '.early-warning', $rowQuery);
                            $rawIndikator  = $sw['indikator'] ?? '';
                            $ringkasedInd  = $ringkasIndikator($rawIndikator);
                        @endphp
                        <tr class="transition cursor-pointer {{ $isSelected ? 'bg-orange-50/80 ring-1 ring-inset ring-edu-orange/40' : 'odd:bg-white even:bg-gray-50/50 hover:bg-blue-50/40' }}"
                            onclick="window.location.href='{{ $rowUrl }}'">
                            <td class="p-2 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2 text-center border border-black font-medium truncate">{{ $sw['nis'] }}</td>
                            <td class="p-2 font-bold text-gray-900 border border-black">
                                <div class="truncate" title="{{ $sw['nama'] }}">{{ $sw['nama'] }}</div>
                            </td>
                            <td class="p-2 text-center border border-black">{{ $sw['kelas'] }}</td>
                            <td class="p-2 border border-black">
                                <div class="line-clamp-2 leading-snug" title="{{ $sw['jenis_peringatan'] }}">{{ $sw['jenis_peringatan'] }}</div>
                            </td>
                            <td class="p-2 border border-black">
                                {{-- Ringkas "N mapel di bawah KKM" agar tidak melebar --}}
                                <div class="line-clamp-2 leading-snug" title="{{ $rawIndikator }}">{{ $ringkasedInd }}</div>
                            </td>
                            <td class="p-2 text-center border border-black">
                                @if($sw['tingkat_risiko'] === 'Tinggi')
                                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-red-100 text-red-600 whitespace-nowrap">
                                        <i class="fa-solid fa-triangle-exclamation mr-0.5 text-[9px]"></i> Tinggi
                                    </span>
                                @elseif($sw['tingkat_risiko'] === 'Sedang')
                                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-600 whitespace-nowrap">
                                        <i class="fa-solid fa-circle-exclamation mr-0.5 text-[9px]"></i> Sedang
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-edu-blue whitespace-nowrap">
                                        <i class="fa-solid fa-circle-info mr-0.5 text-[9px]"></i> Kecil
                                    </span>
                                @endif
                            </td>
                            <td class="p-2 border border-black">
                                @if(!empty($sw['ai_insight']))
                                    @php $ai = $sw['ai_insight']; @endphp
                                    <div class="flex flex-col gap-0.5 items-start">
                                        <span class="inline-block px-1.5 py-0.5 text-[8px] font-bold rounded bg-indigo-50 text-indigo-700 border border-indigo-200 leading-snug max-w-full truncate"
                                              title="{{ $ai->cluster_label }}">
                                            {{ Str::limit($ai->cluster_label, 15) }}
                                        </span>
                                        <div class="flex flex-wrap gap-0.5">
                                            @if($ai->is_anomaly)
                                                <span class="inline-flex items-center gap-0.5 px-1 py-0.5 text-[8px] font-extrabold rounded bg-rose-100 text-rose-700 border border-rose-200 whitespace-nowrap"
                                                      title="Skor: {{ number_format($ai->anomaly_score, 3) }}">
                                                    <i class="fa-solid fa-bolt text-[7px]"></i>Anomali
                                                </span>
                                            @endif
                                            @if($ai->berpotensi_turun)
                                                <span class="inline-flex items-center gap-0.5 px-1 py-0.5 text-[8px] font-bold rounded bg-amber-100 text-amber-700 border border-amber-200 whitespace-nowrap">
                                                    <i class="fa-solid fa-arrow-trend-down text-[7px]"></i>Turun
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[9px] text-gray-300 italic">—</span>
                                @endif
                            </td>
                            <td class="p-2 text-center border border-black">
                                <a href="{{ $rowUrl }}"
                                   onclick="event.stopPropagation()"
                                   class="w-6 h-6 rounded bg-edu-blue hover:bg-blue-700 text-white inline-flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-500 border border-black">
                                @if(!empty($search))
                                    Tidak ada data peringatan dini yang cocok dengan pencarian "{{ $search }}".
                                @else
                                    Tidak ada data peringatan dini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- RIGHT SIDE PANEL: DETAIL SISWA (SPAN 4) --}}
    <div class="lg:col-span-4 bg-white rounded-3xl p-6 shadow-xl border border-gray-200 flex flex-col justify-between">
        @if($selectedSiswa)
        <div>
            {{-- Header --}}
            <div class="flex items-center justify-between pb-4 border-b border-gray-100">
                <h3 class="text-lg font-black text-edu-blue font-heading">Detail Siswa</h3>
            </div>

            {{-- Profil Siswa --}}
            <div class="mt-4 flex items-center space-x-3">
                <div class="w-12 h-12 rounded-full bg-edu-orange text-white flex items-center justify-center text-xl shadow-sm flex-shrink-0">
                    <i class="fa-solid fa-user"></i>
                </div>
                <div>
                    <h4 class="text-sm font-extrabold text-gray-900 font-heading leading-snug">{{ $selectedSiswa['nama'] }}</h4>
                    <p class="text-xs text-gray-500 font-medium">NISN {{ $selectedSiswa['nis'] }} | {{ $selectedSiswa['kelas'] }}</p>
                    @php
                        $risikoColor = match($selectedSiswa['tingkat_risiko']) {
                            'Tinggi' => 'bg-red-100 text-red-600',
                            'Sedang' => 'bg-amber-100 text-amber-600',
                            default  => 'bg-blue-100 text-edu-blue',
                        };
                    @endphp
                    <span class="inline-block mt-1 px-2.5 py-0.5 text-[10px] font-bold rounded-full {{ $risikoColor }}">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Risiko {{ $selectedSiswa['tingkat_risiko'] }}
                    </span>
                </div>
            </div>

            {{-- Alert Banner --}}
            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded-2xl flex items-start space-x-2.5">
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-base mt-0.5 flex-shrink-0"></i>
                <p class="text-xs text-red-700 font-medium leading-relaxed">
                    Pastikan data nilai, kehadiran, ekstrakulikuler, dan prestasi sudah lengkap sebelum cetak rapor.
                </p>
            </div>

            {{-- Ringkasan 3 Cards --}}
            <div class="mt-4">
                <h5 class="text-xs font-bold text-gray-700 font-heading mb-2">Ringkasan</h5>
                <div class="grid grid-cols-3 gap-2 text-center">
                    <div class="p-2 rounded-xl bg-blue-50/60 border border-blue-100">
                        <i class="fa-solid fa-chart-line text-edu-blue text-xs block mb-1"></i>
                        <span class="text-[10px] text-gray-500 block">Rata-rata Nilai</span>
                        <strong class="text-xs text-gray-900 font-black font-heading">{{ $selectedSiswa['rata_nilai'] }}</strong>
                        @if(str_contains($selectedSiswa['penurunan_nilai'], '%'))
                            <span class="text-[9px] text-red-500 font-bold block">&darr; {{ $selectedSiswa['penurunan_nilai'] }}</span>
                        @else
                            <span class="text-[9px] text-gray-400 font-medium block">{{ $selectedSiswa['penurunan_nilai'] }}</span>
                        @endif
                    </div>
                    <div class="p-2 rounded-xl bg-blue-50/60 border border-blue-100">
                        <i class="fa-regular fa-calendar-check text-edu-blue text-xs block mb-1"></i>
                        <span class="text-[10px] text-gray-500 block">Kehadiran</span>
                        <strong class="text-xs text-gray-900 font-black font-heading">{{ $selectedSiswa['kehadiran'] }}</strong>
                        <span class="text-[9px] text-red-500 font-bold block">&darr; {{ $selectedSiswa['penurunan_kehadiran'] }}</span>
                    </div>
                    <div class="p-2 rounded-xl bg-blue-50/60 border border-blue-100">
                        <i class="fa-solid fa-star text-edu-blue text-xs block mb-1"></i>
                        <span class="text-[10px] text-gray-500 block">Ekstrakulikuler</span>
                        <strong class="text-xs text-gray-900 font-black font-heading">{{ $selectedSiswa['ekskul_status'] }}</strong>
                        <span class="text-[9px] text-emerald-600 font-bold block">Terdaftar</span>
                    </div>
                </div>
            </div>

            {{-- Detail Indikator --}}
            <div class="mt-4">
                <h5 class="text-xs font-bold text-gray-700 font-heading mb-2">Detail Indikator</h5>
                <div class="space-y-2 text-xs">
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-chart-column text-red-500 mt-0.5 text-xs flex-shrink-0"></i>
                        <div>
                            <span class="font-bold text-gray-800">Rata-rata Nilai:</span>
                            <span class="text-gray-600">
                                {{ $selectedSiswa['rata_nilai'] }}
                                {{ str_contains($selectedSiswa['penurunan_nilai'], '%') ? '(turun ' . $selectedSiswa['penurunan_nilai'] . ')' : '(' . $selectedSiswa['penurunan_nilai'] . ')' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-calendar-xmark text-red-500 mt-0.5 text-xs flex-shrink-0"></i>
                        <div>
                            <span class="font-bold text-gray-800">Kehadiran:</span>
                            <span class="text-gray-600">{{ $selectedSiswa['kehadiran'] }} ({{ $selectedSiswa['alpha'] }})</span>
                        </div>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-book-open text-amber-500 mt-0.5 text-xs flex-shrink-0"></i>
                        <div>
                            <span class="font-bold text-gray-800">Capaian Kompetensi:</span>
                            <span class="text-gray-600">{{ $selectedSiswa['kkm_info'] }}</span>
                        </div>
                    </div>
                    <div class="flex items-start space-x-2">
                        <i class="fa-solid fa-user-shield text-emerald-500 mt-0.5 text-xs flex-shrink-0"></i>
                        <div>
                            <span class="font-bold text-gray-800">Perilaku:</span>
                            <span class="text-gray-600">{{ $selectedSiswa['perilaku'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== PANEL ANALISIS AI ===== --}}
            @if(!empty($selectedSiswa['ai_insight']))
                @php $ai = $selectedSiswa['ai_insight']; @endphp
                <div class="mt-4 rounded-2xl bg-gradient-to-br from-indigo-50/80 to-purple-50/50 border border-indigo-200 shadow-sm overflow-hidden">
                    {{-- Header AI Panel --}}
                    <div class="flex items-center justify-between px-4 py-2.5 border-b border-indigo-100">
                        <div class="flex items-center space-x-2 min-w-0">
                            <i class="fa-solid fa-brain text-indigo-600 text-sm flex-shrink-0"></i>
                            <div class="min-w-0">
                                <h6 class="text-xs font-black text-indigo-950 font-heading leading-tight">Analisis AI</h6>
                                <span class="text-[9px] text-indigo-500 font-medium">Pendukung keputusan</span>
                            </div>
                        </div>
                        <span class="ml-2 flex-shrink-0 text-[8px] px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 font-bold border border-amber-200 whitespace-nowrap">
                            Data Simulasi
                        </span>
                    </div>

                    <div class="px-4 py-3 space-y-2.5">
                        {{-- Klaster & Status Pola --}}
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <span class="text-gray-500 text-[10px] block mb-0.5">Klaster Pola</span>
                                <span class="font-bold text-indigo-900 bg-white px-2 py-0.5 rounded border border-indigo-200 text-[10px] block truncate"
                                      title="{{ $ai->cluster_label }}">
                                    {{ $ai->cluster_label }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 text-[10px] block mb-0.5">Status Pola</span>
                                @if($ai->is_anomaly)
                                    <span class="font-extrabold text-rose-700 bg-rose-100 px-2 py-0.5 rounded border border-rose-300 text-[10px] block">
                                        <i class="fa-solid fa-bolt mr-0.5"></i> Anomali
                                    </span>
                                @else
                                    <span class="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 text-[10px] block">
                                        <i class="fa-solid fa-check mr-0.5"></i> Pola Wajar
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Alasan anomali (maks 2 baris) --}}
                        @if($ai->is_anomaly && !empty($ai->alasan))
                        <div class="space-y-1">
                            @foreach(array_slice((array)$ai->alasan, 0, 2) as $als)
                            <div class="p-1.5 rounded-xl bg-white border border-rose-200 text-[10px] text-rose-800 leading-snug">
                                <i class="fa-solid fa-circle-exclamation text-rose-500 mr-0.5"></i>
                                {{ $als['teks_penjelasan'] }}
                            </div>
                            @endforeach
                        </div>
                        @endif

                        {{-- Proyeksi 2 kartu --}}
                        <div class="grid grid-cols-2 gap-2 text-center pt-1 border-t border-indigo-100">
                            <div class="p-2 rounded-xl bg-white border border-indigo-100">
                                <span class="text-[9px] text-gray-500 block">Proyeksi Nilai</span>
                                <strong class="text-xs text-indigo-950 font-black font-heading">
                                    {{ number_format($ai->prediksi_nilai, 1, ',', '.') }}
                                </strong>
                                <span class="text-[9px] {{ $ai->slope_nilai < 0 ? 'text-rose-600' : 'text-emerald-600' }} font-bold block">
                                    {{ $ai->slope_nilai >= 0 ? '+' : '' }}{{ number_format($ai->slope_nilai, 2, ',', '.') }} poin/penilaian
                                </span>
                            </div>
                            <div class="p-2 rounded-xl bg-white border border-indigo-100">
                                <span class="text-[9px] text-gray-500 block">Proyeksi Kehadiran</span>
                                <strong class="text-xs text-indigo-950 font-black font-heading">
                                    {{ number_format($ai->prediksi_kehadiran, 1, ',', '.') }}%
                                </strong>
                                <span class="text-[9px] {{ $ai->slope_kehadiran < 0 ? 'text-rose-600' : 'text-emerald-600' }} font-bold block">
                                    {{ $ai->slope_kehadiran >= 0 ? '+' : '' }}{{ number_format($ai->slope_kehadiran, 2, ',', '.') }} poin/minggu
                                </span>
                            </div>
                        </div>

                        @if($ai->berpotensi_turun)
                        <div class="p-2 rounded-xl bg-amber-50 border border-amber-200 flex items-center space-x-1.5 text-[10px] text-amber-900 font-medium">
                            <i class="fa-solid fa-arrow-trend-down text-amber-600 flex-shrink-0"></i>
                            <span>Model memproyeksikan kecenderungan penurunan ke depan.</span>
                        </div>
                        @endif

                        {{-- Tautan ke Analisis AI --}}
                        <a href="{{ route($rolePrefix.'.early-warning.ai', ['semester_id' => $selectedSemesterId]) }}"
                           class="block text-center text-[10px] text-indigo-600 hover:text-indigo-800 font-bold transition">
                            <i class="fa-solid fa-arrow-up-right-from-square mr-1 text-[9px]"></i>
                            Lihat di Analisis AI
                        </a>
                    </div>
                </div>
            @endif

            {{-- Tindakan Yang Disarankan --}}
            <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-2xl">
                <div class="flex items-start space-x-2">
                    <i class="fa-solid fa-circle-exclamation text-edu-blue text-sm mt-0.5 flex-shrink-0"></i>
                    <div>
                        <h6 class="text-xs font-bold text-edu-blue font-heading">Tindakan yang disarankan</h6>
                        <ul class="mt-1.5 text-[11px] text-gray-700 space-y-1 pl-3">
                            <li class="flex items-start gap-1.5"><span class="text-edu-blue mt-0.5">•</span> Lakukan konseling dengan siswa dan orang tua</li>
                            <li class="flex items-start gap-1.5"><span class="text-edu-blue mt-0.5">•</span> Pantau perkembangan nilai dan kehadiran</li>
                            <li class="flex items-start gap-1.5"><span class="text-edu-blue mt-0.5">•</span> Berikan motivasi dan pendampingan belajar</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- ACTION BUTTON --}}
        <div class="mt-5 pt-4 border-t border-gray-100">
            <a href="{{ route($rolePrefix . '.tindak-lanjut', $selectedSiswa['id_siswa']) }}"
               class="w-full bg-edu-blue hover:bg-blue-800 text-white py-3 px-4 rounded-xl text-xs font-bold font-heading flex items-center justify-center space-x-2 shadow-md transition">
                <span>Tindak Lanjut Siswa Ini</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

        @else
        {{-- Empty state kanan --}}
        <div class="flex flex-col items-center justify-center h-full py-16 text-center text-gray-400">
            <i class="fa-solid fa-user-slash text-4xl mb-3 text-gray-300"></i>
            <p class="font-bold text-sm text-gray-700">Tidak Ada Siswa Terpilih</p>
            <p class="text-xs text-gray-400 mt-1 max-w-xs">Klik baris siswa di tabel kiri untuk melihat detail dan analisis AI-nya.</p>
        </div>
        @endif
    </div>

</div>
@endsection
