@extends('layouts.app')

@section('title', 'Input Nilai Siswa')
@section('page_title', 'Nilai')
@section('page_subtitle', 'Dashboard > Nilai > Input Nilai')

@section('content')
@php
    $rolePrefix = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'guru';
    $isGuru = auth()->user()->isGuruMapel();
    $isWali = auth()->user()->isWaliKelas();
@endphp

<!-- FILTER BAR (FORM MURNI GET - TIDAK MENYIMPAN DATA SAAT GANTI FILTER) -->
<form id="filterForm" method="GET" action="{{ route($rolePrefix . '.nilai') }}" class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 mb-6 font-heading">
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

            <!-- Mata Pelajaran -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">
                    Mata Pelajaran
                    @if($isGuru)
                        <span class="text-[10px] text-edu-orange font-normal"><i class="fa-solid fa-lock text-[9px] mr-0.5"></i>Ampuan</span>
                    @endif
                </label>
                <select name="mapel_id" onchange="document.getElementById('filterForm').submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @forelse($mapelList as $m)
                        <option value="{{ $m->id_mapel }}" {{ $selectedMapelId == $m->id_mapel ? 'selected' : '' }}>{{ $m->nama_mapel }}</option>
                    @empty
                        <option value="">Belum ada mapel diampu</option>
                    @endforelse
                </select>
            </div>

            <!-- Search -->
            <div class="pt-4 flex-1 min-w-[220px]">
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3 text-edu-orange text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau kelas siswa......" class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-300 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                </div>
            </div>
        </div>

        <!-- Simpan Semua Button (Mentarget form input nilai) -->
        <div class="pt-4">
            <button type="submit" form="formInputNilai" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2.5 px-6 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
                <i class="fa-solid fa-floppy-disk"></i>
                <span>Simpan Semua</span>
            </button>
        </div>
    </div>
</form>

<!-- FORM SIMPAN NILAI (POST) -->
<form id="formInputNilai" action="{{ route($rolePrefix . '.nilai.store') }}" method="POST">
    @csrf
    <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
    <input type="hidden" name="mapel_id" value="{{ $selectedMapelId }}">
    <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">

    <!-- EXCEL SPREADSHEET TABLE -->
    <div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
        <div class="overflow-x-auto w-full max-h-[640px] pb-2">
            <table class="w-full min-w-[1300px] excel-table border-collapse text-xs text-left">
                <thead>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                        <th rowspan="2" class="p-3 w-12 border border-black">No.</th>
                        <th rowspan="2" class="p-3 w-28 border border-black">NIS/NISN</th>
                        <th rowspan="2" class="p-3 min-w-[200px] border border-black">NAMA</th>
                        <th rowspan="2" class="p-3 w-12 border border-black">L/P</th>
                        <th colspan="8" class="p-2 border border-black">Nilai Sumatif (TP)</th>
                        <th rowspan="2" class="p-3 w-14 border border-black">NS</th>
                        <th rowspan="2" class="p-3 w-14 border border-black">SAS</th>
                        <th rowspan="2" class="p-3 w-16 border border-black">Nilai Akhir</th>
                        <th rowspan="2" class="p-3 min-w-[210px] border border-black">Aksi Capaian</th>
                        <th rowspan="2" class="p-3 w-28 border border-black">Status</th>
                    </tr>
                    <tr class="bg-edu-orange text-white text-center font-heading font-black text-[10px] uppercase">
                        <th class="p-1.5 w-10 border border-black">TP1</th>
                        <th class="p-1.5 w-10 border border-black">TP2</th>
                        <th class="p-1.5 w-10 border border-black">TP3</th>
                        <th class="p-1.5 w-10 border border-black">TP4</th>
                        <th class="p-1.5 w-10 border border-black">TP5</th>
                        <th class="p-1.5 w-10 border border-black">TP6</th>
                        <th class="p-1.5 w-10 border border-black">TP7</th>
                        <th class="p-1.5 w-10 border border-black">TP8</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-black bg-white font-body">
                    @forelse($siswaList as $idx => $s)
                        @php
                            $rowNilai = $nilaiMap[$s->id_siswa] ?? null;

                            // Nilai TP1-TP8 dari database
                            $tpValues = [];
                            $allTpFilled = true;
                            $hasAnyTp = false;
                            for($t = 1; $t <= 8; $t++) {
                                $col = 'tp' . $t;
                                $val = $rowNilai && !is_null($rowNilai->$col) ? (float)$rowNilai->$col : '';
                                $tpValues[$t] = $val;
                                if ($val !== '') {
                                    $hasAnyTp = true;
                                } else {
                                    $allTpFilled = false;
                                }
                            }
                            $nsValue = $rowNilai && !is_null($rowNilai->ns) ? (float)$rowNilai->ns : '';
                            $sasValue = $rowNilai && !is_null($rowNilai->sas) ? (float)$rowNilai->sas : '';

                            // Nilai Akhir hanya valid jika ada skor riil (bukan 0)
                            $hasScore = $hasAnyTp || ($sasValue !== '');
                            $naValue = '';
                            if ($hasScore && $rowNilai && !is_null($rowNilai->nilai_akhir) && $rowNilai->nilai_akhir > 0) {
                                $naValue = (float)$rowNilai->nilai_akhir;
                            }

                            // Capaian Kompetensi (filter teks dummy lama)
                            $dummyText = 'Menunjukkan penguasaan materi sesuai tujuan pembelajaran.';
                            $c1Text = $rowNilai && $rowNilai->capaian ? trim($rowNilai->capaian->deskripsi_1) : '';
                            $c2Text = $rowNilai && $rowNilai->capaian ? trim($rowNilai->capaian->deskripsi_2) : '';

                            $hasCapaian1 = !empty($c1Text);
                            $hasCapaian2 = !empty($c2Text) && ($c2Text !== $dummyText);

                            // Komponen kelengkapan:
                            $hasSas = ($sasValue !== '');
                            $hasNa = ($naValue !== '');

                            // Lengkap jika SEMUA TP (TP1 s/d TP8) terisi, SAS terisi, Nilai Akhir ada, Capaian 1 ada, Capaian 2 ada
                            $isComplete = $allTpFilled && $hasSas && $hasNa && $hasCapaian1 && $hasCapaian2;
                            $hasAny = $hasAnyTp || $hasSas || $hasNa || $hasCapaian1 || $hasCapaian2;
                            $isPartial = $hasAny && !$isComplete;
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition row-siswa" 
                            data-siswa-id="{{ $s->id_siswa }}"
                            data-has-capaian1="{{ $hasCapaian1 ? '1' : '0' }}"
                            data-has-capaian2="{{ $hasCapaian2 ? '1' : '0' }}">
                            <td class="p-2.5 text-center font-semibold border border-black">{{ $idx + 1 }}.</td>
                            <td class="p-2.5 text-center border border-black font-medium">{{ $s->nis }}</td>
                            <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $s->nama }}</td>
                            <td class="p-2.5 text-center border border-black">{{ $s->jenis_kelamin }}</td>

                            <!-- TP1 - TP8 inputs -->
                            @for($tp = 1; $tp <= 8; $tp++)
                                <td class="p-1 text-center border border-black">
                                    <input type="number" step="0.01" min="0" max="100" 
                                        name="nilai[{{ $s->id_siswa }}][tp{{ $tp }}]" 
                                        value="{{ $tpValues[$tp] }}" 
                                        placeholder="-"
                                        oninput="calculateRow(this)"
                                        onchange="calculateRow(this)"
                                        class="tp-input w-11 text-center py-1 bg-transparent rounded font-medium focus:bg-white focus:ring-1 focus:ring-edu-orange border border-gray-200">
                                </td>
                            @endfor

                            <!-- NS (Rata-rata dari semua TP terisi) -->
                            <td class="p-1 text-center font-bold text-gray-800 border border-black">
                                <input type="number" step="0.01" min="0" max="100" readonly
                                    name="nilai[{{ $s->id_siswa }}][ns]" 
                                    value="{{ $nsValue }}" 
                                    placeholder="-"
                                    class="ns-input w-12 text-center py-1 bg-gray-50/80 rounded font-bold text-gray-800 border-0 focus:outline-none">
                            </td>

                            <!-- SAS (Sumatif Akhir Semester) -->
                            <td class="p-1 text-center font-bold text-gray-800 border border-black">
                                <input type="number" step="0.01" min="0" max="100" 
                                    name="nilai[{{ $s->id_siswa }}][sas]" 
                                    value="{{ $sasValue }}" 
                                    placeholder="-"
                                    oninput="calculateRow(this)"
                                    onchange="calculateRow(this)"
                                    class="sas-input w-12 text-center py-1 bg-transparent rounded font-bold focus:bg-white focus:ring-1 focus:ring-edu-orange border border-gray-200">
                            </td>

                            <!-- Nilai Akhir (Rata-rata NS dan SAS) -->
                            <td class="p-1 text-center font-black text-edu-blue border border-black">
                                <input type="number" step="0.01" min="0" max="100" readonly
                                    name="nilai[{{ $s->id_siswa }}][nilai_akhir]" 
                                    value="{{ $naValue }}" 
                                    placeholder="-"
                                    class="na-input w-14 text-center py-1 bg-blue-50/80 rounded font-black text-edu-blue border-0 focus:outline-none">
                            </td>

                            <!-- Aksi Capaian Buttons -->
                            <td class="p-2 text-center border border-black">
                                <div class="flex items-center justify-center space-x-1.5 font-heading">
                                    <button type="button" onclick="openModalCapaian({{ $s->id_siswa }}, '{{ addslashes($s->nama) }}', 1)" class="btn-capaian-1 px-2.5 py-1 rounded text-[11px] font-bold transition {{ $hasCapaian1 ? 'bg-edu-blue text-white hover:bg-blue-700' : 'bg-red-500 text-white hover:bg-red-600' }}">
                                        Kompetensi 1
                                    </button>
                                    <button type="button" onclick="openModalCapaian({{ $s->id_siswa }}, '{{ addslashes($s->nama) }}', 2)" class="btn-capaian-2 px-2.5 py-1 rounded text-[11px] font-bold transition {{ $hasCapaian2 ? 'bg-edu-blue text-white hover:bg-blue-700' : 'bg-red-500 text-white hover:bg-red-600' }}">
                                        Kompetensi 2
                                    </button>
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="p-2 text-center border border-black status-cell">
                                @if($isComplete)
                                    <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-700">
                                        Lengkap
                                    </span>
                                @elseif($isPartial)
                                    <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full bg-amber-100 text-amber-700">
                                        Belum Lengkap
                                    </span>
                                @else
                                    <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full bg-red-100 text-red-700">
                                        Belum Diisi
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="p-8 text-center text-gray-500 border border-black">Tidak ada data siswa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</form>

<!-- MODAL INPUT CAPAIAN KOMPETENSI -->
<div id="modalCapaian" class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-edu-darkblue rounded-3xl p-8 max-w-xl w-full shadow-2xl border border-blue-400/30 text-white font-heading">
        <h3 id="modalCapaianTitle" class="text-2xl font-black text-center mb-6">Capaian Kompetensi 1</h3>
        <p id="modalCapaianSubtitle" class="text-xs text-blue-200 text-center mb-4"></p>

        <form id="formCapaian">
            @csrf
            <input type="hidden" id="capaian_siswa_id" name="siswa_id">
            <input type="hidden" id="capaian_mapel_id" name="mapel_id" value="{{ $selectedMapelId }}">
            <input type="hidden" id="capaian_semester_id" name="semester_id" value="{{ $selectedSemesterId }}">
            <input type="hidden" id="capaian_nomor" name="nomor" value="1">

            <div class="mb-6">
                <textarea id="capaian_deskripsi" name="deskripsi" rows="8" placeholder="Text" class="w-full p-4 rounded-2xl bg-white text-gray-900 font-body text-sm border-0 focus:ring-4 focus:ring-orange-400 focus:outline-none shadow-inner"></textarea>
            </div>

            <div class="flex items-center justify-between">
                <button type="button" onclick="closeModalCapaian()" class="px-8 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md transition">
                    Kembali
                </button>
                <button type="button" id="btnSubmitCapaian" onclick="saveCapaian()" class="px-8 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white font-bold rounded-xl shadow-md transition">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- TOAST NOTIFICATION -->
<div id="toastNotification" class="fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center space-x-3 font-heading text-xs">
    <i class="fa-solid fa-circle-check text-base"></i>
    <span id="toastMessage">Pesan berhasil</span>
</div>

<script>
    function showToast(message, isSuccess = true) {
        const toast = document.getElementById('toastNotification');
        const toastMsg = document.getElementById('toastMessage');
        if (!toast || !toastMsg) return;

        toastMsg.innerText = message;
        toast.className = `fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-0 opacity-100 pointer-events-auto ${isSuccess ? 'bg-emerald-600' : 'bg-red-600'} text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center space-x-3 font-heading text-xs`;

        setTimeout(() => {
            toast.className = 'fixed bottom-5 right-5 z-50 transform transition-all duration-300 translate-y-20 opacity-0 pointer-events-none bg-emerald-600 text-white px-5 py-3 rounded-2xl shadow-2xl flex items-center space-x-3 font-heading text-xs';
        }, 3000);
    }

    // Update status badge di baris siswa
    function updateRowStatus(row) {
        if (!row) return;
        const hasC1 = row.getAttribute('data-has-capaian1') === '1';
        const hasC2 = row.getAttribute('data-has-capaian2') === '1';

        // Cek TP1 - TP8: SEMUA harus terisi agar dianggap lengkap
        const tpInputs = row.querySelectorAll('.tp-input');
        let allTpFilled = true;
        let hasAnyTp = false;

        tpInputs.forEach(input => {
            const val = input.value.trim();
            if (val !== '' && !isNaN(parseFloat(val))) {
                hasAnyTp = true;
            } else {
                allTpFilled = false;
            }
        });

        const sasInput = row.querySelector('.sas-input');
        const hasSas = sasInput && sasInput.value.trim() !== '' && !isNaN(parseFloat(sasInput.value));

        const naInput = row.querySelector('.na-input');
        const hasNa = naInput && naInput.value.trim() !== '' && !isNaN(parseFloat(naInput.value)) && parseFloat(naInput.value) > 0;

        // Lengkap HANYA jika SEMUA TP (TP1 s/d TP8) terisi, SAS terisi, NA terhitung, Capaian 1 terisi, DAN Capaian 2 terisi
        const isComplete = allTpFilled && hasSas && hasNa && hasC1 && hasC2;
        const hasAny = hasAnyTp || hasSas || hasNa || hasC1 || hasC2;

        const statusCell = row.querySelector('.status-cell');
        if (!statusCell) return;

        if (isComplete) {
            statusCell.innerHTML = '<span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-700">Lengkap</span>';
        } else if (hasAny) {
            statusCell.innerHTML = '<span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full bg-amber-100 text-amber-700">Belum Lengkap</span>';
        } else {
            statusCell.innerHTML = '<span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full bg-red-100 text-red-700">Belum Diisi</span>';
        }
    }

    // Kalkulasi real-time per baris sesuai rumus Kurikulum Merdeka SMP
    function calculateRow(inputElement) {
        // Cegah pengetikan angka di luar batas 0–100 secara langsung di browser
        if (inputElement && inputElement.value !== '') {
            let typed = parseFloat(inputElement.value);
            if (!isNaN(typed)) {
                if (typed > 100) inputElement.value = 100;
                else if (typed < 0) inputElement.value = 0;
            }
        }

        const row = inputElement.closest('tr.row-siswa');
        if (!row) return;

        // 1. Ambil nilai TP1 - TP8 yang terisi
        const tpInputs = row.querySelectorAll('.tp-input');
        let tpSum = 0;
        let tpCount = 0;

        tpInputs.forEach(input => {
            let val = parseFloat(input.value);
            if (!isNaN(val) && input.value.trim() !== '') {
                val = Math.min(100, Math.max(0, val));
                tpSum += val;
                tpCount++;
            }
        });

        // 2. Hitung NS (Nilai Sumatif - rata-rata dari semua TP terisi)
        const nsInput = row.querySelector('.ns-input');
        let nsVal = null;
        if (tpCount > 0) {
            nsVal = Math.round((tpSum / tpCount) * 100) / 100;
            nsInput.value = nsVal;
        } else {
            nsInput.value = '';
        }

        // 3. Ambil SAS (Sumatif Akhir Semester)
        const sasInput = row.querySelector('.sas-input');
        const sasRaw = parseFloat(sasInput.value);
        const sasVal = (!isNaN(sasRaw) && sasInput.value.trim() !== '') ? Math.min(100, Math.max(0, sasRaw)) : null;

        // 4. Hitung Nilai Akhir (Rata-rata NS dan SAS) - HANYA JIKA NS DAN SAS KEDUANYA SUDAH TERISI
        const naInput = row.querySelector('.na-input');
        if (nsVal !== null && sasVal !== null) {
            const na = Math.round(((nsVal + sasVal) / 2) * 100) / 100;
            naInput.value = na;
        } else {
            naInput.value = '';
        }

        // 5. Perbarui tampilan badge status
        updateRowStatus(row);
    }

    function openModalCapaian(siswaId, siswaNama, nomor) {
        document.getElementById('capaian_siswa_id').value = siswaId;
        document.getElementById('capaian_nomor').value = nomor;
        document.getElementById('modalCapaianTitle').innerText = 'Capaian Kompetensi ' + nomor;
        document.getElementById('modalCapaianSubtitle').innerText = 'Siswa: ' + siswaNama;
        document.getElementById('capaian_deskripsi').value = '';

        const url = "{{ route($rolePrefix . '.capaian.detail') }}?siswa_id=" + siswaId + "&mapel_id={{ $selectedMapelId }}&semester_id={{ $selectedSemesterId }}";

        // Fetch existing via AJAX
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    let desc = nomor === 1 ? (data.deskripsi_1 || '') : (data.deskripsi_2 || '');
                    if (nomor === 2 && desc === 'Menunjukkan penguasaan materi sesuai tujuan pembelajaran.') {
                        desc = '';
                    }
                    document.getElementById('capaian_deskripsi').value = desc;
                }
            })
            .catch(err => console.log(err));

        document.getElementById('modalCapaian').classList.remove('hidden');
    }

    function closeModalCapaian() {
        document.getElementById('modalCapaian').classList.add('hidden');
    }

    function saveCapaian() {
        const form = document.getElementById('formCapaian');
        const formData = new FormData(form);
        const siswaId = document.getElementById('capaian_siswa_id').value;
        const nomor = document.getElementById('capaian_nomor').value;
        const row = document.querySelector(`tr.row-siswa[data-siswa-id="${siswaId}"]`);

        // Lampirkan juga nilai baris tabel jika sedang diisi agar tersimpan bersama
        if (row) {
            row.querySelectorAll('.tp-input').forEach((input, index) => {
                if (input.value !== '') {
                    formData.append(`tp${index + 1}`, input.value);
                }
            });
            const nsInput = row.querySelector('.ns-input');
            if (nsInput && nsInput.value !== '') formData.append('ns', nsInput.value);
            const sasInput = row.querySelector('.sas-input');
            if (sasInput && sasInput.value !== '') formData.append('sas', sasInput.value);
            const naInput = row.querySelector('.na-input');
            if (naInput && naInput.value !== '') formData.append('nilai_akhir', naInput.value);
        }

        const saveBtn = document.getElementById('btnSubmitCapaian');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerText = 'Menyimpan...';
        }

        fetch("{{ route($rolePrefix . '.capaian.store') }}", {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Simpan';
            }

            if (data.status === 'success') {
                closeModalCapaian();

                // Update status capaian di baris siswa
                if (row) {
                    const deskripsi = document.getElementById('capaian_deskripsi').value.trim();
                    const isFilled = deskripsi !== '';
                    row.setAttribute(`data-has-capaian${nomor}`, isFilled ? '1' : '0');

                    const btn = row.querySelector(`.btn-capaian-${nomor}`);
                    if (btn) {
                        if (isFilled) {
                            btn.className = `btn-capaian-${nomor} px-2.5 py-1 rounded text-[11px] font-bold transition bg-edu-blue text-white hover:bg-blue-700`;
                        } else {
                            btn.className = `btn-capaian-${nomor} px-2.5 py-1 rounded text-[11px] font-bold transition bg-red-500 text-white hover:bg-red-600`;
                        }
                    }

                    updateRowStatus(row);
                }

                showToast(data.message || 'Capaian kompetensi berhasil disimpan.', true);
            } else {
                alert(data.message || 'Gagal menyimpan capaian kompetensi.');
            }
        })
        .catch(err => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerText = 'Simpan';
            }
            console.error(err);
            showToast('Terjadi kesalahan saat menyimpan.', false);
        });
    }
</script>
@endsection
