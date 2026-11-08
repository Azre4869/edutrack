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
                <h2 class="text-xl font-bold text-gray-800 font-montserrat">{{ $siswa->nama ?? '-' }}</h2>
                <p class="text-gray-500 text-sm">NISN {{ $siswa->nisn ?? ($siswa->nis ?? '-') }} &nbsp;|&nbsp; {{ $siswa->kelas->nama_kelas ?? '-' }}</p>
                <span class="inline-flex items-center gap-1 mt-1 text-red-600 text-xs font-semibold">
                    <i class="fas fa-circle-exclamation"></i> Risiko Tinggi
                </span>
            </div>
        </div>
        <div class="flex flex-1 flex-wrap gap-8 justify-around border-l pl-6">
            <div>
                <p class="text-gray-400 text-xs">Rata-rata nilai</p>
                <p class="text-2xl font-bold text-edu-blue">{{ $analisis->rata_nilai ?? '-' }}</p>
                @if(isset($analisis->arah) && $analisis->arah === 'turun')
                    <p class="text-red-600 text-xs"><i class="fas fa-arrow-down"></i> {{ $analisis->perubahan_teks }}</p>
                @elseif(isset($analisis->arah) && $analisis->arah === 'naik')
                    <p class="text-green-600 text-xs"><i class="fas fa-arrow-up"></i> {{ $analisis->perubahan_teks }}</p>
                @elseif(isset($analisis->arah) && $analisis->arah === 'tetap')
                    <p class="text-gray-500 text-xs"><i class="fas fa-minus"></i> {{ $analisis->perubahan_teks }}</p>
                @else
                    <p class="text-gray-400 text-xs">{{ $analisis->perubahan_teks ?? 'Tidak ada data pembanding' }}</p>
                @endif
            </div>
            <div>
                <p class="text-gray-400 text-xs">Kehadiran</p>
                <p class="text-2xl font-bold text-edu-blue">{{ isset($analisis->kehadiran_pct) ? $analisis->kehadiran_pct . '%' : '-' }}</p>
                <p class="text-gray-400 text-xs">Perlu pemantauan kehadiran</p>
            </div>
            <div>
                <p class="text-gray-400 text-xs">Di bawah KKM</p>
                <p class="text-2xl font-bold text-edu-blue">{{ isset($analisis->di_bawah_kkm) ? $analisis->di_bawah_kkm . ' mapel' : '-' }}</p>
                <p class="text-gray-400 text-xs">Capaian kompetensi</p>
            </div>
            <div class="text-right">
                <div class="flex items-center gap-2 text-edu-blue text-sm">
                    <i class="fas fa-shield-check"></i>
                    <span>Tidak ada pelanggaran terbaru</span>
                </div>
                <p class="text-gray-500 text-xs mt-1">{{ $semesterObj ? $semesterObj->tahun_ajaran . ' · Semester ' . $semesterObj->nama_semester : '-' }}</p>
            </div>
        </div>
    </div>

    {{-- Main Form Area --}}
    @php
        $rolePrefix = $rolePrefix ?? (auth()->user() && auth()->user()->isOperator() ? 'operator' : 'wali');
    @endphp
    <form action="{{ route($rolePrefix . '.tindak-lanjut.store') }}" method="POST">
        @csrf
        <input type="hidden" name="id_siswa" value="{{ $siswa->id_siswa ?? '' }}">
        <input type="hidden" name="id_analisis" value="{{ $analisisRecord->id_analisis ?? '' }}">
        <input type="hidden" name="semester_id" value="{{ $semesterId ?? '' }}">

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
                                value="{{ old('tanggal_sesi', $counseling['tanggal_sesi'] ?? '') }}"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Waktu sesi <span class="text-red-500">*</span></label>
                        <input type="time" name="waktu_sesi" required
                            value="{{ old('waktu_sesi', $counseling['waktu_sesi'] ?? '') }}"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Jenis konseling</label>
                        <select name="jenis_konseling" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <option value="Akademik & kehadiran" {{ old('jenis_konseling', $counseling['jenis_konseling'] ?? '') === 'Akademik & kehadiran' ? 'selected' : '' }}>Akademik &amp; kehadiran</option>
                            <option value="Akademik" {{ old('jenis_konseling', $counseling['jenis_konseling'] ?? '') === 'Akademik' ? 'selected' : '' }}>Akademik</option>
                            <option value="Kehadiran" {{ old('jenis_konseling', $counseling['jenis_konseling'] ?? '') === 'Kehadiran' ? 'selected' : '' }}>Kehadiran</option>
                            <option value="Perilaku" {{ old('jenis_konseling', $counseling['jenis_konseling'] ?? '') === 'Perilaku' ? 'selected' : '' }}>Perilaku</option>
                            <option value="Lainnya" {{ old('jenis_konseling', $counseling['jenis_konseling'] ?? '') === 'Lainnya' ? 'selected' : '' }}>Lainnya</option>
                        </select>
                    </div>
                </div>

                {{-- Row 2: Pendamping, Peserta, Tempat --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pendamping / konselor <span class="text-red-500">*</span></label>
                        <select name="pendamping" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <option value="">-- Pilih Guru Pendamping --</option>
                            @foreach($guruList ?? [] as $guru)
                                <option value="{{ $guru->id_guru }}" {{ (string) old('pendamping', '') === (string) $guru->id_guru ? 'selected' : '' }}>
                                    {{ $guru->nama_guru }}{{ !empty($guru->jabatan) ? ' (' . $guru->jabatan . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Peserta yang diundang</label>
                        <select name="peserta" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                            <option value="Siswa & orang tua / wali" {{ old('peserta', $counseling['peserta'] ?? '') === 'Siswa & orang tua / wali' ? 'selected' : '' }}>Siswa &amp; orang tua / wali</option>
                            <option value="Siswa saja" {{ old('peserta', $counseling['peserta'] ?? '') === 'Siswa saja' ? 'selected' : '' }}>Siswa saja</option>
                            <option value="Orang tua / wali saja" {{ old('peserta', $counseling['peserta'] ?? '') === 'Orang tua / wali saja' ? 'selected' : '' }}>Orang tua / wali saja</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tempat / media</label>
                        <div class="relative">
                            <input type="text" name="tempat" placeholder="Isi ruang atau tautan"
                                value="{{ old('tempat', $counseling['tempat'] ?? '') }}"
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
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none resize-none">{{ old('catatan', '') }}</textarea>
                    <div class="flex justify-between text-xs text-gray-400 mt-1">
                        <span>Belum ada catatan sesi</span>
                        <span>0 / 2.000</span>
                    </div>
                </div>

                {{-- Fokus Early Warning --}}
                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                    <h4 class="text-edu-blue font-semibold text-sm mb-1">Fokus awal dari Early Warning</h4>
                    <p class="text-gray-600 text-sm">Telusuri penurunan nilai dan kebutuhan belajar pada {{ isset($analisis->di_bawah_kkm) ? $analisis->di_bawah_kkm . ' mapel' : '-' }} di bawah KKM. Bahas kehadiran dan rencanakan dukungan bersama siswa serta orang tua.</p>
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
                            <p class="text-gray-500 text-xs">Koordinasikan dukungan pada {{ isset($analisis->di_bawah_kkm) ? $analisis->di_bawah_kkm . ' mapel' : '-' }} di bawah KKM; pantau perkembangan nilai.</p>
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
                            <option value="">-- Pilih Penanggung Jawab --</option>
                            @php
                                $selectedPj = old('penanggung_jawab', auth()->user()?->id_guru ?? '');
                            @endphp
                            @foreach($guruList ?? [] as $guru)
                                <option value="{{ $guru->id_guru }}" {{ (string) $selectedPj === (string) $guru->id_guru ? 'selected' : '' }}>
                                    {{ $guru->nama_guru }}{{ !empty($guru->jabatan) ? ' (' . $guru->jabatan . ')' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Tanggal evaluasi --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal evaluasi tindak lanjut <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="date" name="tanggal_evaluasi" required
                                value="{{ old('tanggal_evaluasi', $counseling['tanggal_evaluasi'] ?? '') }}"
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
            <a href="{{ route($rolePrefix . '.early-warning') }}" class="flex items-center gap-2 text-edu-blue font-semibold text-sm hover:underline">
                <i class="fas fa-arrow-left"></i> Kembali ke Early Warning
            </a>
            <div class="text-center">
                @if(isset($riwayatTindakLanjut) && $riwayatTindakLanjut->isNotEmpty())
                    <p class="text-emerald-700 font-semibold text-sm">
                        <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i>
                        {{ $riwayatTindakLanjut->count() }} Sesi / Draf Tersimpan di Bawah
                    </p>
                    <p class="text-gray-400 text-xs">Form di atas dapat diisi kembali untuk menjadwalkan sesi tindak lanjut berikutnya.</p>
                @else
                    <p class="text-gray-700 font-semibold text-sm">Draf baru - Belum disimpan</p>
                    <p class="text-gray-400 text-xs">Menyimpan draf tidak menandai konseling sebagai selesai.</p>
                @endif
            </div>
            <button type="submit"
                class="bg-edu-blue hover:bg-blue-700 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition">
                Simpan Draf Konseling
            </button>
        </div>
    </form>

    {{-- ======= RIWAYAT DRAF & SESI KONSELING ======= --}}
    <div class="bg-white rounded-2xl mt-6 p-6 shadow-md border border-gray-100 font-heading">
        <div class="flex items-center justify-between pb-4 border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 text-edu-blue flex items-center justify-center text-lg shadow-sm">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-900">Riwayat Draf & Sesi Konseling</h3>
                    <p class="text-xs text-gray-500 font-body">Daftar catatan dan rencana bimbingan konseling yang telah tersimpan untuk siswa ini.</p>
                </div>
            </div>
            <span class="text-xs px-3 py-1 rounded-full bg-blue-100 text-edu-blue font-bold">
                {{ isset($riwayatTindakLanjut) ? $riwayatTindakLanjut->count() : 0 }} Sesi Tersimpan
            </span>
        </div>

        @if(isset($riwayatTindakLanjut) && $riwayatTindakLanjut->isNotEmpty())
            <div class="mt-4 space-y-4 font-body">
                @foreach($riwayatTindakLanjut as $idx => $tl)
                    @php
                        $statusBadge = match($tl->status) {
                            'Selesai'         => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                            'Sedang Diproses' => 'bg-amber-100 text-amber-800 border-amber-200',
                            default           => 'bg-blue-100 text-blue-800 border-blue-200',
                        };
                    @endphp
                    <div class="p-4 rounded-xl bg-gray-50/80 border border-gray-200 hover:border-edu-blue/40 transition">
                        <div class="flex flex-wrap items-center justify-between gap-2 pb-2.5 border-b border-gray-200/80 text-xs">
                            <div class="flex items-center space-x-2 font-heading">
                                <span class="font-black text-gray-900 text-sm">
                                    Sesi #{{ $riwayatTindakLanjut->count() - $idx }}
                                </span>
                                <span class="text-gray-400">&bull;</span>
                                <span class="text-gray-600 font-medium">
                                    <i class="fa-regular fa-calendar mr-1 text-edu-orange"></i>
                                    {{ \Carbon\Carbon::parse($tl->tanggal)->translatedFormat('l, d F Y') }}
                                    @if($tl->waktu)
                                        pukul {{ substr($tl->waktu, 0, 5) }} WIB
                                    @endif
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $statusBadge }}">
                                    {{ $tl->status }}
                                </span>
                                @if($tl->status !== 'Selesai')
                                    <form action="{{ route($rolePrefix . '.tindak-lanjut.selesai', $tl->id_tindak_lanjut) }}" method="POST" onsubmit="return confirm('Tandai sesi konseling ini sebagai Selesai?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-bold transition shadow-sm" title="Tandai konseling ini telah selesai dilaksanakan">
                                            <i class="fa-solid fa-check"></i> Tandai Selesai
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route($rolePrefix . '.tindak-lanjut.destroy', $tl->id_tindak_lanjut) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus sesi konseling ini? Data yang dihapus tidak dapat dikembalikan.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-bold transition" title="Hapus sesi / draf ini">
                                        <i class="fa-regular fa-trash-can"></i> Hapus
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
                            <div class="p-2.5 bg-white rounded-lg border border-gray-100 shadow-sm">
                                <span class="text-gray-400 text-[10px] block font-heading font-semibold">Guru Pendamping / Konselor</span>
                                <strong class="text-gray-800 font-heading block mt-0.5 text-xs">
                                    {{ $tl->guru?->nama_guru ?? ($tl->user?->name ?? 'Belum Ditentukan') }}
                                </strong>
                                <span class="text-[10px] text-gray-400">Pencatat: {{ $tl->user?->username ?? 'Operator' }}</span>
                            </div>
                            <div class="p-2.5 bg-white rounded-lg border border-gray-100 shadow-sm">
                                <span class="text-gray-400 text-[10px] block font-heading font-semibold">Jenis & Tempat</span>
                                <strong class="text-gray-800 font-heading block mt-0.5 text-xs">{{ $tl->jenis_konseling ?? '-' }}</strong>
                                <span class="text-[10px] text-gray-500">{{ $tl->tempat_media ?? 'Ruang BK' }} &bull; {{ $tl->peserta_diundang ?? 'Siswa' }}</span>
                            </div>
                            <div class="p-2.5 bg-white rounded-lg border border-gray-100 shadow-sm">
                                <span class="text-gray-400 text-[10px] block font-heading font-semibold">Rencana Evaluasi</span>
                                <strong class="text-gray-800 font-heading block mt-0.5 text-xs">
                                    {{ $tl->tanggal_evaluasi ? \Carbon\Carbon::parse($tl->tanggal_evaluasi)->translatedFormat('d M Y') : 'Belum Dijadwalkan' }}
                                </strong>
                                <span class="text-[10px] text-gray-500">Efektivitas: {{ $tl->skor_efektivitas ?? 'Belum Dievaluasi' }}</span>
                            </div>
                        </div>

                        {{-- Catatan Konseling & Rencana Dukungan --}}
                        <div class="mt-3 pt-3 border-t border-gray-100 text-xs space-y-2">
                            <div>
                                <span class="font-bold text-gray-700 block mb-1">Catatan Konseling:</span>
                                <p class="text-gray-700 bg-white p-3 rounded-lg border border-gray-100 leading-relaxed font-body">
                                    "{{ $tl->catatan_konseling ?: 'Tidak ada catatan tambahan.' }}"
                                </p>
                            </div>
                            @if($tl->rencana_dukungan)
                                <div>
                                    <span class="font-bold text-gray-700 block mb-1">Rencana Dukungan yang Dipilih:</span>
                                    <div class="flex flex-wrap gap-1.5 mt-1">
                                        @foreach(explode(', ', $tl->rencana_dukungan) as $renc)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-md bg-blue-50 text-edu-blue border border-blue-200 text-[11px] font-medium">
                                                <i class="fa-solid fa-check text-[9px] mr-1 text-emerald-600"></i>
                                                {{ ucfirst($renc) }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                            @if($tl->hasil_evaluasi)
                                <div>
                                    <span class="font-bold text-gray-700 block mb-1">Catatan Hasil Evaluasi:</span>
                                    <p class="text-gray-700 bg-emerald-50/60 p-2.5 rounded-lg border border-emerald-200 leading-relaxed font-body">
                                        <i class="fa-solid fa-circle-check text-emerald-600 mr-1"></i> {{ $tl->hasil_evaluasi }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="mt-4 p-8 text-center bg-gray-50 rounded-xl border border-dashed border-gray-200 font-body">
                <i class="fa-solid fa-clipboard-list text-3xl text-gray-300 mb-2"></i>
                <p class="text-sm font-bold text-gray-600">Belum Ada Sesi Konseling yang Tersimpan</p>
                <p class="text-xs text-gray-400 mt-0.5">Isi form di atas dan klik tombol "Simpan Draf Konseling" untuk membuat sesi pertama bagi siswa ini.</p>
            </div>
        @endif
    </div>

</div>
@endsection
