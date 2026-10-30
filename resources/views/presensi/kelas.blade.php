@extends('layouts.app')

@section('title', 'Presensi Siswa')
@section('page_title', 'Presensi')
@section('page_subtitle', 'Dashboard > Presensi Siswa')

@section('content')
@php
    $rolePrefix = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'wali';
@endphp

<div class="space-y-4">
    {{-- Alert Notifikasi Sukses Simpan (Hanya muncul jika tombol Simpan Semua ditekan) --}}
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-5 py-3 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-sm">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
    @endif

    <!-- FILTER & CONTROL BAR (MATCHING MOCKUP) -->
    <div class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 font-heading">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Form Filter Murni GET (TIDAK AKAN MENYIMPAN DATA, HANYA GANTI TAMPILAN) -->
            <form id="filterForm" method="GET" action="{{ route($rolePrefix . '.presensi-kelas') }}" class="flex flex-wrap items-center gap-4 flex-1">
                <!-- Tanggal -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Tanggal</label>
                    <div class="relative">
                        <input type="date" name="tanggal" value="{{ $selectedTanggal }}" onchange="document.getElementById('filterForm').submit()" class="appearance-none bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    </div>
                </div>

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
                        @if((auth()->user()->role ?? '') === 'Wali Kelas')
                            <span class="text-[10px] text-edu-blue font-normal"><i class="fa-solid fa-lock text-[9px] mr-0.5"></i>Binaan</span>
                        @endif
                    </label>
                    <select name="kelas_id" onchange="document.getElementById('filterForm').submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                        @foreach($kelasList as $k)
                            <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Input -->
                <div class="pt-4 flex-1 min-w-[200px]">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-edu-orange text-xs"></i>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau kelas siswa......" class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                    </div>
                </div>
            </form>

            <!-- Right: Simpan Semua Button (Tersambung ke savePresensiForm via atribut form) -->
            <div class="pt-4">
                <button type="submit" form="savePresensiForm" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-6 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Semua</span>
                </button>
            </div>
        </div>
    </div>

    <!-- FORM SIMPAN PRESENSI (Method POST: Hanya berjalan jika tombol Simpan Semua diklik) -->
    <form id="savePresensiForm" action="{{ route($rolePrefix . '.presensi-kelas.store') }}" method="POST">
        @csrf
        <input type="hidden" name="tanggal" value="{{ $selectedTanggal }}">
        <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
        <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">

    <!-- EXCEL SPREADSHEET TABLE -->
    <div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
        <div class="overflow-x-auto w-full max-h-[640px] pb-2">
            <table class="w-full min-w-[1000px] excel-table border-collapse text-xs text-left">
                <thead>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                        <th rowspan="2" class="p-3 w-12 border border-black">No.</th>
                        <th rowspan="2" class="p-3 w-28 border border-black">NIS/NISN</th>
                        <th rowspan="2" class="p-3 min-w-[220px] border border-black">NAMA</th>
                        <th rowspan="2" class="p-3 w-12 border border-black">L/P</th>
                        <th colspan="4" class="p-2 border border-black">Status Kehadiran</th>
                        <th rowspan="2" class="p-3 min-w-[260px] border border-black">Keterangan</th>
                    </tr>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black text-[10px] uppercase">
                        <th class="p-2 w-16 border border-black">Hadir</th>
                        <th class="p-2 w-16 border border-black">Sakit</th>
                        <th class="p-2 w-16 border border-black">Izin</th>
                        <th class="p-2 w-16 border border-black">Alpha</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black bg-white font-body">
                    @forelse($siswaList as $idx => $s)
                        @php
                            $currentStatus = $absensiMap[$s->id_siswa]->status ?? 'Hadir';
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-2.5 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2.5 text-center border border-black font-medium">{{ $s->nis }}</td>
                            <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $s->nama }}</td>
                            <td class="p-2.5 text-center border border-black">{{ $s->jenis_kelamin }}</td>

                            <!-- RADIO BUTTONS -->
                            <td class="p-2 text-center border border-black">
                                <label class="inline-flex items-center justify-center cursor-pointer">
                                    <input type="radio" name="status[{{ $s->id_siswa }}]" value="Hadir" {{ $currentStatus === 'Hadir' ? 'checked' : '' }} class="w-4 h-4 text-edu-blue focus:ring-edu-blue border-gray-400">
                                </label>
                            </td>
                            <td class="p-2 text-center border border-black">
                                <label class="inline-flex items-center justify-center cursor-pointer">
                                    <input type="radio" name="status[{{ $s->id_siswa }}]" value="Sakit" {{ $currentStatus === 'Sakit' ? 'checked' : '' }} class="w-4 h-4 text-amber-500 focus:ring-amber-500 border-gray-400">
                                </label>
                            </td>
                            <td class="p-2 text-center border border-black">
                                <label class="inline-flex items-center justify-center cursor-pointer">
                                    <input type="radio" name="status[{{ $s->id_siswa }}]" value="Izin" {{ $currentStatus === 'Izin' ? 'checked' : '' }} class="w-4 h-4 text-emerald-500 focus:ring-emerald-500 border-gray-400">
                                </label>
                            </td>
                            <td class="p-2 text-center border border-black">
                                <label class="inline-flex items-center justify-center cursor-pointer">
                                    <input type="radio" name="status[{{ $s->id_siswa }}]" value="Alpha" {{ $currentStatus === 'Alpha' ? 'checked' : '' }} class="w-4 h-4 text-red-500 focus:ring-red-500 border-gray-400">
                                </label>
                            </td>

                            <!-- KETERANGAN INPUT / SELECT -->
                            <td class="p-1.5 border border-black">
                                <input type="text" name="keterangan[{{ $s->id_siswa }}]" placeholder="Catatan kehadiran (opsional)..." class="w-full px-3 py-1 bg-transparent text-xs rounded-lg border border-gray-300 focus:outline-none focus:ring-1 focus:ring-edu-orange">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="p-8 text-center text-gray-500 border border-black">Tidak ada data siswa di kelas yang dipilih.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </form>
</div>
@endsection
