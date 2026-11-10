@extends('layouts.app')

@section('title', 'Data Kelas')
@section('page_title', 'Data')
@section('page_subtitle', 'Dashboard > Data > Kelas')

@section('content')
<!-- CONTROL / FILTER BAR -->
<div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <!-- Dropdown Per Page -->
    <div class="flex items-center space-x-3">
        <div class="relative w-full sm:w-auto">
            <select onchange="window.location.href='?per_page='+this.value" class="w-full sm:w-auto appearance-none bg-white border border-gray-200 text-gray-800 text-xs font-bold font-heading py-2.5 pl-4 pr-10 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-edu-orange cursor-pointer">
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>Tampilkan 1 - 50</option>
                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>Tampilkan 1 - 20</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-500">
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
        </div>
    </div>

    <!-- Right Controls: Export + Tambah Kelas -->
    <div class="flex items-center flex-wrap gap-2.5">
        <button onclick="exportTableToExcel('.excel-table', 'Data_Kelas')" class="flex-1 sm:flex-none justify-center bg-white hover:bg-gray-50 text-gray-800 text-xs font-bold font-heading py-2.5 px-4 rounded-xl shadow-sm border border-gray-200 flex items-center space-x-2 transition cursor-pointer">
            <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i>
            <span>Export Excel</span>
        </button>
        @if(auth()->user()->role === 'Operator')
        <button onclick="document.getElementById('modalTambahKelas').classList.remove('hidden')" class="flex-1 sm:flex-none justify-center bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold font-heading py-2.5 px-5 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Kelas</span>
        </button>
        @endif
    </div>
</div>

<!-- SEARCH BAR & FILTER BUTTON -->
<div class="mb-5 flex items-center gap-3">
    <form action="{{ url()->current() }}" method="GET" class="flex-1 flex items-center bg-white rounded-xl shadow-sm px-4 py-2.5 border border-gray-200">
        <i class="fa-solid fa-magnifying-glass text-edu-orange mr-3"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kelas......" class="w-full text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none font-heading">
        @if(request('search'))
            <a href="{{ url()->current() }}" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fa-solid fa-xmark"></i></a>
        @endif
    </form>
</div>

<!-- Petunjuk Geser Tabel Mobile -->
<div class="block lg:hidden mb-2 text-[11px] text-blue-100 font-medium text-right">
    <i class="fa-solid fa-arrows-left-right mr-1"></i>Geser tabel untuk melihat kolom lainnya &rarr;
</div>

<!-- EXCEL / SPREADSHEET TABLE CONTAINER -->
<div class="bg-white rounded-3xl p-3 sm:p-4 shadow-xl border border-gray-200 overflow-hidden">
    <div class="overflow-x-auto max-h-[640px]">
        <table class="w-full excel-table border-collapse text-xs text-left">
            <thead>
                <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                    <th class="p-3 w-14 border border-black">No.</th>
                    <th class="p-3 min-w-[160px] border border-black">Kelas</th>
                    <th class="p-3 min-w-[140px] border border-black">Tahun Ajaran</th>
                    <th class="p-3 min-w-[220px] border border-black">Wali Kelas</th>
                    <th class="p-3 w-32 border border-black">Jumlah Siswa</th>
                    <th class="p-3 w-28 border border-black">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black bg-white font-body">
                @forelse($kelas as $idx => $k)
                    <tr class="hover:bg-blue-50/40 transition">
                        <td class="p-2.5 text-center font-semibold border border-black">{{ $kelas->firstItem() + $idx }}.</td>
                        <td class="p-2.5 text-center font-bold text-gray-900 border border-black">{{ $k->nama_kelas }}</td>
                        <td class="p-2.5 text-center border border-black">{{ $k->tahun_ajaran }}</td>
                        <td class="p-2.5 border border-black font-semibold">{{ $k->waliKelas ? $k->waliKelas->nama_guru : 'Belum Ditentukan' }}</td>
                        <td class="p-2.5 text-center font-bold text-edu-orange border border-black">{{ $k->siswa_count }}</td>
                        <td class="p-2.5 text-center border border-black">
                            <div class="flex items-center justify-center space-x-1.5">
                                <button onclick="showDetailKelas('{{ addslashes($k->nama_kelas) }}', {{ $k->tingkat }}, '{{ $k->tahun_ajaran }}', '{{ addslashes($k->waliKelas ? $k->waliKelas->nama_guru : 'Belum Ditentukan') }}', '{{ $k->waliKelas ? $k->waliKelas->nip : '-' }}', {{ $k->siswa_count }})" title="Lihat Detail" class="w-6 h-6 rounded bg-edu-blue hover:bg-blue-700 text-white flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @if(auth()->user()->role === 'Operator')
                                <button onclick="editKelas({{ $k->id_kelas }}, '{{ $k->nama_kelas }}', {{ $k->tingkat }}, '{{ $k->tahun_ajaran }}', '{{ $k->id_wali_kelas }}')" title="Edit" class="w-6 h-6 rounded bg-amber-400 hover:bg-amber-500 text-white flex items-center justify-center text-[10px] transition">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('operator.kelas.destroy', $k->id_kelas) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kelas ini?')" class="inline">
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
                        <td colspan="6" class="p-8 text-center text-gray-500 border border-black">Tidak ada data kelas ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-500 font-heading">
            Menampilkan {{ $kelas->firstItem() ?? 0 }} sampai {{ $kelas->lastItem() ?? 0 }} dari {{ $kelas->total() }} kelas
        </p>
        <div>
            {{ $kelas->links() }}
        </div>
    </div>
</div>

<!-- MODAL TAMBAH KELAS -->
<div id="modalTambahKelas" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-black text-gray-900">Tambah Data Kelas</h3>
            <button onclick="document.getElementById('modalTambahKelas').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('operator.kelas.store') }}" method="POST" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Kelas *</label>
                <input type="text" name="nama_kelas" required placeholder="Contoh: VII B" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Tingkat *</label>
                <select name="tingkat" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="7">Kelas 7 (VII)</option>
                    <option value="8">Kelas 8 (VIII)</option>
                    <option value="9">Kelas 9 (IX)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Tahun Ajaran *</label>
                <input type="text" name="tahun_ajaran" value="{{ old('tahun_ajaran', \App\Models\Semester::aktif()?->tahun_ajaran ?? '') }}" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Wali Kelas</label>
                <select name="id_wali_kelas" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="">-- Pilih Guru Wali Kelas --</option>
                    @foreach($guruList as $g)
                        <option value="{{ $g->id_guru }}">{{ $g->nama_guru }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalTambahKelas').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition">
                    Simpan Kelas
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT KELAS -->
<div id="modalEditKelas" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <h3 class="text-lg font-black text-gray-900">Edit Data Kelas</h3>
            <button onclick="document.getElementById('modalEditKelas').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formEditKelas" method="POST" class="mt-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Kelas *</label>
                <input type="text" id="edit_nama_kelas" name="nama_kelas" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Tingkat *</label>
                <select id="edit_tingkat" name="tingkat" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="7">Kelas 7 (VII)</option>
                    <option value="8">Kelas 8 (VIII)</option>
                    <option value="9">Kelas 9 (IX)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Tahun Ajaran *</label>
                <input type="text" id="edit_tahun_ajaran" name="tahun_ajaran" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Wali Kelas</label>
                <select id="edit_wali_kelas" name="id_wali_kelas" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="">-- Pilih Guru Wali Kelas --</option>
                    @foreach($guruList as $g)
                        <option value="{{ $g->id_guru }}">{{ $g->nama_guru }}</option>
                    @endforeach
                </select>
            </div>

            <div class="pt-4 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalEditKelas').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DETAIL KELAS -->
<div id="modalDetailKelas" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-7 max-w-md w-full shadow-2xl border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-2xl bg-blue-100 text-edu-blue flex items-center justify-center text-lg">
                    <i class="fa-solid fa-chalkboard"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-900" id="detail_nama_kelas">-</h3>
                    <p class="text-xs text-gray-500 font-body" id="detail_tingkat_ta">-</p>
                </div>
            </div>
            <button onclick="document.getElementById('modalDetailKelas').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-5 space-y-3 font-body text-xs">
            <div class="bg-blue-50/50 p-3.5 rounded-2xl border border-blue-100">
                <span class="text-xs font-bold text-edu-blue block mb-1">Wali Kelas</span>
                <p id="detail_wali_nama" class="text-sm font-black text-gray-900">-</p>
                <p id="detail_wali_nip" class="text-[11px] text-gray-500 mt-0.5">NIP: -</p>
            </div>
            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100 flex justify-between items-center">
                <span class="text-gray-500 font-medium">Jumlah Peserta Didik</span>
                <span id="detail_jumlah_siswa" class="font-bold text-edu-orange bg-white px-3 py-1 rounded-lg border border-gray-200">- Siswa</span>
            </div>
            <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-100 flex justify-between items-center">
                <span class="text-gray-500 font-medium">Status Rombel</span>
                <span class="font-bold text-emerald-700 bg-emerald-100 px-2.5 py-1 rounded-lg">Aktif Berjalan</span>
            </div>
        </div>

        <div class="pt-5 flex justify-end">
            <button type="button" onclick="document.getElementById('modalDetailKelas').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition">
                Tutup
            </button>
        </div>
    </div>
</div>

<script>
    function showDetailKelas(nama, tingkat, ta, waliNama, waliNip, siswaCount) {
        document.getElementById('detail_nama_kelas').innerText = 'Kelas ' + nama;
        document.getElementById('detail_tingkat_ta').innerText = 'Tingkat ' + tingkat + ' • Tahun Ajaran ' + ta;
        document.getElementById('detail_wali_nama').innerText = waliNama;
        document.getElementById('detail_wali_nip').innerText = waliNip && waliNip !== '-' ? 'NIP: ' + waliNip : 'NIP: -';
        document.getElementById('detail_jumlah_siswa').innerText = siswaCount + ' Siswa';
        document.getElementById('modalDetailKelas').classList.remove('hidden');
    }

    function editKelas(id, nama, tingkat, ta, waliId) {
        document.getElementById('edit_nama_kelas').value = nama;
        document.getElementById('edit_tingkat').value = tingkat;
        document.getElementById('edit_tahun_ajaran').value = ta;
        document.getElementById('edit_wali_kelas').value = waliId || '';
        document.getElementById('formEditKelas').action = '/operator/kelas/' + id;
        document.getElementById('modalEditKelas').classList.remove('hidden');
    }
</script>
@endsection
