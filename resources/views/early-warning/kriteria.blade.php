@extends('layouts.app')

@section('title', 'Kelola Kriteria Analisis EWS')
@section('page_title', 'Kriteria Analisis EWS')
@section('page_subtitle', 'Dashboard > Early Warning > Kelola Kriteria')

@section('content')
<div class="mb-6 flex items-center justify-between font-heading">
    <div>
        <h2 class="text-xl font-black text-gray-900">Konfigurasi Parameter dan Ambang Batas EWS</h2>
        <p class="text-xs text-gray-500 mt-1">Sesuaikan nilai ambang batas, bobot risiko, dan status aktif untuk tiap kriteria peringatan dini.</p>
    </div>
    <a href="{{ route('operator.early-warning') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2.5 px-4 rounded-xl flex items-center space-x-2 transition">
        <i class="fa-solid fa-arrow-left"></i>
        <span>Kembali ke Early Warning</span>
    </a>
</div>

<div class="grid grid-cols-1 gap-6 font-heading">
    @foreach($kriteriaList as $kriteria)
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100">
        <form action="{{ route('operator.early-warning.kriteria.update', $kriteria->id_kriteria) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="flex flex-wrap items-center justify-between border-b border-gray-100 pb-4 mb-4 gap-4">
                <div class="flex items-center space-x-3">
                    <span class="w-8 h-8 rounded-xl bg-orange-100 text-edu-orange font-black flex items-center justify-center text-sm">
                        #{{ $kriteria->id_kriteria }}
                    </span>
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ $kriteria->nama_kriteria }}</h3>
                        <span class="text-[11px] font-semibold text-gray-400">Kode: {{ $kriteria->kode ?? 'KRITERIA_'.$kriteria->id_kriteria }} &bull; Jenis: {{ $kriteria->jenis }}</span>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="aktif" value="1" {{ $kriteria->aktif ? 'checked' : '' }} class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                        <span class="ml-2 text-xs font-bold {{ $kriteria->aktif ? 'text-emerald-600' : 'text-gray-400' }}">
                            {{ $kriteria->aktif ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nama Kriteria</label>
                    <input type="text" name="nama_kriteria" value="{{ $kriteria->nama_kriteria }}" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-edu-orange focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Nilai Ambang Batas</label>
                    <div class="flex items-center space-x-2">
                        <input type="number" step="0.01" name="nilai_ambang" value="{{ $kriteria->nilai_ambang }}" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-edu-orange focus:outline-none">
                        <span class="text-xs text-gray-500 font-bold whitespace-nowrap">{{ $kriteria->satuan ?? 'poin' }}</span>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Bobot Risiko (0.1 - 5.0)</label>
                    <input type="number" step="0.05" name="bobot" value="{{ $kriteria->bobot ?? 1.00 }}" required class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-edu-orange focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-700 mb-1">Deskripsi Parameter</label>
                    <input type="text" name="parameter" value="{{ $kriteria->parameter }}" class="w-full bg-gray-50 border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold text-gray-800 focus:ring-2 focus:ring-edu-orange focus:outline-none">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="submit" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold py-2 px-5 rounded-xl shadow transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-floppy-disk"></i>
                    <span>Simpan Perubahan</span>
                </button>
            </div>
        </form>
    </div>
    @endforeach
</div>
@endsection
