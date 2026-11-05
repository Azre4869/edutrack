@extends('layouts.app')

@section('title', 'Analisis AI & Klastering Pola Belajar')
@section('page_title', 'Analisis AI')
@section('page_subtitle', 'Dashboard > Early Warning > Analisis AI')

@section('content')
@php
    $rolePrefix  = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'wali';
    $isOperator  = (auth()->user()->role ?? '') === 'Operator';

    // Palet warna klaster — satu sumber kebenaran, dipakai PHP & JS
    $clusterPalette = [
        '#6366f1', // 0 Indigo
        '#f43f5e', // 1 Rose
        '#f59e0b', // 2 Amber
        '#10b981', // 3 Emerald
        '#0ea5e9', // 4 Sky
        '#a855f7', // 5 Purple
    ];

    $aiStatusText  = $aiServiceActive ? 'Layanan AI Aktif' : 'Layanan AI Tidak Aktif';
    $aiStatusColor = $aiServiceActive ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-rose-100 text-rose-700 border-rose-300';
    $aiStatusIcon  = $aiServiceActive ? 'fa-circle-check' : 'fa-circle-xmark';
@endphp

{{-- ======= DISCLAIMER BANNER ======= --}}
<div class="mb-6 p-4 rounded-3xl bg-gradient-to-r from-indigo-900 to-purple-900 text-white shadow-lg font-heading">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-2xl flex-shrink-0">
                <i class="fa-solid fa-brain text-amber-300"></i>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-base font-black tracking-wide">Lapisan Analisis Prediktif & Pola Belajar AI</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-gray-950 uppercase tracking-wider whitespace-nowrap">
                        Data Simulasi
                    </span>
                </div>
                <p class="text-xs text-indigo-200 font-medium mt-0.5">
                    Model K-Means & Isolation Forest lokal (deterministik tanpa API luar). Digunakan sebagai instrumen pendukung keputusan pendidik.
                </p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
            {{-- Chip status layanan AI --}}
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold border {{ $aiStatusColor }} whitespace-nowrap">
                <i class="fa-solid {{ $aiStatusIcon }} text-[10px]"></i>
                {{ $aiStatusText }}
            </span>
            @if($isOperator)
            <a href="{{ route('operator.early-warning.ai.evaluasi', ['semester_id' => $selectedSemesterId]) }}"
               class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold border border-white/20 transition flex items-center space-x-1.5">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Evaluasi Model</span>
            </a>
            <form action="{{ route('operator.early-warning.ai.run') }}" method="POST"
                  onsubmit="return confirm('Jalankan ulang klastering K-Means dan deteksi anomali pada semester terpilih?')">
                @csrf
                <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
                <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">
                <button type="submit"
                        class="px-3 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-gray-950 text-xs font-black shadow-md transition flex items-center space-x-1.5">
                    <i class="fa-solid fa-bolt"></i>
                    <span>Jalankan Ulang AI</span>
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

{{-- ======= FILTER BAR ======= --}}
<div class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 mb-6 font-heading">
    <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4 flex-1">
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                <select name="semester_id" onchange="this.form.submit()"
                        class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer">
                    @foreach($semesterList as $sm)
                        <option value="{{ $sm->id_semester }}" {{ $selectedSemesterId == $sm->id_semester ? 'selected' : '' }}>
                            {{ $sm->nama_semester }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas</label>
                <select name="kelas_id" onchange="this.form.submit()"
                        class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer">
                    @if($isOperator)
                        <option value="">Semua Kelas (Kohor Penuh)</option>
                    @endif
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>
                            {{ $k->nama_kelas }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <a href="{{ route($rolePrefix . '.early-warning', ['semester_id' => $selectedSemesterId, 'kelas_id' => $selectedKelasId]) }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-4 rounded-xl transition flex items-center space-x-1.5">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Early Warning</span>
        </a>
    </form>
</div>

@if($latestRun)

{{-- ======= STAT CARDS ======= --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 font-heading">
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-3">
        <div class="w-11 h-11 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-lg flex-shrink-0">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-500 uppercase">Siswa Teranalisis</p>
            <h4 class="text-lg font-black text-gray-900 mt-0.5">{{ $latestRun->metrik['total_diproses'] ?? count($insights) }} Siswa</h4>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-3">
        <div class="w-11 h-11 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-lg flex-shrink-0">
            <i class="fa-solid fa-shapes"></i>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-500 uppercase">Jumlah Klaster (k)</p>
            <h4 class="text-lg font-black text-gray-900 mt-0.5">{{ $latestRun->metrik['k'] ?? count($clusterSummary) }} Pola</h4>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-3">
        <div class="w-11 h-11 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg flex-shrink-0">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-500 uppercase">Silhouette Score</p>
            <h4 class="text-lg font-black text-gray-900 mt-0.5">
                {{ number_format((float)($latestRun->metrik['silhouette_score'] ?? 0.0), 3, ',', '.') }}
                @php
                    $sil = (float)($latestRun->metrik['silhouette_score'] ?? 0);
                    $silLabel = $sil >= 0.5 ? 'Kuat' : ($sil >= 0.25 ? 'Cukup' : 'Lemah');
                    $silColor = $sil >= 0.5 ? 'text-emerald-600' : ($sil >= 0.25 ? 'text-amber-600' : 'text-rose-500');
                @endphp
                <span class="text-[10px] font-semibold {{ $silColor }}">({{ $silLabel }})</span>
            </h4>
        </div>
    </div>
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-3">
        <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg flex-shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <p class="text-[10px] font-bold text-gray-500 uppercase">Pola Anomali</p>
            <h4 class="text-lg font-black text-rose-600 mt-0.5">{{ $anomalies->count() }} Siswa</h4>
        </div>
    </div>
</div>

{{-- ======= SECTION 1: PCA SCATTER + KLASTER CARDS ======= --}}
{{-- Flexbox — tidak pakai grid-cols-12 agar aman di semua lebar layar --}}
<div class="flex flex-col xl:flex-row gap-6 mb-6">

    {{-- PCA SCATTER (60% di xl) --}}
    <div class="xl:w-[60%] flex-shrink-0 bg-white rounded-3xl p-6 shadow-md border border-gray-200">
        <div class="flex items-start justify-between pb-3 border-b border-gray-100 mb-4">
            <div>
                <h3 class="text-sm font-black text-gray-900 font-heading flex items-center space-x-2">
                    <i class="fa-solid fa-circle-nodes text-indigo-600"></i>
                    <span>Peta Proyeksi 2D Pola Siswa (PCA)</span>
                </h3>
                <p class="text-xs text-gray-500 font-medium mt-1">
                    Tiap titik mewakili satu siswa berdasarkan multi-fitur nilai TP/SAS, stabilitas kehadiran, dan tren semester. Titik merah bergaris = anomali.
                </p>
            </div>
            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider flex-shrink-0 ml-2">Chart.js</span>
        </div>

        <div class="relative w-full" style="height: 420px;">
            <canvas id="pcaScatterChart"></canvas>
        </div>

        <p class="mt-3 text-[11px] text-gray-400 italic text-center">
            *Klastering dilatih pada seluruh kohor; filter kelas hanya menyaring tampilan. Klik titik untuk detail.
        </p>
    </div>

    {{-- KLASTER CARDS (sisa lebar) --}}
    <div class="xl:flex-1 min-w-0 bg-white rounded-3xl p-6 shadow-md border border-gray-200 flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <h3 class="text-sm font-black text-gray-900 font-heading flex items-center space-x-2">
                <i class="fa-solid fa-layer-group text-purple-600"></i>
                <span>Karakteristik Klaster Belajar</span>
            </h3>
        </div>

        <div class="space-y-3 flex-1 overflow-y-auto pr-1" style="max-height: 420px;">
            @foreach($clusterSummary as $cs)
            @php
                $cColor = $clusterPalette[$cs['cluster_id'] % count($clusterPalette)];
            @endphp
            <div class="p-4 rounded-2xl border border-gray-100 bg-gray-50/60 hover:bg-white hover:shadow-sm transition">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-black text-gray-900 font-heading flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full flex-shrink-0 ring-2 ring-white shadow-sm"
                              style="background-color: {{ $cColor }}"></span>
                        {{ $cs['label'] }}
                    </span>
                    <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full text-white whitespace-nowrap"
                          style="background-color: {{ $cColor }}85">
                        {{ $cs['jumlah_siswa'] }} Siswa ({{ number_format($cs['persentase'], 1, ',', '.') }}%)
                    </span>
                </div>
                <p class="text-xs text-gray-600 leading-relaxed mb-3 font-body">
                    {{ $cs['deskripsi'] }}
                </p>

                {{-- Bar proporsi --}}
                <div class="h-1.5 w-full bg-gray-200 rounded-full mb-3">
                    <div class="h-1.5 rounded-full" style="width: {{ min($cs['persentase'], 100) }}%; background-color: {{ $cColor }}"></div>
                </div>

                <div class="grid grid-cols-3 gap-2 text-center text-[10px] font-heading">
                    <div class="bg-white p-2 rounded-xl border border-gray-200">
                        <span class="text-gray-400 block text-[9px]">Rata Nilai</span>
                        <strong class="text-gray-800 font-black">
                            {{ number_format((float)($cs['rata_rata_fitur']['rata_nilai_akhir'] ?? 0), 1, ',', '.') }}
                        </strong>
                    </div>
                    <div class="bg-white p-2 rounded-xl border border-gray-200">
                        <span class="text-gray-400 block text-[9px]">Kehadiran</span>
                        <strong class="text-gray-800 font-black">
                            {{ number_format((float)($cs['rata_rata_fitur']['persen_hadir'] ?? 0), 1, ',', '.') }}%
                        </strong>
                    </div>
                    <div class="bg-white p-2 rounded-xl border border-gray-200">
                        <span class="text-gray-400 block text-[9px]">Rata Alpha</span>
                        <strong class="text-gray-800 font-black">
                            {{ number_format((float)($cs['rata_rata_fitur']['jumlah_alpha'] ?? 0), 1, ',', '.') }} hr
                        </strong>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-500 font-body">
            <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i>
            Centroid dihitung otomatis lintas seluruh kohor semester terpilih.
        </div>
    </div>
</div>

{{-- ======= SECTION 2: TABEL ANOMALI ======= --}}
<div class="bg-white rounded-3xl p-6 shadow-md border border-gray-200 mb-6">
    <div class="flex flex-wrap items-center justify-between pb-4 border-b border-gray-100 gap-3 mb-4">
        <div>
            <h3 class="text-sm font-black text-gray-900 font-heading flex items-center space-x-2">
                <i class="fa-solid fa-bolt text-rose-500"></i>
                <span>Daftar Siswa Terindikasi Anomali</span>
            </h3>
            <p class="text-xs text-gray-500 font-medium mt-0.5">
                Isolation Forest + Robust Z-Score. Urut dari skor anomali tertinggi.
            </p>
        </div>
        <span class="text-xs font-bold text-rose-600 bg-rose-50 px-3 py-1 rounded-full border border-rose-200 whitespace-nowrap">
            Total {{ $anomalies->count() }} Terdeteksi
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full excel-table border-collapse text-xs text-left">
            <thead>
                <tr class="bg-gray-800 text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                    <th class="p-3 w-10 border border-black">No.</th>
                    <th class="p-3 w-24 border border-black">NIS</th>
                    <th class="p-3 min-w-[160px] border border-black">Nama Siswa</th>
                    <th class="p-3 w-16 border border-black">Kelas</th>
                    <th class="p-3 w-24 border border-black">Skor Anomali</th>
                    <th class="p-3 min-w-[240px] border border-black">Penjelasan (Explainable AI)</th>
                    <th class="p-3 w-28 border border-black">Proyeksi Nilai</th>
                    <th class="p-3 w-20 border border-black">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-black bg-white font-body">
                @forelse($anomalies as $i => $anom)
                <tr class="hover:bg-rose-50/40 transition">
                    <td class="p-2.5 text-center font-semibold border border-black">{{ $i + 1 }}.</td>
                    <td class="p-2.5 text-center font-medium border border-black">{{ $anom->siswa->nis ?? '-' }}</td>
                    <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $anom->siswa->nama ?? "Siswa #{$anom->id_siswa}" }}</td>
                    <td class="p-2.5 text-center border border-black">{{ $anom->siswa->kelas->nama_kelas ?? '-' }}</td>
                    <td class="p-2.5 text-center border border-black">
                        <span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700">
                            {{ number_format($anom->anomaly_score, 3, ',', '.') }}
                        </span>
                    </td>
                    <td class="p-2.5 border border-black">
                        @if(!empty($anom->alasan))
                        <div class="space-y-1">
                            @foreach(array_slice((array)$anom->alasan, 0, 2) as $als)
                            <div class="text-[11px] text-gray-800 flex items-start space-x-1.5">
                                <i class="fa-solid fa-circle-exclamation text-rose-500 mt-0.5 text-[10px] flex-shrink-0"></i>
                                <span class="line-clamp-2" title="{{ $als['teks_penjelasan'] }}">{{ $als['teks_penjelasan'] }}</span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <span class="text-gray-400 italic text-[11px]">Penyimpangan multivariat (Isolation Forest).</span>
                        @endif
                    </td>
                    <td class="p-2.5 text-center border border-black font-heading">
                        <span class="font-extrabold text-gray-800">
                            {{ number_format($anom->prediksi_nilai, 1, ',', '.') }}
                        </span>
                        <span class="text-[9px] block {{ $anom->slope_nilai < 0 ? 'text-rose-600' : 'text-emerald-600' }} font-bold">
                            {{ $anom->slope_nilai >= 0 ? '+' : '' }}{{ number_format($anom->slope_nilai, 2, ',', '.') }}/penilaian
                        </span>
                    </td>
                    <td class="p-2.5 text-center border border-black">
                        <a href="{{ route($rolePrefix . '.early-warning', ['semester_id' => $selectedSemesterId, 'kelas_id' => $anom->siswa->id_kelas ?? null, 'selected_id' => $anom->id_siswa]) }}"
                           class="px-2.5 py-1 rounded bg-edu-blue hover:bg-blue-700 text-white text-[10px] font-bold transition inline-flex items-center space-x-1">
                            <i class="fa-solid fa-eye"></i>
                            <span>Detail</span>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="p-8 text-center text-gray-400 border border-black">
                        Tidak ditemukan siswa dengan karakteristik anomali pada semester ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@else
{{-- Empty state: belum ada run --}}
<div class="bg-white rounded-3xl p-12 shadow-sm border border-gray-100 text-center font-heading">
    <div class="w-16 h-16 rounded-full bg-indigo-50 text-indigo-400 flex items-center justify-center text-3xl mx-auto mb-4">
        <i class="fa-solid fa-brain"></i>
    </div>
    <h3 class="text-base font-bold text-gray-700 mb-1">Analisis AI Belum Dijalankan</h3>
    <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
        Silakan klik tombol <strong>"Jalankan Ulang AI"</strong> di atas untuk memproses data kohor siswa dengan model klastering dan anomali.
    </p>
</div>
@endif

@endsection

@push('scripts')
@if(!empty($pcaPoints))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rawData = @json($pcaPoints);
    const palette = @json($clusterPalette);

    // Kelompokkan titik per klaster
    const clustersMap = {};
    rawData.forEach(p => {
        const cId = p.cluster_id;
        if (!clustersMap[cId]) {
            clustersMap[cId] = {
                label: `${p.cluster_label}`,
                data: [],
                backgroundColor: [],
                pointRadius: [],
                pointHoverRadius: 10,
                borderColor: [],
                borderWidth: []
            };
        }
        const baseColor = palette[cId % palette.length];
        clustersMap[cId].data.push({ x: p.x, y: p.y, meta: p });

        if (p.is_anomaly) {
            clustersMap[cId].backgroundColor.push(baseColor + 'DD');
            clustersMap[cId].pointRadius.push(9);
            clustersMap[cId].borderColor.push('#e11d48');
            clustersMap[cId].borderWidth.push(3);
        } else {
            clustersMap[cId].backgroundColor.push(baseColor + 'CC');
            clustersMap[cId].pointRadius.push(5);
            clustersMap[cId].borderColor.push('#ffffff');
            clustersMap[cId].borderWidth.push(1.5);
        }
    });

    const datasets = Object.values(clustersMap);

    const ctx = document.getElementById('pcaScatterChart').getContext('2d');
    new Chart(ctx, {
        type: 'scatter',
        data: { datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        padding: 12,
                        font: { size: 10, family: 'inherit' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const m = ctx.raw.meta;
                            const lines = [`${m.nama} | ${m.kelas}`, `Klaster: ${m.cluster_label}`];
                            if (m.is_anomaly) lines.push(`⚡ Anomali (skor: ${m.anomaly_score.toFixed(3)})`);
                            if (m.berpotensi_turun) lines.push('↘ Berpotensi Turun');
                            return lines;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Komponen 1 (PCA)', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y: {
                    title: { display: true, text: 'Komponen 2 (PCA)', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            }
        }
    });
});
</script>
@endif
@endpush
