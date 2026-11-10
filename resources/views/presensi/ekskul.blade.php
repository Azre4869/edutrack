@extends('layouts.app')

@section('title', 'Ekstrakurikuler')
@section('page_title', 'Ekstrakurikuler')
@section('page_subtitle', 'Dashboard > Ekstrakurikuler')

@section('content')
@php
    $rolePrefix = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'wali';
    $isWali = auth()->user()?->isWaliKelas() ?? false;
    $isOperator = (auth()->user()->role ?? '') === 'Operator';
    $isReadOnly = $isOperator;
@endphp

<!-- FILTER BAR (FORM MURNI GET - TIDAK MENYIMPAN DATA SAAT GANTI FILTER) -->
<form id="filterForm" method="GET" action="{{ route($rolePrefix . '.presensi-ekskul') }}" class="bg-white rounded-3xl p-4 sm:p-5 shadow-lg border border-gray-100 mb-6 font-heading">
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:flex lg:flex-wrap items-end gap-3 sm:gap-4 flex-1">
            <!-- Tahun Ajaran -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Tahun Ajaran</label>
                <select name="tahun_ajaran" onchange="document.getElementById('filterForm').submit()" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($tahunAjaranList ?? \App\Models\Semester::getTahunAjaranList() as $ta)
                        <option value="{{ $ta }}" {{ ($selectedTahunAjaran ?? '') === $ta ? 'selected' : '' }}>{{ $ta }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Semester -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                <select name="semester_id" onchange="document.getElementById('filterForm').submit()" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
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
                <select name="kelas_id" onchange="document.getElementById('filterForm').submit()" class="w-full bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Search -->
            <div class="col-span-2 sm:col-span-3 lg:flex-1 min-w-[200px]">
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Cari Siswa</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-edu-orange text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau ekstrakulikuler......" class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                </div>
            </div>
        </div>

        <!-- Simpan Semua / Mode Read-Only -->
        <div class="w-full lg:w-auto flex justify-end">
            @if(!$isReadOnly)
            <button type="submit" form="formPresensiEkskul" class="w-full sm:w-auto justify-center bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-6 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan Semua</span>
            </button>
            @else
            <span class="w-full sm:w-auto justify-center inline-flex items-center space-x-1.5 px-4 py-2.5 bg-amber-50 border border-amber-300 text-amber-800 text-xs font-bold rounded-xl shadow-sm">
                <i class="fa-solid fa-lock text-amber-600"></i>
                <span>Mode Lihat Saja (Read-Only)</span>
            </span>
            @endif
        </div>
    </div>
</form>

<!-- FORM SIMPAN NILAI EKSTRAKURIKULER (POST) -->
<form id="formPresensiEkskul" action="{{ route($rolePrefix . '.presensi-ekskul.store') }}" method="POST">
    @csrf
    <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
    <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">

    <!-- Petunjuk Geser Tabel Mobile -->
    <div class="block lg:hidden mb-2 text-[11px] text-blue-100 font-medium text-right">
        <i class="fa-solid fa-arrows-left-right mr-1"></i>Geser tabel untuk melihat seluruh ekskul &rarr;
    </div>

    <!-- EXCEL TABLE -->
    <div class="bg-white rounded-3xl p-3 sm:p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
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
                            $hasCustom1 = $ne1 && !empty(trim($ne1->deskripsi ?? ''));
                            $hasCustom2 = $ne2 && !empty(trim($ne2->deskripsi ?? ''));
                            $hasCustom3 = $ne3 && !empty(trim($ne3->deskripsi ?? ''));
                            $waliNote = isset($catatanMap) ? ($catatanMap->get($s->id_siswa)->catatan_wali ?? '') : '';
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-2.5 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2.5 text-center border border-black font-medium">{{ $s->nis }}</td>
                            <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $s->nama }}</td>
                            <td class="p-2.5 text-center border border-black">{{ $s->jenis_kelamin }}</td>

                            <!-- Ekskul 1 -->
                            <td class="p-1 border border-black">
                                <select id="ekskul_select_{{ $s->id_siswa }}_0" name="ekskul[{{ $s->id_siswa }}][0][id_ekskul]" {{ $isReadOnly ? 'disabled' : '' }} class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs {{ $isReadOnly ? 'cursor-not-allowed bg-gray-50/70' : '' }}">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ ($ne1 && $ne1->id_ekskul == $e->id_ekskul) ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <select id="predikat_select_{{ $s->id_siswa }}_0" name="ekskul[{{ $s->id_siswa }}][0][predikat]" {{ $isReadOnly ? 'disabled' : '' }} class="bg-blue-50 text-edu-blue font-bold rounded px-1.5 py-1 text-xs border border-blue-200 {{ $isReadOnly ? 'cursor-not-allowed' : '' }}">
                                    <option value="Sangat Baik" {{ ($ne1 && $ne1->predikat == 'Sangat Baik') ? 'selected' : '' }}>A</option>
                                    <option value="Baik" {{ ($ne1 && $ne1->predikat == 'Baik') || !$ne1 ? 'selected' : '' }}>B</option>
                                    <option value="Cukup" {{ ($ne1 && $ne1->predikat == 'Cukup') ? 'selected' : '' }}>C</option>
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <input type="hidden" id="deskripsi_{{ $s->id_siswa }}_0" name="ekskul[{{ $s->id_siswa }}][0][deskripsi]" value="{{ $ne1->deskripsi ?? '' }}">
                                <button type="button" id="btn_deskripsi_{{ $s->id_siswa }}_0" onclick="bukaModalDeskripsi({{ $s->id_siswa }}, 0, '{{ addslashes($s->nama) }}', 'Ekstrakurikuler 1')" title="{{ $hasCustom1 ? 'Deskripsi: ' . $ne1->deskripsi : ($isReadOnly ? 'Lihat Deskripsi' : 'Tulis Deskripsi Capaian') }}" class="w-6 h-6 rounded {{ $hasCustom1 ? 'bg-emerald-600 text-white' : 'bg-edu-blue text-white' }} inline-flex items-center justify-center text-[10px] hover:opacity-80 transition cursor-pointer">
                                    <i class="fa-solid {{ $hasCustom1 ? 'fa-check' : ($isReadOnly ? 'fa-eye' : 'fa-pen') }}"></i>
                                </button>
                            </td>

                            <!-- Ekskul 2 -->
                            <td class="p-1 border border-black">
                                <select id="ekskul_select_{{ $s->id_siswa }}_1" name="ekskul[{{ $s->id_siswa }}][1][id_ekskul]" {{ $isReadOnly ? 'disabled' : '' }} class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs {{ $isReadOnly ? 'cursor-not-allowed bg-gray-50/70' : '' }}">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ ($ne2 && $ne2->id_ekskul == $e->id_ekskul) ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <select id="predikat_select_{{ $s->id_siswa }}_1" name="ekskul[{{ $s->id_siswa }}][1][predikat]" {{ $isReadOnly ? 'disabled' : '' }} class="bg-blue-50 text-edu-blue font-bold rounded px-1.5 py-1 text-xs border border-blue-200 {{ $isReadOnly ? 'cursor-not-allowed' : '' }}">
                                    <option value="Sangat Baik" {{ ($ne2 && $ne2->predikat == 'Sangat Baik') ? 'selected' : '' }}>A</option>
                                    <option value="Baik" {{ ($ne2 && $ne2->predikat == 'Baik') || !$ne2 ? 'selected' : '' }}>B</option>
                                    <option value="Cukup" {{ ($ne2 && $ne2->predikat == 'Cukup') ? 'selected' : '' }}>C</option>
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <input type="hidden" id="deskripsi_{{ $s->id_siswa }}_1" name="ekskul[{{ $s->id_siswa }}][1][deskripsi]" value="{{ $ne2->deskripsi ?? '' }}">
                                <button type="button" id="btn_deskripsi_{{ $s->id_siswa }}_1" onclick="bukaModalDeskripsi({{ $s->id_siswa }}, 1, '{{ addslashes($s->nama) }}', 'Ekstrakurikuler 2')" title="{{ $hasCustom2 ? 'Deskripsi: ' . $ne2->deskripsi : ($isReadOnly ? 'Lihat Deskripsi' : 'Tulis Deskripsi Capaian') }}" class="w-6 h-6 rounded {{ $hasCustom2 ? 'bg-emerald-600 text-white' : 'bg-edu-blue text-white' }} inline-flex items-center justify-center text-[10px] hover:opacity-80 transition cursor-pointer">
                                    <i class="fa-solid {{ $hasCustom2 ? 'fa-check' : ($isReadOnly ? 'fa-eye' : 'fa-pen') }}"></i>
                                </button>
                            </td>

                            <!-- Ekskul 3 -->
                            <td class="p-1 border border-black">
                                <select id="ekskul_select_{{ $s->id_siswa }}_2" name="ekskul[{{ $s->id_siswa }}][2][id_ekskul]" {{ $isReadOnly ? 'disabled' : '' }} class="w-full bg-white border border-gray-300 rounded px-1.5 py-1 text-xs {{ $isReadOnly ? 'cursor-not-allowed bg-gray-50/70' : '' }}">
                                    <option value="">-- Pilih --</option>
                                    @foreach($ekskulList as $e)
                                        <option value="{{ $e->id_ekskul }}" {{ ($ne3 && $ne3->id_ekskul == $e->id_ekskul) ? 'selected' : '' }}>{{ $e->nama_ekskul }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <select id="predikat_select_{{ $s->id_siswa }}_2" name="ekskul[{{ $s->id_siswa }}][2][predikat]" {{ $isReadOnly ? 'disabled' : '' }} class="bg-blue-50 text-edu-blue font-bold rounded px-1.5 py-1 text-xs border border-blue-200 {{ $isReadOnly ? 'cursor-not-allowed' : '' }}">
                                    <option value="Sangat Baik" {{ ($ne3 && $ne3->predikat == 'Sangat Baik') ? 'selected' : '' }}>A</option>
                                    <option value="Baik" {{ ($ne3 && $ne3->predikat == 'Baik') || !$ne3 ? 'selected' : '' }}>B</option>
                                    <option value="Cukup" {{ ($ne3 && $ne3->predikat == 'Cukup') ? 'selected' : '' }}>C</option>
                                </select>
                            </td>
                            <td class="p-1 text-center border border-black">
                                <input type="hidden" id="deskripsi_{{ $s->id_siswa }}_2" name="ekskul[{{ $s->id_siswa }}][2][deskripsi]" value="{{ $ne3->deskripsi ?? '' }}">
                                <button type="button" id="btn_deskripsi_{{ $s->id_siswa }}_2" onclick="bukaModalDeskripsi({{ $s->id_siswa }}, 2, '{{ addslashes($s->nama) }}', 'Ekstrakurikuler 3')" title="{{ $hasCustom3 ? 'Deskripsi: ' . $ne3->deskripsi : ($isReadOnly ? 'Lihat Deskripsi' : 'Tulis Deskripsi Capaian') }}" class="w-6 h-6 rounded {{ $hasCustom3 ? 'bg-emerald-600 text-white' : 'bg-edu-blue text-white' }} inline-flex items-center justify-center text-[10px] hover:opacity-80 transition cursor-pointer">
                                    <i class="fa-solid {{ $hasCustom3 ? 'fa-check' : ($isReadOnly ? 'fa-eye' : 'fa-pen') }}"></i>
                                </button>
                            </td>

                            <!-- Keterangan / Catatan Siswa -->
                            <td class="p-1.5 border border-black">
                                <input type="text" name="catatan[{{ $s->id_siswa }}]" value="{{ $waliNote }}" placeholder="{{ $isReadOnly ? '-' : 'Catatan untuk siswa...' }}" {{ $isReadOnly ? 'readonly disabled' : '' }} class="w-full px-2 py-1 bg-white text-xs border border-gray-300 rounded focus:ring-1 focus:ring-edu-orange focus:outline-none {{ $isReadOnly ? 'cursor-not-allowed bg-gray-50/70' : '' }}">
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

<!-- MODAL EDIT DESKRIPSI EKSKUL -->
<div id="modalDeskripsiEkskul" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4">
    <div class="bg-white rounded-3xl p-5 sm:p-6 max-w-lg w-full shadow-2xl border border-gray-100 font-heading max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
                <h3 class="text-base font-black text-gray-900" id="modalEkskulTitle">Deskripsi Kegiatan Ekstrakurikuler</h3>
                <p class="text-xs text-edu-blue font-bold" id="modalEkskulSiswa">Nama Siswa</p>
            </div>
            <button type="button" onclick="tutupModalDeskripsi()" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 space-y-3">
            <!-- Pilihan Kegiatan & Predikat -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Kegiatan Ekstrakurikuler *</label>
                    <select id="modalEkskulSelect" {{ $isReadOnly ? 'disabled' : '' }} class="w-full p-2 border border-gray-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none {{ $isReadOnly ? 'bg-gray-50/70 text-gray-500 cursor-not-allowed' : '' }}">
                        <option value="">-- Pilih Kegiatan --</option>
                        @foreach($ekskulList as $e)
                            <option value="{{ $e->id_ekskul }}">{{ $e->nama_ekskul }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nilai / Predikat *</label>
                    <select id="modalPredikatSelect" {{ $isReadOnly ? 'disabled' : '' }} class="w-full p-2 border border-gray-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none {{ $isReadOnly ? 'bg-gray-50/70 text-gray-500 cursor-not-allowed' : '' }}">
                        <option value="Sangat Baik">A - Sangat Baik</option>
                        <option value="Baik" selected>B - Baik</option>
                        <option value="Cukup">C - Cukup</option>
                    </select>
                </div>
            </div>

            <!-- Textarea Deskripsi -->
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Deskripsi / Capaian Ekstrakurikuler:</label>
                <textarea id="modalDeskripsiText" rows="3" {{ $isReadOnly ? 'readonly' : '' }} class="w-full p-3 border border-gray-300 rounded-xl text-xs font-body focus:ring-2 focus:ring-edu-orange focus:outline-none {{ $isReadOnly ? 'bg-gray-50/70 text-gray-700 cursor-not-allowed' : '' }}" placeholder="Tuliskan catatan capaian siswa pada kegiatan ini..."></textarea>
            </div>

            @if(!$isReadOnly)
            <!-- Rekomendasi Cepat -->
            <div>
                <p class="text-[11px] font-bold text-gray-500 mb-1.5">Pilihan Cepat:</p>
                <div class="flex flex-col gap-1.5">
                    <button type="button" onclick="setQuickDeskripsi('Sangat aktif, berdisiplin tinggi, dan menunjukkan antusiasme luar biasa dalam setiap kegiatan.')" class="text-left text-[11px] bg-blue-50/70 hover:bg-blue-100/80 text-blue-900 px-3 py-1.5 rounded-lg border border-blue-200 transition cursor-pointer">
                        ✓ Sangat aktif, berdisiplin tinggi, dan antusias.
                    </button>
                    <button type="button" onclick="setQuickDeskripsi('Aktif mengikuti kegiatan secara konsisten dan mampu bekerja sama dengan baik.')" class="text-left text-[11px] bg-blue-50/70 hover:bg-blue-100/80 text-blue-900 px-3 py-1.5 rounded-lg border border-blue-200 transition cursor-pointer">
                        ✓ Aktif konsisten dan mampu bekerja sama dengan baik.
                    </button>
                    <button type="button" onclick="setQuickDeskripsi('Mengikuti kegiatan dengan cukup baik, perlu lebih ditingkatkan kedisiplinan dan keaktifannya.')" class="text-left text-[11px] bg-blue-50/70 hover:bg-blue-100/80 text-blue-900 px-3 py-1.5 rounded-lg border border-blue-200 transition cursor-pointer">
                        ✓ Cukup baik, perlu ditingkatkan kedisiplinan.
                    </button>
                </div>
            </div>
            @endif

            <div class="pt-3 flex items-center justify-between border-t border-gray-100">
                @if(!$isReadOnly)
                <button type="button" onclick="setQuickDeskripsi('')" class="text-[11px] text-red-500 hover:text-red-700 font-semibold cursor-pointer">
                    <i class="fa-solid fa-trash-can mr-1"></i>Kosongkan
                </button>
                @else
                <div></div>
                @endif
                <div class="flex space-x-2">
                    <button type="button" onclick="tutupModalDeskripsi()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition cursor-pointer">
                        {{ $isReadOnly ? 'Tutup' : 'Batal' }}
                    </button>
                    @if(!$isReadOnly)
                    <button type="button" id="btnSimpanModalDeskripsi" onclick="simpanModalDeskripsiAjax()" class="px-5 py-2 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer flex items-center">
                        <span>Simpan Deskripsi</span>
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentSiswaId = null;
    let currentSlotIdx = null;
    let activeInputId = null;
    let activeButtonId = null;

    function bukaModalDeskripsi(siswaId, slotIdx, siswaNama, slotTitle) {
        currentSiswaId = siswaId;
        currentSlotIdx = slotIdx;
        activeInputId = `deskripsi_${siswaId}_${slotIdx}`;
        activeButtonId = `btn_deskripsi_${siswaId}_${slotIdx}`;

        const ekskulSelect = document.getElementById(`ekskul_select_${siswaId}_${slotIdx}`);
        const predikatSelect = document.getElementById(`predikat_select_${siswaId}_${slotIdx}`);
        const inputEl = document.getElementById(activeInputId);

        document.getElementById('modalEkskulTitle').textContent = `Deskripsi ${slotTitle}`;
        document.getElementById('modalEkskulSiswa').textContent = `${siswaNama}`;

        document.getElementById('modalEkskulSelect').value = ekskulSelect ? ekskulSelect.value : '';
        document.getElementById('modalPredikatSelect').value = predikatSelect ? predikatSelect.value : 'Baik';
        document.getElementById('modalDeskripsiText').value = inputEl ? inputEl.value : '';

        document.getElementById('modalDeskripsiEkskul').classList.remove('hidden');
    }

    function tutupModalDeskripsi() {
        document.getElementById('modalDeskripsiEkskul').classList.add('hidden');
        currentSiswaId = null;
        currentSlotIdx = null;
        activeInputId = null;
        activeButtonId = null;
    }

    function setQuickDeskripsi(text) {
        document.getElementById('modalDeskripsiText').value = text;
    }

    function simpanModalDeskripsiAjax() {
        const idEkskul = document.getElementById('modalEkskulSelect').value;
        if (!idEkskul) {
            alert('Silakan pilih kegiatan ekstrakurikuler terlebih dahulu.');
            document.getElementById('modalEkskulSelect').focus();
            return;
        }

        const predikat = document.getElementById('modalPredikatSelect').value;
        const deskripsi = document.getElementById('modalDeskripsiText').value.trim();
        const saveBtn = document.getElementById('btnSimpanModalDeskripsi');

        saveBtn.disabled = true;
        saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i>Menyimpan...';

        fetch('{{ route($rolePrefix . ".presensi-ekskul.deskripsi") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                siswa_id: currentSiswaId,
                id_ekskul: idEkskul,
                semester_id: '{{ $selectedSemesterId }}',
                predikat: predikat,
                deskripsi: deskripsi
            })
        })
        .then(res => res.json())
        .then(data => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span>Simpan Deskripsi</span>';

            if (data.status === 'success') {
                // Update tabel di baris siswa secara langsung
                const ekskulSelect = document.getElementById(`ekskul_select_${currentSiswaId}_${currentSlotIdx}`);
                const predikatSelect = document.getElementById(`predikat_select_${currentSiswaId}_${currentSlotIdx}`);
                const inputEl = document.getElementById(activeInputId);
                const btnEl = document.getElementById(activeButtonId);

                if (ekskulSelect) ekskulSelect.value = data.id_ekskul;
                if (predikatSelect) predikatSelect.value = data.predikat;
                if (inputEl) inputEl.value = data.deskripsi;

                if (btnEl) {
                    btnEl.className = 'w-6 h-6 rounded bg-emerald-600 text-white inline-flex items-center justify-center text-[10px] hover:opacity-80 transition cursor-pointer';
                    btnEl.innerHTML = '<i class="fa-solid fa-check"></i>';
                    btnEl.title = 'Deskripsi: ' + data.deskripsi;
                }

                tutupModalDeskripsi();
                tampilkanToast(data.message || 'Deskripsi ekstrakurikuler berhasil disimpan!');
            } else {
                alert(data.message || 'Gagal menyimpan deskripsi.');
            }
        })
        .catch(err => {
            saveBtn.disabled = false;
            saveBtn.innerHTML = '<span>Simpan Deskripsi</span>';
            console.error(err);
            alert('Terjadi kesalahan koneksi saat menyimpan.');
        });
    }

    function tampilkanToast(msg) {
        let toast = document.getElementById('floatingToastEkskul');
        if (!toast) {
            toast = document.createElement('div');
            toast.id = 'floatingToastEkskul';
            toast.className = 'fixed bottom-5 right-5 z-50 bg-emerald-600 text-white text-xs font-bold py-3 px-5 rounded-2xl shadow-2xl flex items-center space-x-2 transition-all duration-300 font-heading';
            document.body.appendChild(toast);
        }
        toast.innerHTML = `<i class="fa-solid fa-circle-check text-base"></i><span>${msg}</span>`;
        toast.classList.remove('hidden');
        toast.style.opacity = '1';
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.classList.add('hidden'), 300);
        }, 3000);
    }
</script>
@endsection
