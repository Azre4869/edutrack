@extends('layouts.app')

@section('title', 'Ekstrakurikuler')
@section('page_title', 'Ekstrakurikuler')
@section('page_subtitle', 'Dashboard > Ekstrakurikuler')

@section('content')
@php
    $rolePrefix = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'wali';
    $isWali = auth()->user()->isWaliKelas();
@endphp

@if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-300 text-emerald-800 px-5 py-3 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-sm font-heading">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif

<!-- FILTER BAR (FORM MURNI GET - TIDAK MENYIMPAN DATA SAAT GANTI FILTER) -->
<form id="filterForm" method="GET" action="{{ route($rolePrefix . '.presensi-ekskul') }}" class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 mb-6 font-heading">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4">
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
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau ekstrakulikuler......" class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                </div>
            </div>
        </div>

        <!-- Simpan Semua -->
        <div class="pt-4">
            <button type="submit" form="formPresensiEkskul" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-6 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan Semua</span>
            </button>
        </div>
    </div>
</form>

<!-- FORM SIMPAN NILAI EKSTRAKURIKULER (POST) -->
<form id="formPresensiEkskul" action="{{ route($rolePrefix . '.presensi-ekskul.store') }}" method="POST">
    @csrf
    <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
    <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">

    <!-- EXCEL TABLE -->
    <div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
        <div class="overflow-x-auto w-full max-h-[640px] pb-2">
            <table class="w-full min-w-[1250px] excel-table border-collapse text-xs text-left">
                <thead>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                        <th rowspan="2" class="p-3 w-12 border border-black">No.</th>
                        <th rowspan="2" class="p-3 w-28 border border-black">NIS/NISN</th>
                        <th rowspan="2" class="p-3 min-w-[200px] border border-black">NAMA</th>
                        <th rowspan="2" class="p-3 w-12 border border-black">L/P</th>
                        <th colspan="3" class="p-2 border border-black">Ekstrakulikuler 1</th>
                        <th colspan="3" class="p-2 border border-black">Ekstrakulikuler 2</th>
                        <th colspan="3" class="p-2 border border-black">Ekstrakulikuler 3</th>
                        <th rowspan="2" class="p-3 min-w-[160px] border border-black">Keterangan</th>
                    </tr>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black text-[10px] uppercase">
                        <th class="p-2 min-w-[110px] border border-black">Kegiatan</th>
                        <th class="p-2 w-16 border border-black">Nilai</th>
                        <th class="p-2 w-14 border border-black">Deskripsi</th>
                        <th class="p-2 min-w-[110px] border border-black">Kegiatan</th>
                        <th class="p-2 w-16 border border-black">Nilai</th>
                        <th class="p-2 w-14 border border-black">Deskripsi</th>
                        <th class="p-2 min-w-[110px] border border-black">Kegiatan</th>
                        <th class="p-2 w-16 border border-black">Nilai</th>
                        <th class="p-2 w-14 border border-black">Deskripsi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black bg-white font-body">
                    @forelse($siswaList as $idx => $s)
                        @php
                            $userEkskul = $nilaiEkskul[$s->id_siswa] ?? collect();
                            $ne1 = $userEkskul->get(0);
                            $ne2 = $userEkskul->get(1);
                            $ne3 = $userEkskul->get(2);
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-2.5 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2.5 text-center border border-black font-medium">{{ $s->nis }}</td>
                            <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $s->nama }}</td>
                            <td class="p-2.5 text-center border border-black">{{ $s->jenis_kelamin }}</td>

                            <!-- Ekskul 1 -->
                            <td class="p-1 border border-black">
                                <select name="ekskul[{{ $s->id_siswa }}][0][id_ekskul]" class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ ($ne1 && $ne1->id_ekskul == $e->id_ekskul) || (!$ne1 && $loop->first && $idx == 0) ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <select name="ekskul[{{ $s->id_siswa }}][0][predikat]" class="bg-blue-50 text-edu-blue font-bold rounded px-1.5 py-1 text-xs border border-blue-200">
                                    <option value="Sangat Baik" {{ ($ne1 && $ne1->predikat == 'Sangat Baik') || (!$ne1) ? 'selected' : '' }}>A</option>
                                    <option value="Baik" {{ ($ne1 && $ne1->predikat == 'Baik') ? 'selected' : '' }}>B</option>
                                    <option value="Cukup" {{ ($ne1 && $ne1->predikat == 'Cukup') ? 'selected' : '' }}>C</option>
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <input type="hidden" name="ekskul[{{ $s->id_siswa }}][0][deskripsi]" value="{{ $ne1->deskripsi ?? 'Aktif dan disiplin dalam kegiatan.' }}">
                                <button type="button" onclick="alert('Deskripsi Ekskul 1: {{ $ne1->deskripsi ?? 'Aktif dan disiplin dalam kegiatan.' }}')" class="w-6 h-6 rounded bg-edu-blue text-white inline-flex items-center justify-center text-[10px] hover:bg-blue-700 transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </td>

                            <!-- Ekskul 2 -->
                            <td class="p-1 border border-black">
                                <select name="ekskul[{{ $s->id_siswa }}][1][id_ekskul]" class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ ($ne2 && $ne2->id_ekskul == $e->id_ekskul) || (!$ne2 && $idx == 0 && $e->id_ekskul == 3) ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <select name="ekskul[{{ $s->id_siswa }}][1][predikat]" class="bg-blue-50 text-edu-blue font-bold rounded px-1.5 py-1 text-xs border border-blue-200">
                                    <option value="Sangat Baik" {{ ($ne2 && $ne2->predikat == 'Sangat Baik') ? 'selected' : '' }}>A</option>
                                    <option value="Baik" {{ ($ne2 && $ne2->predikat == 'Baik') || (!$ne2) ? 'selected' : '' }}>B</option>
                                    <option value="Cukup" {{ ($ne2 && $ne2->predikat == 'Cukup') ? 'selected' : '' }}>C</option>
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <input type="hidden" name="ekskul[{{ $s->id_siswa }}][1][deskripsi]" value="{{ $ne2->deskripsi ?? 'Mengikuti kegiatan secara teratur.' }}">
                                <button type="button" onclick="alert('Deskripsi Ekskul 2: {{ $ne2->deskripsi ?? 'Mengikuti kegiatan secara teratur.' }}')" class="w-6 h-6 rounded bg-edu-blue text-white inline-flex items-center justify-center text-[10px] hover:bg-blue-700 transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </td>

                            <!-- Ekskul 3 -->
                            <td class="p-1 border border-black">
                                <select name="ekskul[{{ $s->id_siswa }}][2][id_ekskul]" class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ ($ne3 && $ne3->id_ekskul == $e->id_ekskul) || (!$ne3 && $idx == 0 && $e->id_ekskul == 2) ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <select name="ekskul[{{ $s->id_siswa }}][2][predikat]" class="bg-blue-50 text-edu-blue font-bold rounded px-1.5 py-1 text-xs border border-blue-200">
                                    <option value="Sangat Baik" {{ ($ne3 && $ne3->predikat == 'Sangat Baik') ? 'selected' : '' }}>A</option>
                                    <option value="Baik" {{ ($ne3 && $ne3->predikat == 'Baik') ? 'selected' : '' }}>B</option>
                                    <option value="Cukup" {{ ($ne3 && $ne3->predikat == 'Cukup') || (!$ne3) ? 'selected' : '' }}>C</option>
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <input type="hidden" name="ekskul[{{ $s->id_siswa }}][2][deskripsi]" value="{{ $ne3->deskripsi ?? 'Perlu ditingkatkan keaktifannya.' }}">
                                <button type="button" onclick="alert('Deskripsi Ekskul 3: {{ $ne3->deskripsi ?? 'Perlu ditingkatkan keaktifannya.' }}')" class="w-6 h-6 rounded bg-edu-blue text-white inline-flex items-center justify-center text-[10px] hover:bg-blue-700 transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </td>

                            <!-- Keterangan -->
                            <td class="p-1.5 border border-black">
                                <input type="text" placeholder="Catatan tambahan..." class="w-full px-2 py-1 bg-transparent text-xs border border-gray-200 rounded">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="p-8 text-center text-gray-500 border border-black">Tidak ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>
@endsection
