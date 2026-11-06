<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Rapor – {{ $siswa->nama ?? 'Siswa' }}</title>
<style>
/* ===== RESET ===== */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10pt;
    background: #ccc;
    color: #000;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}

/* ===== TOOLBAR (screen only) ===== */
.toolbar {
    position: sticky; top: 0; z-index: 100;
    background: #333; padding: 8px 18px;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 2px 6px rgba(0,0,0,.5);
}
.toolbar a, .toolbar button {
    padding: 5px 14px; border-radius: 4px;
    font-size: 9.5pt; font-weight: 700; cursor: pointer;
    border: none; text-decoration: none;
    font-family: Arial, sans-serif;
    display: inline-flex; align-items: center; gap: 4px;
}
.btn-back  { background: #e5e5e5; color: #111; }
.btn-print { background: #555; color: #fff; }
.toolbar .info { color: #ccc; font-size: 8.5pt; margin-left: 8px; }

/* ===== PAGE ===== */
.page {
    width: 210mm;
    min-height: 297mm;
    background: #fff;
    margin: 14px auto;
    box-shadow: 0 2px 14px rgba(0,0,0,.35);
    position: relative;
    overflow: hidden;
}
/* padding untuk masing-masing jenis halaman */
.page-cover   { padding: 20mm 20mm 15mm 20mm; }
.page-datadiri{ padding: 14mm 14mm 14mm 16mm; }
.page-isi     { padding: 10mm 13mm 20mm 14mm; }

/* ========================
   HALAMAN 1: COVER
   ======================== */
.cover-body {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 262mm;
    text-align: center;
    gap: 0;
}
.cover-title {
    font-size: 22pt;
    font-weight: 900;
    line-height: 1.2;
    margin-bottom: 8px;
    text-transform: uppercase;
}
.cover-subtitle {
    font-size: 14pt;
    font-weight: 700;
    line-height: 1.35;
    text-transform: uppercase;
    margin-bottom: 22px;
}
.cover-logo {
    width: 150px; height: 150px;
    border: 4px solid #000;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-direction: column;
    font-size: 11pt; font-weight: 900; line-height: 1.5;
    margin: 0 auto 22px;
}
.cover-schoolname {
    font-size: 12pt; font-weight: 900;
    text-transform: uppercase; margin-bottom: 5px;
}
.cover-addr {
    font-size: 9pt; color: #333; line-height: 1.6; margin-bottom: 24px;
}
.cover-divider {
    border-top: 2px solid #000;
    width: 100%; margin: 16px 0;
}
.cover-student-box {
    border: 1.5px solid #000;
    padding: 10px 20px;
    width: 100%;
    text-align: left;
}
.cover-student-box table {
    width: 100%; border-collapse: collapse; font-size: 10.5pt;
}
.cover-student-box td { padding: 3px 2px; }
.cover-student-box .lbl { width: 90px; }
.cover-student-box .sep { width: 12px; text-align: center; }

/* ========================
   HALAMAN 2: DATA DIRI
   ======================== */
.dd-title {
    font-size: 11pt; font-weight: 900;
    text-transform: uppercase; text-align: center;
    margin-bottom: 12px; letter-spacing: .5px;
}
.dd-top {
    display: flex; align-items: flex-start; gap: 0;
    margin-bottom: 8px;
}
.dd-list { flex: 1; }
.photo-block {
    margin-left: 14px; flex-shrink: 0;
}
.photo-box {
    width: 85px; height: 110px;
    border: 1px solid #555;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    font-size: 8pt; color: #666; text-align: center;
    line-height: 1.4;
}

.dd-table { width: 100%; border-collapse: collapse; font-size: 9.5pt; }
.dd-table td { padding: 2.2px 1px; vertical-align: top; line-height: 1.3; }
.dd-table .no  { width: 24px; text-align: right; }
.dd-table .lbl { width: 128px; padding-left: 3px; }
.dd-table .sep { width: 10px; text-align: center; }

.dd-footer {
    margin-top: auto;
    padding-top: 16px;
    display: flex;
    justify-content: flex-end;
    font-size: 9.5pt;
    text-align: center;
}
.dd-footer .ttd-col { text-align: center; }
.dd-footer .ttd-space { height: 52px; }
.dd-footer .ul-name { font-weight: 900; text-decoration: underline; }
.dd-footer .nip { font-size: 9pt; }

/* ========================
   HALAMAN ISI
   ======================== */
.isi-title {
    text-align: center;
    font-size: 12pt; font-weight: 900;
    text-transform: uppercase;
    margin-bottom: 8px;
    letter-spacing: .5px;
}
.info-hdr {
    display: flex; gap: 0; margin-bottom: 7px; font-size: 9.5pt;
}
.info-hdr table { flex: 1; border-collapse: collapse; }
.info-hdr td { padding: 1.5px 2px; vertical-align: top; }
.info-hdr .lbl { width: 92px; }
.info-hdr .sep { width: 8px; }
.kktp-line {
    font-size: 9.5pt; font-style: italic; margin-bottom: 5px;
}

/* Tabel nilai */
.tbl-nilai {
    width: 100%; border-collapse: collapse; font-size: 9pt; margin-bottom: 5px;
}
.tbl-nilai th, .tbl-nilai td {
    border: 1px solid #555; padding: 3px 4px; vertical-align: middle;
}
.tbl-nilai thead th {
    background: #d4d4d4; color: #000;
    text-align: center; font-size: 8.5pt; padding: 4px;
}
.tbl-nilai td.c  { text-align: center; }
.tbl-nilai td.num { text-align: center; font-weight: 700; }
.tbl-nilai td.cap { font-size: 8.5pt; line-height: 1.3; vertical-align: top; }
.tbl-nilai tr.alt { background: #f5f5f5; }
.tbl-nilai tr.grp td {
    background: #e8e8e8; font-weight: 700; font-size: 9pt;
    border: 1px solid #555; padding: 3px 6px;
}

/* Tabel ekskul / prestasi */
.tbl-sec {
    width: 100%; border-collapse: collapse; font-size: 9pt;
}
.tbl-sec th, .tbl-sec td {
    border: 1px solid #555; padding: 3px 5px;
}
.tbl-sec thead th {
    background: #d4d4d4; color: #000; text-align: center; font-size: 8.5pt;
}
.tbl-sec td.c { text-align: center; }

/* Ketidakhadiran */
.tbl-hadir {
    border-collapse: collapse; font-size: 9.5pt;
}
.tbl-hadir th, .tbl-hadir td {
    border: 1px solid #555; padding: 3px 10px;
}
.tbl-hadir thead th {
    background: #d4d4d4; color: #000; text-align: center;
}
.tbl-hadir td.c { text-align: center; }

/* Keputusan */
.keputusan {
    border: 1px solid #555; padding: 7px 10px;
    font-size: 9.5pt; line-height: 1.55;
}
.keputusan .strike { text-decoration: line-through; color: #777; }
.keputusan .note   { font-size: 8.5pt; color: #555; margin-top: 3px; }

/* TTD isi */
.ttd-isi {
    display: flex; justify-content: space-between;
    margin-top: 12px; font-size: 9.5pt;
}
.ttd-isi .col { text-align: center; flex: 1; }
.ttd-isi .sp  { height: 55px; }
.ttd-isi .sig-line {
    border-bottom: 1px solid #555;
    width: 80%; margin: 0 auto; height: 55px;
}
.ttd-isi .ul  { font-weight: 900; text-decoration: underline; }
.ttd-isi .nip { font-size: 8.5pt; }

/* Footer strip */
.pg-footer {
    position: absolute; bottom: 0; left: 0; right: 0;
    background: #555; color: #fff;
    padding: 4px 12px; font-size: 7.5pt;
    display: flex; justify-content: space-between; align-items: center;
}

/* Section label */
.sec-label { font-size: 9.5pt; font-weight: 700; margin: 6px 0 3px; }
.garis { border-top: 1px solid #000; margin: 5px 0; }

/* ===== PRINT ===== */
@media print {
    body { background: #fff; }
    .toolbar { display: none !important; }
    .page {
        margin: 0; box-shadow: none; width: 100%;
        min-height: unset; page-break-after: always;
    }
    .page:last-child { page-break-after: auto; }
    /* force gray backgrounds to print */
    .tbl-nilai thead th, .tbl-sec thead th,
    .tbl-hadir thead th, .tbl-nilai tr.grp td,
    .tbl-nilai tr.alt { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>

{{-- TOOLBAR --}}
<div class="toolbar">
    <a href="{{ route('wali.rapor') }}" class="btn-back">← Kembali</a>
    @if($isLengkap ?? false)
    <button onclick="window.print()" class="btn-print">Cetak Rapor</button>
    @else
    <button onclick="alert('Peringatan: Nilai siswa ini belum lengkap secara keseluruhan pada sistem penilaian. Hanya mata pelajaran yang telah lengkap yang tercetak.')" class="btn-print" style="background:#b45309;">Cetak Rapor (Draft)</button>
    @endif
    <span class="info">{{ $siswa->nama ?? '-' }} | {{ $siswa->kelas->nama_kelas ?? '-' }} | Semester {{ $semesterLabel }}</span>
    @if(!($isLengkap ?? false))
    <span style="background:#fef08a;color:#854d0e;padding:2px 8px;border-radius:4px;font-size:8pt;font-weight:bold;margin-left:auto;">
        ⚠️ Nilai Belum Lengkap (Hanya mapel dengan nilai & capaian lengkap yang ditampilkan)
    </span>
    @endif
</div>

@php
    $namaKelas = $siswa->kelas->nama_kelas ?? '-';
    $tingkat   = (int)($siswa->kelas->tingkat ?? 7);
    $fase      = $tingkat >= 9 ? 'E' : 'D';

    // Kelas naik / sekarang
    $kelasRomawi = ['7'=>'VII','8'=>'VIII','9'=>'IX'];
    $terbilang   = ['7'=>'tujuh','8'=>'delapan','9'=>'sembilan'];
    $kelasNaik   = $kelasRomawi[strval($tingkat + 1)] ?? 'berikutnya';
    $kelasNaikTb = $terbilang[strval($tingkat + 1)] ?? '';
    $kelasNowRom = $kelasRomawi[strval($tingkat)] ?? $namaKelas;
    $kelasNowTb  = $terbilang[strval($tingkat)] ?? '';

    // Pengelompokan mapel
    $namaMapelPilihan = ['Prakarya','Seni Budaya','Informatika','TIK'];
    $namaMapelMulok   = ['Bahasa Madura','Muatan Lokal','Bahasa Daerah','Bahasa Jawa'];

    // HANYA tampilkan nilai yang SUDAH LENGKAP (minimal 1 TP terisi, SAS terisi, Nilai Akhir > 0, Capaian 1 & 2 terisi)
    $nilaiAll = $siswa->nilai->filter(function($n) {
        if (!$n) return false;

        $hasAtLeastOneTp = false;
        for ($i = 1; $i <= 8; $i++) {
            $col = 'tp' . $i;
            if (!is_null($n->$col) && $n->$col !== '') {
                $hasAtLeastOneTp = true;
                break;
            }
        }
        if (!$hasAtLeastOneTp) return false;

        if (is_null($n->sas) || $n->sas === '') return false;
        if (is_null($n->nilai_akhir) || (float)$n->nilai_akhir <= 0) return false;
        $c = $n->capaian;
        if (!$c) return false;
        if (empty(trim($c->deskripsi_1 ?? '')) || empty(trim($c->deskripsi_2 ?? ''))) return false;
        return true;
    });

    $nilaiUtama   = $nilaiAll->filter(fn($n)=> !in_array($n->mataPelajaran->nama_mapel??'', array_merge($namaMapelPilihan,$namaMapelMulok)));
    $nilaiPilihan = $nilaiAll->filter(fn($n)=>  in_array($n->mataPelajaran->nama_mapel??'', $namaMapelPilihan));
    $nilaiMulok   = $nilaiAll->filter(fn($n)=>  in_array($n->mataPelajaran->nama_mapel??'', $namaMapelMulok));
    if ($nilaiUtama->isEmpty() && $nilaiPilihan->isEmpty() && $nilaiMulok->isEmpty()) {
        $nilaiUtama = $nilaiAll;
    }
@endphp

{{-- ================================================
     HALAMAN 1: COVER
     ================================================ --}}
<div class="page page-cover">
    <div class="cover-body">

        <div class="cover-title">
            LAPORAN<br>
            HASIL BELAJAR<br>
            PESERTA DIDIK
        </div>
        <div class="cover-subtitle">
            SEKOLAH MENENGAH PERTAMA<br>
            (SMP)
        </div>

        <div class="cover-logo">
            SMP<br>NEGERI 1<br>SAMPANG
        </div>

        <div class="cover-schoolname">UPTD SMP NEGERI 1 SAMPANG</div>
        <div class="cover-addr">
            Jl. Wijayakusuma No.2, Sampang, Jawa Timur 69213<br>
            Telp. (0323) 321082 &nbsp;|&nbsp; smpn1sampang.sch.id
        </div>

        <div class="cover-divider"></div>

        <div class="cover-student-box">
            <table>
                <tr>
                    <td class="lbl">Nama</td>
                    <td class="sep">:</td>
                    <td><strong>{{ strtoupper($siswa->nama ?? '-') }}</strong></td>
                </tr>
                <tr>
                    <td class="lbl">NIS / NISN</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->nis ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Kelas</td>
                    <td class="sep">:</td>
                    <td>{{ $namaKelas }}</td>
                </tr>
                <tr>
                    <td class="lbl">Tahun Pelajaran</td>
                    <td class="sep">:</td>
                    <td>{{ $tahunAjaran }}</td>
                </tr>
            </table>
        </div>

    </div>
</div>

{{-- ================================================
     HALAMAN 2: KETERANGAN TENTANG DIRI PESERTA DIDIK
     ================================================ --}}
<div class="page page-datadiri" style="display:flex;flex-direction:column;min-height:297mm;">

    <div class="dd-title">Keterangan Tentang Diri Peserta Didik</div>

    {{-- Foto di kanan atas, list di kiri --}}
    <div class="dd-top">
        <div class="dd-list">
            <table class="dd-table">
                <tr>
                    <td class="no">1.</td>
                    <td class="lbl">Nama Peserta Didik (Lengkap)</td>
                    <td class="sep">:</td>
                    <td><strong>{{ strtoupper($siswa->nama ?? '-') }}</strong></td>
                </tr>
                <tr>
                    <td class="no">2.</td>
                    <td class="lbl">Nomor Induk</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->nis ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="no">3.</td>
                    <td class="lbl">Tempat Tanggal Lahir</td>
                    <td class="sep">:</td>
                    <td>{{ ($siswa->tempat_lahir ?? '') . (($siswa->tempat_lahir ?? null) ? ', ' : '') . (isset($siswa->tanggal_lahir) ? \Carbon\Carbon::parse($siswa->tanggal_lahir)->isoFormat('D MMMM Y') : '') }}</td>
                </tr>
                <tr>
                    <td class="no">4.</td>
                    <td class="lbl">Jenis Kelamin</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->jenis_kelamin=='L' ? 'Laki-laki' : ($siswa->jenis_kelamin=='P' ? 'Perempuan' : ($siswa->jenis_kelamin ?? '-')) }}</td>
                </tr>
                <tr>
                    <td class="no">5.</td>
                    <td class="lbl">Agama</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->agama ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">6.</td>
                    <td class="lbl">Status dalam Keluarga</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->status_keluarga ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">7.</td>
                    <td class="lbl">Anak ke</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->anak_ke ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">8.</td>
                    <td class="lbl">Alamat Siswa</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->alamat ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">9.</td>
                    <td class="lbl">Nomor Telepon Rumah</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->no_telp ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">10.</td>
                    <td class="lbl">Sekolah Asal</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->sekolah_asal ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">11.</td>
                    <td class="lbl">Diterima di sekolah ini</td>
                    <td class="sep">:</td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="lbl" style="padding-left:14px">Di kelas</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->kelas_diterima ?? '' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="lbl" style="padding-left:14px">Pada tanggal</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->tanggal_diterima ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">12.</td>
                    <td class="lbl">Nama Orang Tua</td>
                    <td class="sep">:</td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="lbl" style="padding-left:14px">a. Ayah</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->nama_ayah ?? '' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="lbl" style="padding-left:14px">b. Ibu</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->nama_ibu ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">13.</td>
                    <td class="lbl">Alamat Orang Tua</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->alamat_ortu ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">14.</td>
                    <td class="lbl">Nomor Telepon Rumah</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->telp_ortu ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">15.</td>
                    <td class="lbl">Pekerjaan Orang Tua</td>
                    <td class="sep">:</td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="lbl" style="padding-left:14px">a. Ayah</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->pekerjaan_ayah ?? '' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="lbl" style="padding-left:14px">b. Ibu</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->pekerjaan_ibu ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">16.</td>
                    <td class="lbl">Nama Wali Siswa</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->nama_wali ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">17.</td>
                    <td class="lbl">Alamat Wali Siswa</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->alamat_wali ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">18.</td>
                    <td class="lbl">Nomor Telepon Wali</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->telp_wali ?? '' }}</td>
                </tr>
                <tr>
                    <td class="no">19.</td>
                    <td class="lbl">Pekerjaan Wali Siswa</td>
                    <td class="sep">:</td>
                    <td>{{ $siswa->pekerjaan_wali ?? '' }}</td>
                </tr>
            </table>
        </div>

        {{-- Foto pojok kanan atas --}}
        <div class="photo-block">
            <div class="photo-box">
                Pas Foto<br>3 &times; 4
            </div>
        </div>
    </div>

    {{-- TTD Kepala Sekolah di kanan bawah --}}
    <div class="dd-footer" style="margin-top:auto;">
        <div class="ttd-col">
            <p>Sampang, {{ $tanggalRapor }}</p>
            <p>Kepala UPTD SMP Negeri 1 Sampang,</p>
            <div class="ttd-space"></div>
            <p class="ul-name">{{ $namaKepsek ?? '-' }}</p>
            <p class="nip">NIP. {{ $nipKepsek ?? '-' }}</p>
        </div>
    </div>

</div>

{{-- ================================================
     HALAMAN 3: ISI — PENCAPAIAN KOMPETENSI (Nilai)
     ================================================ --}}
<div class="page page-isi" style="padding-bottom:20mm;">

    <div class="isi-title">Pencapaian Kompetensi Peserta Didik</div>

    {{-- Info Header --}}
    <div class="info-hdr">
        <table>
            <tr><td class="lbl">Nama</td><td class="sep">:</td><td><strong>{{ strtoupper($siswa->nama ?? '-') }}</strong></td></tr>
            <tr><td class="lbl">Nomor Induk</td><td class="sep">:</td><td>{{ $siswa->nis ?? '-' }}</td></tr>
            <tr><td class="lbl">Nama Sekolah</td><td class="sep">:</td><td>UPTD SMP NEGERI 1 SAMPANG</td></tr>
            <tr><td class="lbl">Alamat</td><td class="sep">:</td><td>Jl. Wijayakusuma No.2, Sampang</td></tr>
        </table>
        <table>
            <tr><td class="lbl">Kelas</td><td class="sep">:</td><td>{{ $namaKelas }}</td></tr>
            <tr><td class="lbl">Fase</td><td class="sep">:</td><td>{{ $fase }}</td></tr>
            <tr><td class="lbl">Semester</td><td class="sep">:</td><td>{{ $semesterLabel=='Genap' ? '2 (Dua)' : '1 (Satu)' }}</td></tr>
            <tr><td class="lbl">Tahun Pelajaran</td><td class="sep">:</td><td>{{ $tahunAjaran }}</td></tr>
        </table>
    </div>

    <p class="kktp-line">Kriteria Ketercapaian Tujuan Pembelajaran Minimal : 76 - 88</p>

    {{-- Tabel Nilai --}}
    <table class="tbl-nilai">
        <thead>
            <tr>
                <th style="width:4%">No</th>
                <th style="width:20%">Mata Pelajaran</th>
                <th style="width:9%">Nilai Akhir</th>
                <th>Capaian Kompetensi</th>
            </tr>
        </thead>
        <tbody>
        @php $no = 1; $pair = 0; @endphp

        {{-- Mapel Utama --}}
        @foreach($nilaiUtama as $n)
        @php
            $na  = $n->nilai_akhir ?? null;
            $d1  = $n->capaian->deskripsi_1 ?? '-';
            $d2  = $n->capaian->deskripsi_2 ?? '-';
            $mp  = $n->mataPelajaran->nama_mapel ?? '-';
            $alt = ($pair % 2 == 1) ? 'alt' : '';
            $pair++;
        @endphp
        <tr class="{{ $alt }}">
            <td class="c" rowspan="2">{{ $no++ }}</td>
            <td rowspan="2">{{ $mp }}</td>
            <td class="num" rowspan="2">{{ $na !== null ? number_format($na,0) : '-' }}</td>
            <td class="cap">{{ $d1 }}</td>
        </tr>
        <tr class="{{ $alt }}"><td class="cap">{{ $d2 }}</td></tr>
        @endforeach

        {{-- Mapel Pilihan --}}
        @if($nilaiPilihan->count() > 0)
        <tr class="grp">
            <td>{{ $no++ }}</td>
            <td colspan="3">Mata Pelajaran Pilihan :</td>
        </tr>
        @foreach($nilaiPilihan as $n)
        @php
            $na = $n->nilai_akhir ?? null;
            $d1 = $n->capaian->deskripsi_1 ?? '-';
            $d2 = $n->capaian->deskripsi_2 ?? '-';
            $mp = $n->mataPelajaran->nama_mapel ?? '-';
        @endphp
        <tr>
            <td class="c" rowspan="2"></td>
            <td rowspan="2" style="padding-left:12px">{{ $mp }}</td>
            <td class="num" rowspan="2">{{ $na !== null ? number_format($na,0) : '-' }}</td>
            <td class="cap">{{ $d1 }}</td>
        </tr>
        <tr><td class="cap">{{ $d2 }}</td></tr>
        @endforeach
        @endif

        {{-- Muatan Lokal --}}
        @if($nilaiMulok->count() > 0)
        <tr class="grp">
            <td>{{ $no }}</td>
            <td colspan="3">Muatan Lokal :</td>
        </tr>
        @foreach($nilaiMulok as $n)
        @php
            $na = $n->nilai_akhir ?? null;
            $d1 = $n->capaian->deskripsi_1 ?? '-';
            $d2 = $n->capaian->deskripsi_2 ?? '-';
            $mp = $n->mataPelajaran->nama_mapel ?? '-';
        @endphp
        <tr>
            <td class="c" rowspan="2"></td>
            <td rowspan="2" style="padding-left:12px">{{ $mp }}</td>
            <td class="num" rowspan="2">{{ $na !== null ? number_format($na,0) : '-' }}</td>
            <td class="cap">{{ $d1 }}</td>
        </tr>
        <tr><td class="cap">{{ $d2 }}</td></tr>
        @endforeach
        @endif

        @if($nilaiAll->isEmpty())
        <tr><td colspan="4" class="c" style="padding:14px;font-style:italic;color:#555">Belum ada mata pelajaran dengan status nilai & capaian lengkap untuk ditampilkan pada rapor ini.</td></tr>
        @endif
        </tbody>
    </table>

    {{-- Footer strip hal 3 --}}
    <div class="pg-footer">
        <span>UPTD SMP NEGERI 1 SAMPANG</span>
        <span>{{ strtoupper($siswa->nama??'') }} | {{ $siswa->nis??'' }} | Kelas: {{ $namaKelas }} | Semester: {{ $semesterLabel }} | {{ $tahunAjaran }}</span>
        <span>Halaman: 1</span>
    </div>
</div>

{{-- ================================================
     HALAMAN 4: ISI — Ekskul, Prestasi, Kehadiran, TTD
     ================================================ --}}
<div class="page page-isi" style="padding-bottom:20mm;">

    {{-- Ekskul + Prestasi --}}
    <div style="display:flex;gap:14px;margin-bottom:8px;">

        <div style="flex:1.2">
            <div class="sec-label">Ekstrakurikuler</div>
            <table class="tbl-sec">
                <thead>
                    <tr>
                        <th style="width:7%">No.</th>
                        <th style="width:38%">Ekstrakurikuler</th>
                        <th style="width:13%">Nilai</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                @php $ekList = $siswa->nilaiEkstrakurikuler; @endphp
                @for($i = 0; $i < 4; $i++)
                @php $ek = $ekList[$i] ?? null; @endphp
                <tr>
                    <td class="c">{{ $i+1 }}</td>
                    <td>{{ $ek?->ekstrakurikuler?->nama_ekstrakurikuler ?? '' }}</td>
                    <td class="c">{{ $ek?->predikat ?? '' }}</td>
                    <td>{{ $ek?->deskripsi ?? '' }}</td>
                </tr>
                @endfor
                </tbody>
            </table>
        </div>

        <div style="flex:0.9">
            <div class="sec-label">Jenis Prestasi</div>
            <table class="tbl-sec">
                <thead>
                    <tr>
                        <th style="width:8%">No.</th>
                        <th>Jenis Prestasi</th>
                        <th style="width:32%">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                @php $prList = $siswa->prestasi; @endphp
                @for($i = 0; $i < 3; $i++)
                @php $pr = $prList[$i] ?? null; @endphp
                <tr>
                    <td class="c">{{ $i+1 }}.</td>
                    <td>{{ $pr?->nama_prestasi ?? '' }}</td>
                    <td>{{ $pr?->keterangan ?? '' }}</td>
                </tr>
                @endfor
                </tbody>
            </table>
        </div>

    </div>

    {{-- Ketidakhadiran + Keputusan --}}
    <div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:10px;">

        <div>
            <table class="tbl-hadir">
                <thead>
                    <tr><th colspan="3">Ketidakhadiran</th></tr>
                </thead>
                <tbody>
                    <tr><td>Sakit</td><td class="c">{{ $presensi->sakit ?? '-' }}</td><td>hari</td></tr>
                    <tr><td>Izin</td><td class="c">{{ $presensi->izin ?? '-' }}</td><td>hari</td></tr>
                    <tr><td>Tanpa Keterangan</td><td class="c">{{ $presensi->alpha ?? '-' }}</td><td>hari</td></tr>
                </tbody>
            </table>
        </div>

        <div style="flex:1;">
            <div class="keputusan">
                <p style="font-weight:700;margin-bottom:2px;">Keputusan:</p>
                @if($selectedSemesterId == 2)
                    <p>Berdasarkan pencapaian kompetensi pada semester ke-1 dan ke-2, peserta didik ditetapkan *)</p>
                    @php
                        $isTinggal = (($catatan->status_kenaikan ?? 'Naik') === 'Tinggal');
                    @endphp
                    <p class="{{ $isTinggal ? 'strike' : '' }}">naik ke kelas &nbsp;<strong>{{ $kelasNaik }}</strong> ( {{ $kelasNaikTb }} )</p>
                    <p class="{{ !$isTinggal ? 'strike' : '' }}">tinggal di kelas &nbsp;{{ $kelasNowRom }} ( {{ $kelasNowTb }} )</p>
                    <p class="note">*)Coret yang tidak perlu.</p>
                @else
                    <p>Berdasarkan pencapaian kompetensi pada semester Gasal (Semester 1), peserta didik berhak melanjutkan kegiatan belajar ke semester Genap.</p>
                    <p class="note" style="margin-top:4px;">*) Keputusan kenaikan kelas ditetapkan pada akhir tahun pelajaran (Semester Genap).</p>
                @endif
            </div>
        </div>

    </div>

    {{-- Catatan Sikap & Wali Kelas --}}
    <div style="margin-bottom:10px;">
        <div class="sec-label">Catatan Wali Kelas</div>
        <div style="border:1px solid #111; padding:7px 10px; font-size:10px; min-height:40px; line-height:1.45;">
            @if(!empty($catatan?->catatan_wali))
                {{ $catatan->catatan_wali }}
            @else
                <span style="font-style:italic;color:#666;">Tingkatkan terus prestasi belajar dan pertahankan kedisiplinan serta akhlak mulia dalam segala aktivitas sekolah.</span>
            @endif
            @if(!empty($catatan?->sikap_spiritual) || !empty($catatan?->sikap_sosial))
                <div style="margin-top:5px;padding-top:4px;border-top:1px dashed #bbb;font-size:9.5px;color:#222;display:flex;gap:16px;">
                    @if(!empty($catatan?->sikap_spiritual))
                        <div style="flex:1;"><strong>Sikap Spiritual:</strong> {{ $catatan->sikap_spiritual }}</div>
                    @endif
                    @if(!empty($catatan?->sikap_sosial))
                        <div style="flex:1;"><strong>Sikap Sosial:</strong> {{ $catatan->sikap_sosial }}</div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- TTD --}}
    <div class="ttd-isi">
        <div class="col">
            <p>Mengetahui:</p>
            <p>Orang Tua/Wali,</p>
            <div class="sig-line"></div>
            <p>.....................................</p>
        </div>
        <div class="col">
            <p>Wali Kelas</p>
            <div class="sp"></div>
            <p class="ul">{{ $namaWali }}</p>
            <p class="nip">NIP. {{ $nipWali }}</p>
        </div>
        <div class="col">
            <p>Sampang, {{ $tanggalRapor }}</p>
            <p>Kepala Sekolah,</p>
            <div class="sp"></div>
            <p class="ul">{{ $namaKepsek ?? '-' }}</p>
            <p class="nip">NIP. {{ $nipKepsek ?? '-' }}</p>
        </div>
    </div>

    {{-- Footer strip hal 4 --}}
    <div class="pg-footer">
        <span>UPTD SMP NEGERI 1 SAMPANG</span>
        <span>{{ strtoupper($siswa->nama??'') }} | {{ $siswa->nis??'' }} | Kelas: {{ $namaKelas }} | Semester: {{ $semesterLabel }} | {{ $tahunAjaran }}</span>
        <span>Halaman: 2</span>
    </div>
</div>

</body>
</html>
