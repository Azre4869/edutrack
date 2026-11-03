@extends('layouts.app')

@section('title', 'Tindak Lanjut')
@section('page_title', 'Tindak Lanjut')
@section('page_subtitle', 'Dashboard > Early Warning > Tindak Lanjut')

@section('content')
<div class="space-y-4">

    {{-- Student Profile Card --}}
    <div class="bg-white rounded-xl p-5 flex flex-wrap items-center gap-6">
        <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-orange-100 flex items-center justify-center">
                <i class="fas fa-user text-orange-400 text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-800 font-montserrat">{{ $siswa->nama_siswa ?? 'Firdausi Anam' }}</h2>
                <p class="text-gray-500 text-sm">NISN {{ $siswa->nis ?? '16389' }} &nbsp;|&nbsp; {{ $siswa->kelas->nama_kelas ?? 'VII A' }}</p>
                <span class="inline-flex items-center gap-1 mt-1 text-red-600 text-xs font-semibold">
                    <i class="fas fa-circle-exclamation"></i> Risiko Tinggi
                </span>
            </div>
        </div>
        <div class="flex flex-1 flex-wrap gap-8 justify-around border-l pl-6">
            <div>
                <p class="text-gray-400 text-xs">Rata-rata nilai</p>
                <p class="text-2xl font-bold text-edu-blue">{{ $analisis->rata_nilai ?? '68,2' }}</p>
                <p class="text-green-600 text-xs"><i class="fas fa-arrow-up"></i> 12% dari periode sebelumnya</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs">Kehadiran</p>
                <p class="text-2xl font-bold text-edu-blue">{{ $analisis->kehadiran_pct ?? '87' }}%</p>
                <p class="text-gray-400 text-xs">Perlu pemantauan kehadiran</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs">Di bawah KKM</p>
                <p class="text-2xl font-bold text-edu-blue">{{ $analisis->di_bawah_kkm ?? '3' }} mapel</p>
                <p class="text-gray-400 text-xs">Capaian kompetensi</p>
            </div>
            <div class="text-right">
                <div class="flex items-center gap-2 text-edu-blue text-sm">
                    <i class="fas fa-shield-check"></i>
                    <span>Tidak ada pelanggaran terbaru</span>
                </div>
                <p class="text-gray-500 text-xs mt-1">2026/2027 &nbsp;·&nbsp; Semester Genap</p>
            </div>
        </div>
    </div>

    {{-- Main Form Area --}}
    <form action="{{ route('wali.tindak-lanjut.store') }}" method="POST">
        @csrf
        <input type="hidden" name="id_siswa" value="{{ $siswa->id_siswa ?? '' }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- LEFT: Detail Sesi Konseling --}}
            <div class="lg:col-span-2 bg-white rounded-xl p-6 space-y-5">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="text-edu-blue font-bold text-lg font-montserrat flex items-center gap-2">
                        <i class="fas fa-comments text-edu-orange"></i> Detail sesi konseling
                    </h3>
                    <span class="text-xs text-gray-400">* Wajib diisi</span>
                </div>

                {{-- Info banner --}}
                <div class="flex items-center justify-between bg-blue-50 border border-blue-200 rounded-lg px-4 py-3">
                    <div class="flex items-center gap-2 text-blue-700 text-sm">
                        <i class="fas fa-circle-info"></i>
                        <span>Sesi belum dilaksanakan. Jadwalkan pertemuan; isi hasil konseling setelah sesi selesai.</span>
                    </div>
                    <span class="text-blue-700 text-xs font-semibold border border-blue-400 rounded px-2 py-0.5">Direncanakan</span>
                </div>

                {{-- Row 1: Tanggal, Waktu, Jenis --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal sesi <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="date" name="tanggal_sesi" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Waktu sesi <span class="text-red-500">*</span></label>
                        <input type="time" name="waktu_sesi" required
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis konseling</label>
                        <select name="jenis_konseling" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <option>Akademik &amp; kehadiran</option>
                            <option>Akademik</option>
                            <option>Kehadiran</option>
                            <option>Perilaku</option>
                            <option>Lainnya</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2: Pendamping, Peserta, Tempat --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pendamping / konselor <span class="text-red-500">*</span></label>
                        <select name="pendamping" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <option>Endang Sulistian, S.Pd.</option>
                            @foreach($guruList ?? [] as $guru)
                                <option value="{{ $guru->id_guru }}">{{ $guru->nama_guru }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Peserta yang diundang</label>
                        <select name="peserta" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <option>Siswa &amp; orang tua / wali</option>
                            <option>Siswa saja</option>
                            <option>Orang tua / wali saja</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempat / media</label>
                        <div class="relative">
                            <input type="text" name="tempat" placeholder="Isi ruang atau tautan"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 pr-8 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <i class="fas fa-location-dot absolute right-3 top-2.5 text-gray-400 text-sm"></i>
                        </div>
                    </div>
                </div>

                {{-- Catatan Konseling --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Catatan konseling</label>
                        <span class="text-xs text-gray-400">Diisi setelah sesi</span>
                    </div>
                    <textarea name="catatan" rows="5" maxlength="2000"
                        placeholder="Tuliskan hal yang dibahas, kebutuhan siswa, masukan orang tua, dan kesepakatan bersama. Jangan mencatat kesimpulan sebelum sesi berlangsung."
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none resize-none"></textarea>
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>Belum ada catatan sesi</span>
                        <span>0 / 2.000</span>
                    </div>
                </div>

                {{-- Fokus Early Warning --}}
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h4 class="text-edu-blue font-semibold text-sm mb-1">Fokus awal dari Early Warning</h4>
                    <p class="text-gray-600 text-sm">Telusuri penurunan nilai dan kebutuhan belajar pada 3 mapel di bawah KKM. Bahas kehadiran dan rencanakan dukungan bersama siswa serta orang tua.</p>
                </div>

                <p class="text-gray-400 text-xs flex items-center gap-1">
                    <i class="fas fa-lock"></i> Catatan terbatas untuk wali kelas dan pihak pendamping yang berwenang.
                </p>
            </div>

            {{-- RIGHT: Rencana Dukungan --}}
            <div class="bg-white rounded-xl p-6 space-y-5">
                <div class="border-b pb-3">
                    <h3 class="text-edu-blue font-bold text-lg font-montserrat flex items-center gap-2">
                        <i class="fas fa-clipboard-list text-edu-orange"></i> Rencana dukungan
                    </h3>
                    <p class="text-gray-400 text-xs mt-1">Usulan tindak lanjut dari Early Warning. Pilihan di bawah adalah rencana, bukan tindakan yang sudah selesai.</p>
                </div>

                {{-- Checkboxes --}}
                <div class="space-y-4">
                    {{-- Pendampingan --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="rencana[]" value="pendampingan" checked
                            class="mt-1 w-4 h-4 accent-edu-blue">
                        <div>
                            <p class="text-edu-blue font-semibold text-sm">Pendampingan belajar &amp; remedial</p>
                            <p class="text-gray-500 text-xs">Koordinasikan dukungan pada 3 mapel di bawah KKM; pantau perkembangan nilai.</p>
                            <p class="text-gray-400 text-xs mt-0.5">Pelaksana usulan: wali kelas &amp; guru mapel</p>
                        </div>
                    </label>
                    {{-- Pemantauan --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="rencana[]" value="pemantauan" checked
                            class="mt-1 w-4 h-4 accent-edu-blue">
                        <div>
                            <p class="text-edu-blue font-semibold text-sm">Pemantauan kehadiran</p>
                            <p class="text-gray-500 text-xs">Tinjau presensi setiap minggu dan koordinasikan dengan orang tua / wali.</p>
                            <p class="text-gray-400 text-xs mt-0.5">Pelaksana usulan: wali kelas &amp; orang tua / wali</p>
                        </div>
                    </label>
                    {{-- Motivasi --}}
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="rencana[]" value="motivasi" checked
                            class="mt-1 w-4 h-4 accent-edu-blue">
                        <div>
                            <p class="text-edu-blue font-semibold text-sm">Motivasi &amp; dukungan belajar</p>
                            <p class="text-gray-500 text-xs">Susun target belajar yang realistis bersama siswa saat sesi konseling.</p>
                            <p class="text-gray-400 text-xs mt-0.5">Pelaksana usulan: pendamping / konselor</p>
                        </div>
                    </label>
                </div>

                <div class="border-t pt-4 space-y-4">
                    {{-- Penanggung jawab --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Penanggung jawab <span class="text-red-500">*</span></label>
                        <select name="penanggung_jawab" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <option>Endang Sulistian, S.Pd.</option>
                            @foreach($guruList ?? [] as $guru)
                                <option value="{{ $guru->id_guru }}">{{ $guru->nama_guru }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal evaluasi --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal evaluasi tindak lanjut <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="date" name="tanggal_evaluasi" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                        </div>
                    </div>

                    {{-- Hint --}}
                    <div class="bg-orange-50 border border-orange-200 rounded-lg px-3 py-2 flex items-center gap-2">
                        <i class="fas fa-calendar-clock text-orange-400 text-sm"></i>
                        <span class="text-orange-700 text-xs">Disarankan evaluasi 1-2 minggu setelah sesi konseling.</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Bottom Action Bar --}}
        <div class="bg-white rounded-xl mt-4 px-6 py-4 flex items-center justify-between">
            <a href="{{ route('wali.early-warning') }}" class="flex items-center gap-2 text-edu-blue font-semibold text-sm hover:underline">
                <i class="fas fa-arrow-left"></i> Kembali ke Early Warning
            </a>
            <div class="text-center">
                <p class="text-gray-700 font-semibold text-sm">Draf baru - Belum disimpan</p>
                <p class="text-gray-400 text-xs">Menyimpan draf tidak menandai konseling sebagai selesai.</p>
            </div>
            <button type="submit"
                class="bg-edu-blue hover:bg-blue-700 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition">
                Simpan Draf Konseling
            </button>
        </div>
    </form>

</div>
@endsection
