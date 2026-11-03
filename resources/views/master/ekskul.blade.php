@extends('layouts.app')

@section('title', 'Data Ekstrakurikuler')
@section('page_title', 'Data')
@php
    $dashUrl = auth()->user()->role === 'Operator' ? route('operator.dashboard') : (auth()->user()->role === 'Wali Kelas' ? route('wali.dashboard') : route('guru.dashboard'));
@endphp
@section('breadcrumb')
    <a href="{{ $dashUrl }}" class="hover:text-white transition-colors">Dashboard</a>
    <span class="mx-1 text-blue-300">&rsaquo;</span>
    <span class="text-blue-200">Data</span>
    <span class="mx-1 text-blue-300">&rsaquo;</span>
    <span class="text-white font-bold">Ekstrakulikuler</span>
@endsection

@section('content')
<!-- CONTROL / FILTER BAR -->
<div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center space-x-3">
        <div class="relative">
            <select onchange="window.location.href='?per_page='+this.value" class="appearance-none bg-white border border-gray-200 text-gray-800 text-xs font-bold font-heading py-2.5 pl-4 pr-10 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-edu-orange">
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>Tampilkan 1 - 50</option>
                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>Tampilkan 1 - 20</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-500">
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
        </div>
    </div>

    <div class="flex items-center space-x-3">
        <button onclick="exportTableToExcel('.excel-table', 'Data_Ekstrakulikuler')" class="bg-white hover:bg-gray-50 text-gray-800 text-xs font-bold font-heading py-2.5 px-4 rounded-xl shadow-sm border border-gray-200 flex items-center space-x-2 transition">
            <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i>
            <span>Export Excel</span>
        </button>
        @if(auth()->user()->role === 'Operator')
        <button onclick="document.getElementById('modalTambahEkskul').classList.remove('hidden')" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold font-heading py-2.5 px-5 rounded-xl shadow-md flex items-center space-x-2 transition">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Ekstrakulikuler</span>
        </button>
        @endif
    </div>
</div>

<!-- SEARCH BAR & FILTER BUTTON -->
<div class="mb-5 flex items-center gap-3">
    <form action="{{ url()->current() }}" method="GET" class="flex-1 flex items-center bg-white rounded-xl shadow-sm px-4 py-2.5 border border-gray-200">
        <i class="fa-solid fa-magnifying-glass text-edu-orange mr-3"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kegiatan ekstrakulikuler......" class="w-full text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none font-heading">
        @if(request('search'))
            <a href="{{ url()->current() }}" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fa-solid fa-xmark"></i></a>
        @endif
    </form>

    <button class="bg-white hover:bg-gray-50 text-gray-800 text-xs font-bold font-heading py-3 px-6 rounded-xl shadow-sm border border-gray-200 flex items-center space-x-2 transition">
        <span>Filter</span>
        <i class="fa-solid fa-filter text-edu-orange"></i>
    </button>
</div>

<!-- EXCEL TABLE CONTAINER -->
<div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
    <div class="overflow-x-auto w-full max-h-[640px] pb-2">
        <table class="w-full min-w-[900px] excel-table border-collapse text-xs text-left">
            <thead>
                <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                    <th class="p-3 w-14 border border-black">No.</th>
                    <th class="p-3 min-w-[240px] border border-black">Nama Ekstrakulikuler</th>
                    <th class="p-3 min-w-[200px] border border-black">Guru Pembina</th>
                    <th class="p-3 min-w-[160px] border border-black">Jadwal Latihan</th>
                    <th class="p-3 w-28 border border-black">Status</th>
                    <th class="p-3 w-28 border border-black">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black bg-white font-body">
                @forelse($ekskul as $idx => $e)
                    <tr class="hover:bg-blue-50/40 transition">
                        <td class="p-2.5 text-center font-semibold border border-black">{{ $ekskul->firstItem() + $idx }}.</td>
                        <td class="p-2.5 font-bold text-gray-900 border border-black flex items-center space-x-2">
                            <span class="w-6 h-6 rounded bg-orange-100 text-edu-orange flex items-center justify-center font-bold text-[10px]">
                                <i class="fa-solid fa-campground"></i>
                            </span>
                            <span>{{ $e->nama_ekskul }}</span>
                        </td>
                        <td class="p-2.5 border border-black font-semibold">Agus Supriyanto, S.Pd.</td>
                        <td class="p-2.5 text-center border border-black">Jumat, 14.30 - 16.30 WIB</td>
                        <td class="p-2.5 text-center border border-black">
                            <span class="inline-block px-3 py-1 text-[11px] font-bold rounded-full bg-emerald-100 text-emerald-700">
                                Aktif
                            </span>
                        </td>
                        <td class="p-2.5 text-center border border-black">
                            <div class="flex items-center justify-center space-x-1.5">
                                <button onclick="alert('Detail Ekskul: {{ $e->nama_ekskul }}')" title="Lihat" class="w-6 h-6 rounded bg-edu-blue hover:bg-blue-700 text-white flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @if(auth()->user()->role === 'Operator')
                                <button onclick="editEkskul({{ $e->id_ekskul }}, '{{ addslashes($e->nama_ekskul) }}')" title="Edit" class="w-6 h-6 rounded bg-amber-400 hover:bg-amber-500 text-white flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('operator.ekskul.destroy', $e->id_ekskul) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus ekstrakulikuler ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="w-6 h-6 rounded bg-red-500 hover:bg-red-600 text-white flex items-center justify-center text-[10px] transition">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-500 border border-black">Tidak ada data ekstrakulikuler ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500 font-heading">
            Menampilkan {{ $ekskul->firstItem() ?? 0 }} sampai {{ $ekskul->lastItem() ?? 0 }} dari {{ $ekskul->total() }} ekstrakulikuler
        </p>
        <div>
            {{ $ekskul->links() }}
        </div>
    </div>
</div>

<!-- MODAL TAMBAH EKSKUL -->
<div id="modalTambahEkskul" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-black text-gray-900">Tambah Ekstrakulikuler</h3>
            <button onclick="document.getElementById('modalTambahEkskul').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('operator.ekskul.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Kegiatan Ekstrakulikuler *</label>
                <input type="text" name="nama_ekskul" required placeholder="Contoh: Robotika & Coding" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalTambahEkskul').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition">
                    Simpan Ekstrakulikuler
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT EKSKUL -->
<div id="modalEditEkskul" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-black text-gray-900">Edit Ekstrakulikuler</h3>
            <button onclick="document.getElementById('modalEditEkskul').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formEditEkskul" method="POST" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Kegiatan Ekstrakulikuler *</label>
                <input type="text" id="edit_nama_ekskul" name="nama_ekskul" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalEditEkskul').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function editEkskul(id, nama) {
        document.getElementById('edit_nama_ekskul').value = nama;
        document.getElementById('formEditEkskul').action = '/operator/ekskul/' + id;
        document.getElementById('modalEditEkskul').classList.remove('hidden');
    }
</script>
@endsection
