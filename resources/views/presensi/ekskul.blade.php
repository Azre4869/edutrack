@extends('layouts.app')

@section('title', 'Presensi Ekstrakurikuler')
@section('page_title', 'Presensi')
@section('page_subtitle', 'Dashboard > Presensi > Ekstrakulikuler')

@section('content')
<form action="{{ route('wali.presensi-ekskul.store') }}" method="POST">
    @csrf

    <!-- FILTER BAR -->
    <div class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 mb-6 font-heading">
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
                    <select name="semester_id" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none">
                        @foreach($semesterList as $sm)
                            <option value="{{ $sm->id_semester }}" {{ $selectedSemesterId == $sm->id_semester ? 'selected' : '' }}>{{ $sm->nama_semester }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Kelas -->
                <div>
                    <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas</label>
                    <select name="kelas_id" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none">
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
                <button type="submit" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-6 rounded-xl shadow-md flex items-center space-x-2 transition">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Semua</span>
                </button>
            </div>
        </div>
    </div>

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
                                <select name="ekskul[{{ $s->id_siswa }}][id_ekskul]" class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ ($ne1 && $ne1->id_ekskul == $e->id_ekskul) || ($loop->first && $idx == 0) ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <select name="ekskul[{{ $s->id_siswa }}][predikat]" class="bg-blue-50 text-edu-blue font-bold rounded px-1.5 py-1 text-xs border border-blue-200">
                                    <option value="Sangat Baik" selected>A</option>
                                    <option value="Baik">B</option>
                                    <option value="Cukup">C</option>
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <button type="button" onclick="alert('Deskripsi Ekskul 1: {{ $ne1->deskripsi ?? 'Sangat aktif dalam kegiatan kepramukaan.' }}')" class="w-6 h-6 rounded bg-edu-blue text-white inline-flex items-center justify-center text-[10px]">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </td>

                            <!-- Ekskul 2 -->
                            <td class="p-1 border border-black">
                                <select class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ $idx == 0 && $e->id_ekskul == 3 ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <span class="px-2 py-0.5 rounded font-bold text-xs bg-amber-100 text-amber-700">B</span>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <button type="button" class="w-6 h-6 rounded bg-edu-blue text-white inline-flex items-center justify-center text-[10px]">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            </td>

                            <!-- Ekskul 3 -->
                            <td class="p-1 border border-black">
                                <select class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ $idx == 0 && $e->id_ekskul == 2 ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <span class="px-2 py-0.5 rounded font-bold text-xs bg-red-100 text-red-700">C</span>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <button type="button" class="w-6 h-6 rounded bg-edu-blue text-white inline-flex items-center justify-center text-[10px]">
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
