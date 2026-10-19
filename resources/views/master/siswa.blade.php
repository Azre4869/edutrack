@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page_title', 'Data')
@php
    $roleName = auth()->user()->role ?? 'Operator';
    $dashUrl = $roleName === 'Operator' ? route('operator.dashboard') : ($roleName === 'Wali Kelas' ? route('wali.dashboard') : route('guru.dashboard'));
    $dataUrl = $roleName === 'Operator' ? route('operator.siswa') : ($roleName === 'Wali Kelas' ? route('wali.siswa') : route('guru.siswa'));
@endphp
@section('breadcrumb')
    <a href="{{ $dashUrl }}" class="text-blue-100 hover:text-white hover:underline transition-colors font-medium">Dashboard</a>
    <i class="fa-solid fa-chevron-right text-[10px] mx-1.5 text-blue-300"></i>
    <a href="{{ $dataUrl }}" class="text-blue-100 hover:text-white hover:underline transition-colors font-medium">Data</a>
    <i class="fa-solid fa-chevron-right text-[10px] mx-1.5 text-blue-300"></i>
    <span class="text-white font-bold tracking-wide">Siswa</span>
@endsection

@section('content')
<!-- CONTROL / FILTER BAR -->
<div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <!-- Dropdown Per Page -->
    <div class="flex items-center space-x-3">
        <div class="relative">
            <select onchange="window.location.href='?per_page='+this.value" class="appearance-none bg-white border border-gray-200 text-gray-800 text-xs font-bold font-heading py-2.5 pl-4 pr-10 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-edu-orange">
                <option value="21" {{ request('per_page') == 21 ? 'selected' : '' }}>Tampilkan 1 - 21</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>Tampilkan 1 - 50</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>Tampilkan 1 - 100</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-500">
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
        </div>
    </div>

    <!-- Right Controls: Export Excel & Tambah Siswa -->
    <div class="flex items-center space-x-3">
        <button onclick="exportTableToExcel('.excel-table', 'Data_Siswa')" class="bg-white hover:bg-gray-50 text-gray-800 text-xs font-bold font-heading py-2.5 px-4 rounded-xl shadow-sm border border-gray-200 flex items-center space-x-2 transition">
            <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i>
            <span>Export Excel</span>
        </button>
        @if(auth()->user()->role === 'Operator')
        <button onclick="document.getElementById('modalTambahSiswa').classList.remove('hidden')" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold font-heading py-2.5 px-5 rounded-xl shadow-md flex items-center space-x-2 transition">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Siswa</span>
        </button>
        @endif
    </div>
</div>

<!-- SEARCH BAR & FILTER BUTTON -->
<div class="mb-5 flex items-center gap-3">
    <form action="{{ url()->current() }}" method="GET" class="flex-1 flex items-center bg-white rounded-xl shadow-sm px-4 py-2.5 border border-gray-200">
        <i class="fa-solid fa-magnifying-glass text-edu-orange mr-3"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, atau kelas......" class="w-full text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none font-heading">
        @if(request('search'))
            <a href="{{ url()->current() }}" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fa-solid fa-xmark"></i></a>
        @endif
    </form>

    <button onclick="document.getElementById('filterModal').classList.toggle('hidden')" class="bg-white hover:bg-gray-50 text-gray-800 text-xs font-bold font-heading py-3 px-6 rounded-xl shadow-sm border border-gray-200 flex items-center space-x-2 transition">
        <span>Filter</span>
        <i class="fa-solid fa-filter text-edu-orange"></i>
    </button>
</div>

<!-- EXCEL / SPREADSHEET TABLE CONTAINER -->
<div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
    <div class="overflow-x-auto w-full max-h-[640px] pb-2">
        <table class="w-full min-w-[1350px] excel-table border-collapse text-xs text-left">
            <thead>
                <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                    <th class="p-3 w-12 border border-black">No.</th>
                    <th class="p-3 w-24 border border-black">NIS</th>
                    <th class="p-3 min-w-[200px] border border-black">NAMA</th>
                    <th class="p-3 w-14 border border-black">L/P</th>
                    <th class="p-3 min-w-[120px] border border-black">Tempat Lahir</th>
                    <th class="p-3 min-w-[120px] border border-black">Tanggal Lahir</th>
                    <th class="p-3 min-w-[100px] border border-black">Agama</th>
                    <th class="p-3 min-w-[90px] border border-black">Status</th>
                    <th class="p-3 w-20 border border-black">Anak ke</th>
                    <th class="p-3 min-w-[180px] border border-black">Alamat Siswa</th>
                    <th class="p-3 min-w-[140px] border border-black">No.Telepon Rumah</th>
                    <th class="p-3 min-w-[140px] border border-black">Sekolah Asal</th>
                    <th class="p-3 w-28 border border-black">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black bg-white font-body">
                @forelse($siswa as $idx => $s)
                    <tr class="hover:bg-blue-50/40 transition">
                        <td class="p-2.5 text-center font-semibold border border-black">{{ $siswa->firstItem() + $idx }}.</td>
                        <td class="p-2.5 text-center border border-black font-medium">{{ $s->nis }}</td>
                        <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $s->nama }}</td>
                        <td class="p-2.5 text-center border border-black">{{ $s->jenis_kelamin }}</td>
                        <td class="p-2.5 border border-black">Sampang</td>
                        <td class="p-2.5 text-center border border-black">27 Juli 2011</td>
                        <td class="p-2.5 text-center border border-black">Islam</td>
                        <td class="p-2.5 text-center border border-black">Anak Kandung</td>
                        <td class="p-2.5 text-center border border-black">1</td>
                        <td class="p-2.5 border border-black">Jl. Melati No. 12, Sampang</td>
                        <td class="p-2.5 text-center border border-black">083445699635</td>
                        <td class="p-2.5 border border-black">SDN Sampang 1</td>
                        <td class="p-2.5 text-center border border-black">
                            <div class="flex items-center justify-center space-x-1.5">
                                <button onclick="alert('Detail Siswa: {{ $s->nama }} (NIS: {{ $s->nis }})')" title="Lihat" class="w-6 h-6 rounded bg-edu-blue hover:bg-blue-700 text-white flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @if(auth()->user()->role === 'Operator')
                                <button onclick="editSiswa({{ $s->id_siswa }}, '{{ $s->nis }}', '{{ addslashes($s->nama) }}', '{{ $s->jenis_kelamin }}', {{ $s->id_kelas }})" title="Edit" class="w-6 h-6 rounded bg-amber-400 hover:bg-amber-500 text-white flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('operator.siswa.destroy', $s->id_siswa) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus siswa ini?')" class="inline">
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
                        <td colspan="13" class="p-8 text-center text-gray-500 border border-black">Tidak ada data siswa ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500 font-heading">
            Menampilkan {{ $siswa->firstItem() ?? 0 }} sampai {{ $siswa->lastItem() ?? 0 }} dari {{ $siswa->total() }} siswa
        </p>
        <div>
            {{ $siswa->links() }}
        </div>
    </div>
</div>

<!-- MODAL TAMBAH SISWA (OPERATOR) -->
<div id="modalTambahSiswa" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-black text-gray-900">Tambah Data Siswa</h3>
            <button onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('operator.siswa.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">NIS *</label>
                <input type="text" name="nis" required placeholder="Contoh: 16450" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap Siswa *</label>
                <input type="text" name="nama" required placeholder="Nama lengkap siswa" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Jenis Kelamin *</label>
                <select name="jenis_kelamin" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Kelas *</label>
                <select name="id_kelas" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}">{{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition">
                    Simpan Siswa
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT SISWA -->
<div id="modalEditSiswa" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-black text-gray-900">Edit Data Siswa</h3>
            <button onclick="document.getElementById('modalEditSiswa').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formEditSiswa" method="POST" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">NIS *</label>
                <input type="text" id="edit_nis" name="nis" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Lengkap Siswa *</label>
                <input type="text" id="edit_nama" name="nama" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Jenis Kelamin *</label>
                <select id="edit_jk" name="jenis_kelamin" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="L">Laki-laki (L)</option>
                    <option value="P">Perempuan (P)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Kelas *</label>
                <select id="edit_kelas" name="id_kelas" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}">{{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalEditSiswa').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
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
    function editSiswa(id, nis, nama, jk, kelasId) {
        document.getElementById('edit_nis').value = nis;
        document.getElementById('edit_nama').value = nama;
        document.getElementById('edit_jk').value = jk;
        document.getElementById('edit_kelas').value = kelasId;
        document.getElementById('formEditSiswa').action = '/operator/siswa/' + id;
        document.getElementById('modalEditSiswa').classList.remove('hidden');
    }
</script>
@endsection
