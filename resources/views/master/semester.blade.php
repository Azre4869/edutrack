@extends('layouts.app')

@section('title', 'Data Semester & Tahun Ajaran')
@section('page_title', 'Data')
@section('page_subtitle', 'Dashboard > Data > Semester & Tahun Ajaran')

@section('content')
<!-- CONTROL / FILTER BAR -->
<div class="mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
    <!-- Dropdown Per Page -->
    <div class="flex items-center space-x-3">
        <div class="relative w-full sm:w-auto">
            <select onchange="window.location.href='?per_page='+this.value" class="w-full sm:w-auto appearance-none bg-white border border-gray-200 text-gray-800 text-xs font-bold font-heading py-2.5 pl-4 pr-10 rounded-full shadow-sm focus:outline-none focus:ring-2 focus:ring-edu-orange cursor-pointer">
                <option value="20" {{ request('per_page') == 20 ? 'selected' : '' }}>Tampilkan 1 - 20</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>Tampilkan 1 - 50</option>
            </select>
            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-gray-500">
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </div>
        </div>
    </div>

    <!-- Right Controls: Tambah Semester -->
    <div class="flex items-center flex-wrap gap-2.5">
        @if(auth()->user()->role === 'Operator')
        <button onclick="document.getElementById('modalTambahSemester').classList.remove('hidden')" class="flex-1 sm:flex-none justify-center bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold font-heading py-2.5 px-5 rounded-xl shadow-md flex items-center space-x-2 transition cursor-pointer">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Semester Baru</span>
        </button>
        @endif
    </div>
</div>

<!-- SEARCH BAR -->
<div class="mb-5 flex items-center gap-3">
    <form action="{{ url()->current() }}" method="GET" class="flex-1 flex items-center bg-white rounded-xl shadow-sm px-4 py-2.5 border border-gray-200">
        <i class="fa-solid fa-magnifying-glass text-edu-orange mr-3"></i>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari semester atau tahun ajaran (contoh: Genap, 2026/2027)..." class="w-full text-xs font-medium text-gray-800 placeholder-gray-400 focus:outline-none font-heading">
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
<div class="bg-white rounded-3xl p-3 sm:p-4 shadow-xl border border-gray-200 overflow-hidden w-full max-w-full">
    <div class="overflow-x-auto w-full max-h-[640px] pb-2">
        <table class="w-full min-w-[700px] excel-table border-collapse text-xs text-left">
            <thead>
                <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                    <th class="p-3 w-16 border border-black">No.</th>
                    <th class="p-3 min-w-[180px] border border-black">Tahun Ajaran</th>
                    <th class="p-3 min-w-[180px] border border-black">Semester</th>
                    <th class="p-3 w-36 border border-black">Status</th>
                    <th class="p-3 min-w-[220px] border border-black">Aksi Pengaturan</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black bg-white font-body">
                @forelse($semesters as $idx => $s)
                    <tr class="hover:bg-blue-50/40 transition {{ $s->is_active ? 'bg-emerald-50/50' : '' }}">
                        <td class="p-3 text-center font-semibold border border-black">{{ $semesters->firstItem() + $idx }}.</td>
                        <td class="p-3 font-black text-gray-900 border border-black text-center text-sm">
                            {{ $s->tahun_ajaran }}
                        </td>
                        <td class="p-3 font-bold text-gray-800 border border-black text-center">
                            Semester {{ $s->nama_semester }}
                        </td>
                        <td class="p-3 text-center border border-black">
                            @if($s->is_active)
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-black bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-sm">
                                    <i class="fa-solid fa-circle-check mr-1.5 text-emerald-600"></i> Aktif Berjalan
                                </span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-600 border border-gray-300">
                                    <i class="fa-solid fa-box-archive mr-1.5 text-gray-400"></i> Arsip
                                </span>
                            @endif
                        </td>
                        <td class="p-3 text-center border border-black">
                            <div class="flex items-center justify-center space-x-2">
                                @if(!$s->is_active)
                                    <form action="{{ route('operator.semester.set-active', $s->id_semester) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menetapkan semester {{ $s->nama_semester }} ({{ $s->tahun_ajaran }}) sebagai semester aktif sekolah?')">
                                        @csrf
                                        <button type="submit" class="bg-edu-blue hover:bg-blue-800 text-white text-[11px] font-bold py-1.5 px-3 rounded-lg shadow-sm flex items-center space-x-1.5 transition">
                                            <i class="fa-solid fa-toggle-on text-emerald-400"></i>
                                            <span>Jadikan Semester Aktif</span>
                                        </button>
                                    </form>

                                    <form action="{{ route('operator.semester.destroy', $s->id_semester) }}" method="POST" onsubmit="return confirm('Hapus semester ini? Tindakan ini hanya berhasil jika belum ada data nilai atau absensi.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Semester" class="w-7 h-7 rounded-lg bg-red-500 hover:bg-red-600 text-white flex items-center justify-center text-xs transition shadow-sm">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs font-extrabold text-emerald-700 italic">
                                        <i class="fa-solid fa-check mr-1"></i> Sedang Digunakan
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-gray-500 border border-black">Belum ada data semester terdaftar.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="mt-4 flex flex-col md:flex-row items-center justify-between gap-4 border-t border-gray-100 pt-3">
        <p class="text-xs font-semibold text-gray-500 font-heading">
            Menampilkan {{ $semesters->firstItem() ?? 0 }} sampai {{ $semesters->lastItem() ?? 0 }} dari {{ $semesters->total() }} semester
        </p>
        <div>
            {{ $semesters->links() }}
        </div>
    </div>
</div>

<!-- MODAL TAMBAH SEMESTER -->
<div id="modalTambahSemester" class="hidden fixed inset-0 z-50 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl relative">
        <div class="flex items-center justify-between border-b pb-3 mb-4">
            <h3 class="text-lg font-black text-gray-900 font-heading">Tambah Semester Baru</h3>
            <button onclick="document.getElementById('modalTambahSemester').classList.add('hidden')" class="text-gray-400 hover:text-gray-600 text-xl font-bold">&times;</button>
        </div>

        <form action="{{ route('operator.semester.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Tahun Ajaran <span class="text-red-500">*</span></label>
                <input type="text" name="tahun_ajaran" required placeholder="Contoh: 2027/2028" pattern="\d{4}/\d{4}"
                       value="{{ old('tahun_ajaran') }}"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none">
                <span class="text-[10px] text-gray-400 block mt-1">Format: 4 digit tahun / 4 digit tahun (contoh: 2027/2028)</span>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">Nama Semester <span class="text-red-500">*</span></label>
                <select name="nama_semester" required class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="Ganjil" {{ old('nama_semester') === 'Ganjil' ? 'selected' : '' }}>Ganjil (Juli - Desember)</option>
                    <option value="Genap" {{ old('nama_semester') === 'Genap' ? 'selected' : '' }}>Genap (Januari - Juni)</option>
                </select>
            </div>

            <div class="pt-2">
                <label class="inline-flex items-center space-x-2.5 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="w-4 h-4 text-edu-orange rounded border-gray-300 focus:ring-edu-orange">
                    <span class="text-xs font-bold text-gray-800">Jadikan langsung sebagai Semester Aktif</span>
                </label>
                <p class="text-[10px] text-gray-500 mt-1 pl-6.5">Jika dicentang, semester yang sedang aktif saat ini akan otomatis diarsipkan.</p>
            </div>

            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
                <button type="button" onclick="document.getElementById('modalTambahSemester').classList.add('hidden')" class="px-4 py-2.5 rounded-xl border border-gray-300 text-xs font-bold text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-5 py-2.5 rounded-xl bg-edu-orange hover:bg-edu-orangeHover text-white text-xs font-bold shadow-md">Simpan Semester</button>
            </div>
        </form>
    </div>
</div>
@endsection
