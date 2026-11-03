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
@if(session('success'))
    <div class="mb-4 bg-emerald-50 border border-emerald-300 text-emerald-800 px-5 py-3 rounded-2xl text-xs font-semibold flex items-center justify-between shadow-sm font-heading">
        <div class="flex items-center space-x-2">
            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 cursor-pointer">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
@endif

<!-- CONTROL / FILTER BAR -->
<div class="mb-4 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <!-- Dropdown Per Page -->
    <div class="flex items-center space-x-3">
        <div class="relative">
            <select onchange="window.location.href='?per_page='+this.value" class="appearance-none bg-white border border-gray-200 text-gray-800 text-xs font-bold font-heading py-2.5 pl-4 pr-10 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-edu-orange cursor-pointer">
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
        <button onclick="exportTableToExcel('.excel-table', 'Data_Siswa')" class="bg-white hover:bg-gray-50 text-gray-800 text-xs font-bold font-heading py-2.5 px-4 rounded-xl shadow-sm border border-gray-200 flex items-center space-x-2 transition cursor-pointer">
            <i class="fa-solid fa-file-excel text-emerald-600 text-sm"></i>
            <span>Export Excel</span>
        </button>
        @if(auth()->user()->role === 'Operator')
        <button onclick="document.getElementById('modalTambahSiswa').classList.remove('hidden')" class="bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold font-heading py-2.5 px-5 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Siswa</span>
        </button>
        @endif
    </div>
</div>

<!-- SEARCH BAR -->
<div class="mb-5 flex items-center gap-3">
    <form action="{{ url()->current() }}" method="GET" class="flex-1 flex items-center bg-white rounded-xl shadow-sm px-4 py-2.5 border border-gray-200">
        <i class="fa-solid fa-magnifying-glass text-edu-orange mr-3"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama, NIS, atau kelas..." class="w-full text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none font-heading">
        @if(request('search'))
            <a href="{{ url()->current() }}" class="text-gray-400 hover:text-gray-600 text-xs"><i class="fa-solid fa-xmark"></i></a>
        @endif
    </form>
</div>

<!-- EXCEL / SPREADSHEET TABLE CONTAINER -->
<div class="bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
    <div class="overflow-x-auto w-full max-h-[640px] pb-2">
        <table class="w-full min-w-[1350px] excel-table border-collapse text-xs text-left">
            <thead>
                <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                    <th class="p-3 w-12 border border-black">No.</th>
                    <th class="p-3 w-28 border border-black">NIS / NISN</th>
                    <th class="p-3 min-w-[200px] border border-black">NAMA</th>
                    <th class="p-3 w-14 border border-black">L/P</th>
                    <th class="p-3 min-w-[120px] border border-black">Tempat Lahir</th>
                    <th class="p-3 min-w-[120px] border border-black">Tanggal Lahir</th>
                    <th class="p-3 min-w-[90px] border border-black">Agama</th>
                    <th class="p-3 min-w-[100px] border border-black">Status Keluarga</th>
                    <th class="p-3 w-20 border border-black">Anak ke</th>
                    <th class="p-3 min-w-[180px] border border-black">Alamat Siswa</th>
                    <th class="p-3 min-w-[130px] border border-black">No. Telepon</th>
                    <th class="p-3 min-w-[130px] border border-black">Sekolah Asal</th>
                    <th class="p-3 w-28 border border-black">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black bg-white font-body">
                @forelse($siswa as $idx => $s)
                    <tr class="hover:bg-blue-50/40 transition">
                        <td class="p-2.5 text-center font-semibold border border-black">{{ $siswa->firstItem() + $idx }}.</td>
                        <td class="p-2.5 text-center border border-black font-medium">
                            <span class="font-bold">{{ $s->nis }}</span>
                            @if($s->nisn)
                                <div class="text-[10px] text-gray-500 font-mono">{{ $s->nisn }}</div>
                            @endif
                        </td>
                        <td class="p-2.5 font-bold text-gray-900 border border-black">{{ strtoupper($s->nama) }}</td>
                        <td class="p-2.5 text-center border border-black">{{ $s->jenis_kelamin }}</td>
                        <td class="p-2.5 border border-black">{{ $s->tempat_lahir ?? '-' }}</td>
                        <td class="p-2.5 text-center border border-black">{{ isset($s->tanggal_lahir) ? \Carbon\Carbon::parse($s->tanggal_lahir)->isoFormat('D MMMM Y') : '-' }}</td>
                        <td class="p-2.5 text-center border border-black">{{ $s->agama ?? '-' }}</td>
                        <td class="p-2.5 text-center border border-black">{{ $s->status_keluarga ?? ($s->status_dalam_keluarga ?? '-') }}</td>
                        <td class="p-2.5 text-center border border-black">{{ $s->anak_ke ?? '-' }}</td>
                        <td class="p-2.5 border border-black">{{ $s->alamat ?? '-' }}</td>
                        <td class="p-2.5 text-center border border-black font-mono text-[11px]">{{ $s->no_telp ?? ($s->telp_ortu ?? '-') }}</td>
                        <td class="p-2.5 border border-black">{{ $s->sekolah_asal ?? '-' }}</td>
                        <td class="p-2.5 text-center border border-black">
                            <div class="flex items-center justify-center space-x-1.5">
                                <button onclick="lihatSiswa({{ json_encode($s) }})" title="Lihat Profil Biodata Lengkap" class="w-6 h-6 rounded bg-edu-blue hover:bg-blue-700 text-white flex items-center justify-center text-[10px] transition cursor-pointer">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                @if(auth()->user()->role === 'Operator')
                                <button onclick="editSiswa({{ json_encode($s) }})" title="Edit Data Siswa" class="w-6 h-6 rounded bg-amber-400 hover:bg-amber-500 text-white flex items-center justify-center text-[10px] transition cursor-pointer">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <form action="{{ route('operator.siswa.destroy', $s->id_siswa) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus siswa ini?')" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Hapus" class="w-6 h-6 rounded bg-red-500 hover:bg-red-600 text-white flex items-center justify-center text-[10px] transition cursor-pointer">
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

<!-- ==================== MODAL DETAIL SISWA ==================== -->
<div id="modalDetailSiswa" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl border border-gray-100 font-heading max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <div>
                <h3 class="text-base font-black text-gray-900" id="detail_nama">Profil Siswa</h3>
                <p class="text-xs text-edu-blue font-bold" id="detail_nis_nisn">NIS: - | NISN: -</p>
            </div>
            <button onclick="document.getElementById('modalDetailSiswa').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="mt-4 space-y-4 text-xs font-body">
            <!-- Data Diri -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="font-bold text-gray-800 font-heading mb-2.5 pb-1 border-b border-gray-200 text-xs uppercase tracking-wider text-edu-blue">Keterangan Diri Siswa</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    <div><span class="text-gray-500">Jenis Kelamin:</span> <span class="font-semibold" id="dt_jk">-</span></div>
                    <div><span class="text-gray-500">Tempat, Tgl Lahir:</span> <span class="font-semibold" id="dt_ttl">-</span></div>
                    <div><span class="text-gray-500">Agama:</span> <span class="font-semibold" id="dt_agama">-</span></div>
                    <div><span class="text-gray-500">Status dlm Keluarga:</span> <span class="font-semibold" id="dt_status">-</span></div>
                    <div><span class="text-gray-500">Anak ke-:</span> <span class="font-semibold" id="dt_anak_ke">-</span></div>
                    <div><span class="text-gray-500">Sekolah Asal:</span> <span class="font-semibold" id="dt_sekolah_asal">-</span></div>
                    <div><span class="text-gray-500">Diterima di Kelas:</span> <span class="font-semibold" id="dt_kelas_diterima">-</span></div>
                    <div><span class="text-gray-500">Tanggal Diterima:</span> <span class="font-semibold" id="dt_tgl_diterima">-</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">Alamat Tempat Tinggal:</span> <span class="font-semibold" id="dt_alamat">-</span></div>
                </div>
            </div>

            <!-- Data Orang Tua & Wali -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="font-bold text-gray-800 font-heading mb-2.5 pb-1 border-b border-gray-200 text-xs uppercase tracking-wider text-edu-blue">Orang Tua / Wali</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                    <div><span class="text-gray-500">Nama Ayah:</span> <span class="font-semibold" id="dt_nama_ayah">-</span></div>
                    <div><span class="text-gray-500">Pekerjaan Ayah:</span> <span class="font-semibold" id="dt_pek_ayah">-</span></div>
                    <div><span class="text-gray-500">Nama Ibu:</span> <span class="font-semibold" id="dt_nama_ibu">-</span></div>
                    <div><span class="text-gray-500">Pekerjaan Ibu:</span> <span class="font-semibold" id="dt_pek_ibu">-</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">Alamat & Telp Ortu:</span> <span class="font-semibold" id="dt_alamat_telp_ortu">-</span></div>
                    <div><span class="text-gray-500">Nama Wali:</span> <span class="font-semibold" id="dt_nama_wali">-</span></div>
                    <div><span class="text-gray-500">Pekerjaan Wali:</span> <span class="font-semibold" id="dt_pek_wali">-</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500">Alamat & Telp Wali:</span> <span class="font-semibold" id="dt_alamat_telp_wali">-</span></div>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" onclick="document.getElementById('modalDetailSiswa').classList.add('hidden')" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 text-xs font-bold rounded-xl transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL TAMBAH SISWA (OPERATOR) ==================== -->
<div id="modalTambahSiswa" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl border border-gray-100 font-heading max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <h3 class="text-base font-black text-gray-900">Tambah Data Siswa & Biodata Rapor</h3>
            <button onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="{{ route('operator.siswa.store') }}" method="POST" class="mt-4 space-y-4">
            @csrf

            <!-- Section 1: Akademik & Identitas Dasar -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="text-xs font-bold text-edu-blue uppercase tracking-wider mb-3">1. Data Pokok & Kelas</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">NIS *</label>
                        <input type="text" name="nis" required placeholder="Contoh: 16450" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">NISN</label>
                        <input type="text" name="nisn" placeholder="Contoh: 0098765432" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Lengkap Siswa *</label>
                        <input type="text" name="nama" required placeholder="Nama lengkap siswa" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Jenis Kelamin *</label>
                        <select name="jenis_kelamin" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas *</label>
                        <select name="id_kelas" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                            @foreach($kelasList as $k)
                                <option value="{{ $k->id_kelas }}">{{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Keterangan Diri -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="text-xs font-bold text-edu-blue uppercase tracking-wider mb-3">2. Keterangan Diri (Halaman 2 Rapor)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" placeholder="Contoh: Sampang" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Agama</label>
                        <input type="text" name="agama" placeholder="Contoh: Islam" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Status dalam Keluarga</label>
                        <select name="status_anak" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none bg-white">
                            <option value="Anak Kandung">Anak Kandung</option>
                            <option value="Anak Angkat">Anak Angkat</option>
                            <option value="Anak Tiri">Anak Tiri</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Anak Ke-</label>
                        <input type="number" name="anak_ke" min="1" placeholder="Contoh: 1" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Sekolah Asal (SD/MI)</label>
                        <input type="text" name="sekolah_asal" placeholder="Contoh: SDN Sampang 1" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Alamat Tempat Tinggal</label>
                        <textarea name="alamat" rows="2" placeholder="Alamat lengkap siswa..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Orang Tua & Wali -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="text-xs font-bold text-edu-blue uppercase tracking-wider mb-3">3. Data Orang Tua & Wali</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Ayah</label>
                        <input type="text" name="nama_ayah" placeholder="Nama ayah kandung" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Pekerjaan Ayah</label>
                        <input type="text" name="pekerjaan_ayah" placeholder="Contoh: PNS / Wiraswasta" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Ibu</label>
                        <input type="text" name="nama_ibu" placeholder="Nama ibu kandung" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Pekerjaan Ibu</label>
                        <input type="text" name="pekerjaan_ibu" placeholder="Contoh: Ibu Rumah Tangga" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">No. Telepon / HP Rumah</label>
                        <input type="text" name="telp_ortu" placeholder="Contoh: 08123456789" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Wali (Opsional)</label>
                        <input type="text" name="nama_wali" placeholder="Kosongkan bila sama dgn ortu" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="pt-3 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalTambahSiswa').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer">
                    Simpan Siswa
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== MODAL EDIT SISWA ==================== -->
<div id="modalEditSiswa" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl border border-gray-100 font-heading max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100">
            <h3 class="text-base font-black text-gray-900">Edit Data Siswa & Biodata Rapor</h3>
            <button onclick="document.getElementById('modalEditSiswa').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="formEditSiswa" method="POST" class="mt-4 space-y-4">
            @csrf
            @method('PUT')

            <!-- Section 1: Akademik & Identitas Dasar -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="text-xs font-bold text-edu-blue uppercase tracking-wider mb-3">1. Data Pokok & Kelas</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">NIS *</label>
                        <input type="text" id="edit_nis" name="nis" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">NISN</label>
                        <input type="text" id="edit_nisn" name="nisn" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Lengkap Siswa *</label>
                        <input type="text" id="edit_nama" name="nama" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Jenis Kelamin *</label>
                        <select id="edit_jk" name="jenis_kelamin" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                            <option value="L">Laki-laki (L)</option>
                            <option value="P">Perempuan (P)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas *</label>
                        <select id="edit_kelas" name="id_kelas" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                            @foreach($kelasList as $k)
                                <option value="{{ $k->id_kelas }}">{{ $k->nama_kelas }} (Tingkat {{ $k->tingkat }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Keterangan Diri -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="text-xs font-bold text-edu-blue uppercase tracking-wider mb-3">2. Keterangan Diri (Halaman 2 Rapor)</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Tempat Lahir</label>
                        <input type="text" id="edit_tempat_lahir" name="tempat_lahir" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Tanggal Lahir</label>
                        <input type="date" id="edit_tanggal_lahir" name="tanggal_lahir" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Agama</label>
                        <input type="text" id="edit_agama" name="agama" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Status dalam Keluarga</label>
                        <select id="edit_status" name="status_anak" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none bg-white">
                            <option value="Anak Kandung">Anak Kandung</option>
                            <option value="Anak Angkat">Anak Angkat</option>
                            <option value="Anak Tiri">Anak Tiri</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Anak Ke-</label>
                        <input type="number" id="edit_anak_ke" name="anak_ke" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Sekolah Asal (SD/MI)</label>
                        <input type="text" id="edit_sekolah_asal" name="sekolah_asal" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Alamat Tempat Tinggal</label>
                        <textarea id="edit_alamat" name="alamat" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none"></textarea>
                    </div>
                </div>
            </div>

            <!-- Section 3: Orang Tua & Wali -->
            <div class="bg-gray-50 p-4 rounded-2xl border border-gray-200">
                <h4 class="text-xs font-bold text-edu-blue uppercase tracking-wider mb-3">3. Data Orang Tua & Wali</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Ayah</label>
                        <input type="text" id="edit_nama_ayah" name="nama_ayah" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Pekerjaan Ayah</label>
                        <input type="text" id="edit_pekerjaan_ayah" name="pekerjaan_ayah" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Ibu</label>
                        <input type="text" id="edit_nama_ibu" name="nama_ibu" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Pekerjaan Ibu</label>
                        <input type="text" id="edit_pekerjaan_ibu" name="pekerjaan_ibu" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">No. Telepon / HP Rumah</label>
                        <input type="text" id="edit_telp_ortu" name="telp_ortu" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Nama Wali (Opsional)</label>
                        <input type="text" id="edit_nama_wali" name="nama_wali" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="pt-3 flex justify-end space-x-3">
                <button type="button" onclick="document.getElementById('modalEditSiswa').classList.add('hidden')" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold rounded-xl transition cursor-pointer">
                    Batal
                </button>
                <button type="submit" class="px-6 py-2.5 bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function lihatSiswa(s) {
        document.getElementById('detail_nama').textContent = (s.nama || '-').toUpperCase();
        document.getElementById('detail_nis_nisn').textContent = `NIS: ${s.nis || '-'} | NISN: ${s.nisn || '-'}`;
        document.getElementById('dt_jk').textContent = s.jenis_kelamin === 'L' ? 'Laki-laki (L)' : (s.jenis_kelamin === 'P' ? 'Perempuan (P)' : '-');
        document.getElementById('dt_ttl').textContent = `${s.tempat_lahir || '-'}, ${s.tanggal_lahir ? s.tanggal_lahir.substring(0, 10) : '-'}`;
        document.getElementById('dt_agama').textContent = s.agama || '-';
        document.getElementById('dt_status').textContent = s.status_anak || s.status_dalam_keluarga || s.status_keluarga || '-';
        document.getElementById('dt_anak_ke').textContent = s.anak_ke || '-';
        document.getElementById('dt_sekolah_asal').textContent = s.sekolah_asal || '-';
        document.getElementById('dt_kelas_diterima').textContent = s.kelas_diterima || '-';
        document.getElementById('dt_tgl_diterima').textContent = s.tanggal_diterima ? s.tanggal_diterima.substring(0, 10) : '-';
        document.getElementById('dt_alamat').textContent = s.alamat || '-';

        document.getElementById('dt_nama_ayah').textContent = s.nama_ayah || '-';
        document.getElementById('dt_pek_ayah').textContent = s.pekerjaan_ayah || '-';
        document.getElementById('dt_nama_ibu').textContent = s.nama_ibu || '-';
        document.getElementById('dt_pek_ibu').textContent = s.pekerjaan_ibu || '-';
        document.getElementById('dt_alamat_telp_ortu').textContent = `${s.alamat_ortu || s.alamat || '-'} (Telp: ${s.telp_ortu || s.no_telp || '-'})`;
        document.getElementById('dt_nama_wali').textContent = s.nama_wali || '-';
        document.getElementById('dt_pek_wali').textContent = s.pekerjaan_wali || '-';
        document.getElementById('dt_alamat_telp_wali').textContent = `${s.alamat_wali || '-'} (Telp: ${s.telp_wali || '-'})`;

        document.getElementById('modalDetailSiswa').classList.remove('hidden');
    }

    function editSiswa(s) {
        document.getElementById('edit_nis').value = s.nis || '';
        document.getElementById('edit_nisn').value = s.nisn || '';
        document.getElementById('edit_nama').value = s.nama || '';
        document.getElementById('edit_jk').value = s.jenis_kelamin || 'L';
        document.getElementById('edit_kelas').value = s.id_kelas || '';

        document.getElementById('edit_tempat_lahir').value = s.tempat_lahir || '';
        document.getElementById('edit_tanggal_lahir').value = s.tanggal_lahir ? s.tanggal_lahir.substring(0, 10) : '';
        document.getElementById('edit_agama').value = s.agama || '';
        document.getElementById('edit_status').value = s.status_anak || s.status_dalam_keluarga || s.status_keluarga || 'Anak Kandung';
        document.getElementById('edit_anak_ke').value = s.anak_ke || '';
        document.getElementById('edit_sekolah_asal').value = s.sekolah_asal || '';
        document.getElementById('edit_alamat').value = s.alamat || '';

        document.getElementById('edit_nama_ayah').value = s.nama_ayah || '';
        document.getElementById('edit_pekerjaan_ayah').value = s.pekerjaan_ayah || '';
        document.getElementById('edit_nama_ibu').value = s.nama_ibu || '';
        document.getElementById('edit_pekerjaan_ibu').value = s.pekerjaan_ibu || '';
        document.getElementById('edit_telp_ortu').value = s.telp_ortu || s.no_telp || '';
        document.getElementById('edit_nama_wali').value = s.nama_wali || '';

        document.getElementById('formEditSiswa').action = '/operator/siswa/' + s.id_siswa;
        document.getElementById('modalEditSiswa').classList.remove('hidden');
    }
</script>
@endsection
