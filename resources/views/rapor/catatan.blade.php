@extends('layouts.app')

@section('title', 'Input Catatan Sikap & Wali Kelas')
@section('page_title', 'Rapor')
@section('page_subtitle', 'Dashboard > Rapor > Catatan & Sikap')

@section('content')
@php
    $roleName = auth()->user()->role ?? 'Wali Kelas';
    $isWali = auth()->user()->isWaliKelas();
    $prefix = $roleName === 'Operator' ? 'operator' : 'wali';
@endphp

<!-- FILTER BAR -->
<form id="filterForm" method="GET" action="{{ route($prefix . '.catatan') }}" class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 mb-6 font-heading">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
            <!-- Tahun Ajaran -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Tahun Pelajaran</label>
                <select name="tahun_ajaran" onchange="document.getElementById('filterForm').submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($tahunAjaranList ?? \App\Models\Semester::getTahunAjaranList() as $ta)
                        <option value="{{ $ta }}" {{ ($selectedTahunAjaran ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Semester -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                <select name="semester_id" onchange="document.getElementById('filterForm').submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($semesterList as $sm)
                        <option value="{{ $sm->id_semester }}" {{ $selectedSemesterId == $sm->id_semester ? 'selected' : '' }}>{{ $sm->nama_semester }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Kelas -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                    Kelas
                    @if($isWali)
                        <span class="text-[10px] text-edu-blue font-normal"><i class="fa-solid fa-lock text-[9px] mr-0.5"></i>Binaan</span>
                    @endif
                </label>
                <select name="kelas_id" onchange="document.getElementById('filterForm').submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search -->
            <div class="pt-4 flex-1 min-w-[240px]">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-edu-orange text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama atau NIS siswa..." class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="pt-4 flex items-center space-x-3">
            <button type="button" onclick="isiTemplateDefault()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 px-4 rounded-xl border border-gray-300 flex items-center space-x-2 transition cursor-pointer" title="Isi otomatis kolom catatan yang masih kosong dengan rekomendasi standar">
                <i class="fa-solid fa-wand-magic-sparkles text-edu-orange"></i>
                <span>Template Otomatis</span>
            </button>
            <button type="submit" form="formCatatan" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-6 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan Semua</span>
            </button>
        </div>
    </div>
</form>

<form id="formCatatan" action="{{ route($prefix . '.catatan.store') }}" method="POST">
    @csrf
    <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
    <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">

    <!-- EXCEL TABLE CONTAINER -->
    <div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto max-h-[640px]">
            <table class="w-full excel-table border-collapse text-xs text-left">
                <thead>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                        <th class="p-3 w-12 border border-black">No.</th>
                        <th class="p-3 w-28 border border-black">NIS / NISN</th>
                        <th class="p-3 min-w-[200px] border border-black">NAMA SISWA</th>
                        <th class="p-3 w-14 border border-black">L/P</th>
                        <th class="p-3 min-w-[220px] border border-black">SIKAP SPIRITUAL</th>
                        <th class="p-3 min-w-[220px] border border-black">SIKAP SOSIAL</th>
                        <th class="p-3 min-w-[260px] border border-black">CATATAN WALI KELAS</th>
                        <th class="p-3 w-44 border border-black">
                            KEPUTUSAN KENAIKAN
                            @if($selectedSemesterId != 2)
                                <span class="block text-[9px] font-normal lowercase">(berlaku smt genap)</span>
                            @endif
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black bg-white font-body">
                    @forelse($siswaList as $idx => $s)
                        @php
                            $c = $catatanMap->get($s->id_siswa);
                            $spiritual = $c?->sikap_spiritual ?? '';
                            $sosial    = $c?->sikap_sosial ?? '';
                            $waliNote  = $c?->catatan_wali ?? '';
                            $kenaikan  = $c?->status_kenaikan ?? 'Naik';
                        @endphp
                        <tr class="hover:bg-amber-50/30 transition">
                            <td class="p-2 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2 text-center border border-black font-mono">
                                <div class="font-bold">{{ $s->nis }}</div>
                                @if($s->nisn)
                                    <div class="text-[10px] text-gray-500">{{ $s->nisn }}</div>
                                @endif
                            </td>
                            <td class="p-2 font-bold text-gray-900 border border-black">
                                {{ strtoupper($s->nama) }}
                            </td>
                            <td class="p-2 text-center border border-black font-semibold">{{ $s->jenis_kelamin }}</td>

                            <!-- Sikap Spiritual -->
                            <td class="p-1.5 border border-black">
                                <textarea name="catatan[{{ $s->id_siswa }}][sikap_spiritual]" rows="2" placeholder="Taat beribadah dan selalu bersyukur..." class="inp-spiritual w-full px-2 py-1 bg-white border border-gray-300 rounded text-xs focus:ring-1 focus:ring-edu-orange focus:outline-none resize-none">{{ $spiritual }}</textarea>
                            </td>

                            <!-- Sikap Sosial -->
                            <td class="p-1.5 border border-black">
                                <textarea name="catatan[{{ $s->id_siswa }}][sikap_sosial]" rows="2" placeholder="Santun, disiplin, toleran, dan peduli sesama..." class="inp-sosial w-full px-2 py-1 bg-white border border-gray-300 rounded text-xs focus:ring-1 focus:ring-edu-orange focus:outline-none resize-none">{{ $sosial }}</textarea>
                            </td>

                            <!-- Catatan Wali Kelas -->
                            <td class="p-1.5 border border-black">
                                <textarea name="catatan[{{ $s->id_siswa }}][catatan_wali]" rows="2" placeholder="Tingkatkan terus prestasi belajar dan pertahankan kedisiplinan..." class="inp-wali w-full px-2 py-1 bg-white border border-gray-300 rounded text-xs focus:ring-1 focus:ring-edu-orange focus:outline-none resize-none">{{ $waliNote }}</textarea>
                            </td>

                            <!-- Keputusan Kenaikan Kelas -->
                            <td class="p-1.5 text-center border border-black">
                                @if($selectedSemesterId == 2)
                                    <select name="catatan[{{ $s->id_siswa }}][status_kenaikan]" class="w-full bg-white border border-gray-300 rounded px-2 py-1.5 text-xs font-bold text-gray-800 focus:ring-1 focus:ring-edu-orange focus:outline-none">
                                        <option value="Naik" {{ $kenaikan === 'Naik' ? 'selected' : '' }}>Naik ke Kelas Selanjutnya</option>
                                        <option value="Tinggal" {{ $kenaikan === 'Tinggal' ? 'selected' : '' }}>Tinggal di Kelas</option>
                                    </select>
                                @else
                                    <input type="hidden" name="catatan[{{ $s->id_siswa }}][status_kenaikan]" value="{{ $kenaikan }}">
                                    <span class="inline-block px-2 py-1 text-[11px] font-semibold bg-gray-100 text-gray-600 rounded">
                                        Lanjut Smt Genap
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="p-8 text-center text-gray-500 border border-black">Tidak ada data siswa pada kelas ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
    function isiTemplateDefault() {
        if (!confirm('Apakah Anda ingin mengisi otomatis catatan yang masih kosong dengan rekomendasi positif standar?')) {
            return;
        }

        const spiritualText = 'Selalu taat menjalankan ibadah, berdoa sebelum beraktivitas, dan bersyukur.';
        const sosialText = 'Menunjukkan sikap santun, bergotong royong, disiplin, dan mampu bekerja sama dengan baik.';
        const waliText = 'Tingkatkan terus semangat belajar dan pertahankan kedisiplinan serta akhlak mulia dalam segala aktivitas sekolah.';

        document.querySelectorAll('.inp-spiritual').forEach(el => {
            if (!el.value.trim()) el.value = spiritualText;
        });
        document.querySelectorAll('.inp-sosial').forEach(el => {
            if (!el.value.trim()) el.value = sosialText;
        });
        document.querySelectorAll('.inp-wali').forEach(el => {
            if (!el.value.trim()) el.value = waliText;
        });
    }
</script>
@endsection
