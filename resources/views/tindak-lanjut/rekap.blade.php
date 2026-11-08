@extends('layouts.app')

@section('title', 'Rekap Riwayat Konseling')
@section('page_title', 'Tindak Lanjut')
@section('page_subtitle', 'Dashboard > Early Warning > Tindak Lanjut')

@section('content')
@php
    $rolePrefix = $rolePrefix ?? (auth()->user() && auth()->user()->isOperator() ? 'operator' : 'wali');
@endphp
<div class="space-y-5 font-body">

    {{-- Header & Filter --}}
    <div class="bg-white rounded-2xl p-5 shadow-md border border-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-edu-blue flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-clipboard-list"></i>
                </div>
                <div>
                    <h2 class="text-base font-black text-gray-900 font-heading">Rekap Riwayat Konseling</h2>
                    <p class="text-xs text-gray-500">Seluruh sesi bimbingan dan draf konseling siswa yang telah tersimpan.</p>
                </div>
            </div>
            <span class="text-xs px-3 py-1 rounded-full bg-blue-100 text-edu-blue font-bold">
                {{ $semuaRiwayat->count() }} Sesi Tersimpan
            </span>
        </div>

        {{-- Filter Bar --}}
        <form action="{{ url()->current() }}" method="GET" class="mt-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-[11px] font-bold text-gray-600 mb-1 font-heading">Semester</label>
                <select name="semester_id" onchange="this.form.submit()"
                    class="bg-gray-50 border border-gray-200 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($semesterList as $sm)
                        <option value="{{ $sm->id_semester }}" {{ $semesterId == $sm->id_semester ? 'selected' : '' }}>
                            {{ $sm->nama_semester }} {{ $sm->tahun_ajaran }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-[11px] font-bold text-gray-600 mb-1 font-heading">Cari Siswa</label>
                <div class="relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-edu-orange text-xs"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Nama atau NIS siswa..."
                        class="w-full pl-9 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-xl text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-edu-orange">
                </div>
            </div>
            <button type="submit" class="bg-edu-orange hover:bg-orange-600 text-white text-xs font-bold py-2 px-4 rounded-xl transition">
                <i class="fa-solid fa-filter mr-1"></i> Filter
            </button>
            @if($search)
                <a href="{{ url()->current() }}?semester_id={{ $semesterId }}" class="text-xs text-gray-400 hover:text-gray-700 py-2 px-3 rounded-xl border border-gray-200 hover:bg-gray-50 transition">
                    <i class="fa-solid fa-xmark mr-1"></i> Reset
                </a>
            @endif
        </form>
    </div>

    {{-- Daftar Riwayat --}}
    @if($semuaRiwayat->isEmpty())
        <div class="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
            <i class="fa-solid fa-clipboard-list text-4xl text-gray-200 mb-3"></i>
            <p class="text-sm font-bold text-gray-500 font-heading">Belum Ada Riwayat Konseling</p>
            <p class="text-xs text-gray-400 mt-1">
                @if($search)
                    Tidak ditemukan riwayat dengan kata kunci "<strong>{{ $search }}</strong>".
                @else
                    Belum ada sesi konseling yang tersimpan untuk semester ini.
                @endif
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($semuaRiwayat as $tl)
                @php
                    $siswaRiwayat = $tl->analisisSiswa?->siswa;
                    $statusBadge  = match($tl->status) {
                        'Selesai'         => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'Sedang Diproses' => 'bg-amber-100 text-amber-800 border-amber-200',
                        default           => 'bg-blue-100 text-blue-800 border-blue-200',
                    };
                @endphp
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:border-edu-blue/30 transition">

                    {{-- Header Kartu: Info Siswa + Aksi --}}
                    <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-orange-100 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user text-orange-400 text-sm"></i>
                            </div>
                            <div>
                                <a href="{{ route($rolePrefix . '.tindak-lanjut', $siswaRiwayat?->id_siswa) }}"
                                   class="font-black text-gray-900 text-sm font-heading hover:text-edu-blue transition">
                                    {{ $siswaRiwayat?->nama ?? 'Siswa Tidak Diketahui' }}
                                </a>
                                <p class="text-[11px] text-gray-500">
                                    NIS {{ $siswaRiwayat?->nis ?? '-' }}
                                    &bull; {{ $siswaRiwayat?->kelas?->nama_kelas ?? '-' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $statusBadge }}">
                                {{ $tl->status }}
                            </span>
                            {{-- Tombol Tandai Selesai --}}
                            @if($tl->status !== 'Selesai')
                                <form action="{{ route($rolePrefix . '.tindak-lanjut.selesai', $tl->id_tindak_lanjut) }}" method="POST"
                                    onsubmit="return confirm('Tandai sesi konseling ini sebagai Selesai?')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition shadow-sm">
                                        <i class="fa-solid fa-check"></i> Tandai Selesai
                                    </button>
                                </form>
                            @endif
                            {{-- Tombol Hapus --}}
                            <form action="{{ route($rolePrefix . '.tindak-lanjut.destroy', $tl->id_tindak_lanjut) }}" method="POST"
                                onsubmit="return confirm('Hapus sesi konseling ini? Data tidak dapat dikembalikan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-bold transition">
                                    <i class="fa-regular fa-trash-can"></i> Hapus
                                </button>
                            </form>
                            {{-- Tombol ke halaman konseling siswa --}}
                            @if($siswaRiwayat)
                                <a href="{{ route($rolePrefix . '.tindak-lanjut', $siswaRiwayat->id_siswa) }}"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-edu-blue border border-blue-200 text-[11px] font-bold transition">
                                    <i class="fa-solid fa-arrow-right text-[10px]"></i> Tambah Sesi
                                </a>
                            @endif
                        </div>
                    </div>

                    {{-- Detail Sesi --}}
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                        <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                            <span class="text-gray-400 text-[10px] block font-heading font-semibold">Tanggal Sesi</span>
                            <strong class="text-gray-800 font-heading block mt-0.5 text-xs">
                                <i class="fa-regular fa-calendar mr-1 text-edu-orange"></i>
                                {{ \Carbon\Carbon::parse($tl->tanggal)->translatedFormat('d M Y') }}
                                @if($tl->waktu)
                                    <span class="text-gray-400 font-normal">· {{ substr($tl->waktu, 0, 5) }} WIB</span>
                                @endif
                            </strong>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                            <span class="text-gray-400 text-[10px] block font-heading font-semibold">Jenis & Tempat</span>
                            <strong class="text-gray-800 font-heading block mt-0.5 text-xs">{{ $tl->jenis_konseling ?? '-' }}</strong>
                            <span class="text-[10px] text-gray-500">{{ $tl->tempat_media ?? 'Ruang BK' }}</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                            <span class="text-gray-400 text-[10px] block font-heading font-semibold">Guru Pendamping</span>
                            <strong class="text-gray-800 font-heading block mt-0.5 text-xs">
                                {{ $tl->guru?->nama_guru ?? 'Belum Ditentukan' }}
                            </strong>
                            <span class="text-[10px] text-gray-400">Pencatat: {{ $tl->user?->username ?? '-' }}</span>
                        </div>
                        <div class="p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                            <span class="text-gray-400 text-[10px] block font-heading font-semibold">Evaluasi</span>
                            <strong class="text-gray-800 font-heading block mt-0.5 text-xs">
                                {{ $tl->tanggal_evaluasi ? \Carbon\Carbon::parse($tl->tanggal_evaluasi)->translatedFormat('d M Y') : 'Belum Dijadwalkan' }}
                            </strong>
                            <span class="text-[10px] text-gray-500">{{ $tl->skor_efektivitas ?? 'Belum Dievaluasi' }}</span>
                        </div>
                    </div>

                    {{-- Catatan Konseling --}}
                    @if($tl->catatan_konseling)
                        <div class="mt-3 pt-3 border-t border-gray-100 text-xs">
                            <span class="font-bold text-gray-700 block mb-1 font-heading">Catatan Konseling:</span>
                            <p class="text-gray-600 bg-gray-50 px-3 py-2 rounded-lg border border-gray-100 leading-relaxed italic">
                                "{{ $tl->catatan_konseling }}"
                            </p>
                        </div>
                    @endif
                    @if($tl->hasil_evaluasi)
                        <div class="mt-2 text-xs">
                            <p class="text-gray-700 bg-emerald-50/60 px-3 py-2 rounded-lg border border-emerald-200 leading-relaxed">
                                <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i>
                                <strong>Hasil:</strong> {{ $tl->hasil_evaluasi }}
                            </p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

</div>
@endsection
