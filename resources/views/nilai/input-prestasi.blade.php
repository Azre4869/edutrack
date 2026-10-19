@extends('layouts.app')

@section('title', 'Input Prestasi Siswa')
@section('page_title', 'Nilai')
@section('page_subtitle', 'Dashboard > Nilai > Input Nilai > Input Prestasi')

@section('content')
<form action="{{ route('wali.prestasi.store') }}" method="POST">
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
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau prestasi siswa......" class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
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
    <div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto max-h-[640px]">
            <table class="w-full excel-table border-collapse text-xs text-left">
                <thead>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                        <th rowspan="2" class="p-3 w-12 border border-black">No.</th>
                        <th rowspan="2" class="p-3 w-28 border border-black">NIS/NISN</th>
                        <th rowspan="2" class="p-3 min-w-[200px] border border-black">NAMA</th>
                        <th rowspan="2" class="p-3 w-12 border border-black">L/P</th>
                        <th colspan="2" class="p-2 border border-black">Prestasi 1</th>
                        <th colspan="2" class="p-2 border border-black">Prestasi 2</th>
                        <th colspan="2" class="p-2 border border-black">Prestasi 3</th>
                        <th rowspan="2" class="p-3 min-w-[160px] border border-black">Catatan</th>
                    </tr>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black text-[10px] uppercase">
                        <th class="p-2 min-w-[130px] border border-black">Jenis Prestasi</th>
                        <th class="p-2 w-28 border border-black">Keterangan</th>
                        <th class="p-2 min-w-[130px] border border-black">Jenis Prestasi</th>
                        <th class="p-2 w-28 border border-black">Keterangan</th>
                        <th class="p-2 min-w-[130px] border border-black">Jenis Prestasi</th>
                        <th class="p-2 w-28 border border-black">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black bg-white font-body">
                    @forelse($siswaList as $idx => $s)
                        @php
                            $userPrestasi = $prestasiMap[$s->id_siswa] ?? collect();
                            $p1 = $userPrestasi->get(0);
                            $p2 = $userPrestasi->get(1);
                            $p3 = $userPrestasi->get(2);
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-2.5 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2.5 text-center border border-black font-medium">{{ $s->nis }}</td>
                            <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $s->nama }}</td>
                            <td class="p-2.5 text-center border border-black">{{ $s->jenis_kelamin }}</td>

                            <!-- Prestasi 1 -->
                            <td class="p-1 border border-black">
                                <input type="text" name="prestasi[{{ $s->id_siswa }}][nama_prestasi]" value="{{ $p1->nama_prestasi ?? ($idx === 0 ? 'OSN IPA' : '') }}" placeholder="Contoh: OSN IPA" class="w-full px-2 py-1 bg-transparent text-xs border border-gray-200 rounded">
                            </td>
                            <td class="p-1 border border-black text-center">
                                <select name="prestasi[{{ $s->id_siswa }}][keterangan]" class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="Provinsi" {{ ($p1 && $p1->keterangan == 'Provinsi') || $idx === 0 ? 'selected' : '' }}>Provinsi</option>
                                    <option value="Kabupaten">Kabupaten</option>
                                    <option value="Nasional">Nasional</option>
                                    <option value="Sekolah">Sekolah</option>
                                </select>
                            </td>

                            <!-- Prestasi 2 -->
                            <td class="p-1 border border-black">
                                <input type="text" value="{{ $p2->nama_prestasi ?? ($idx === 0 ? 'FLS2N Tari' : '') }}" placeholder="-" class="w-full px-2 py-1 bg-transparent text-xs border border-gray-200 rounded">
                            </td>
                            <td class="p-1 border border-black text-center">
                                <select class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="-">-</option>
                                    <option value="Kabupaten" {{ $idx === 0 ? 'selected' : '' }}>Kabupaten</option>
                                    <option value="Provinsi">Provinsi</option>
                                </select>
                            </td>

                            <!-- Prestasi 3 -->
                            <td class="p-1 border border-black">
                                <input type="text" value="{{ $p3->nama_prestasi ?? '' }}" placeholder="-" class="w-full px-2 py-1 bg-transparent text-xs border border-gray-200 rounded">
                            </td>
                            <td class="p-1 border border-black text-center">
                                <select class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs">
                                    <option value="-">-</option>
                                    <option value="Sekolah">Sekolah</option>
                                    <option value="Kabupaten">Kabupaten</option>
                                </select>
                            </td>

                            <!-- Catatan -->
                            <td class="p-1.5 border border-black">
                                <input type="text" placeholder="Catatan tambahan..." class="w-full px-2 py-1 bg-transparent text-xs border border-gray-200 rounded">
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="p-8 text-center text-gray-500 border border-black">Tidak ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>
@endsection
