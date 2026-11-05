@extends('layouts.app')

@section('title', 'Analisis AI & Klastering Pola Belajar')
@section('page_title', 'Analisis AI')
@section('page_subtitle', 'Dashboard > Early Warning > Analisis AI')

@section('content')
@php
    $rolePrefix = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'wali';
    $isOperator = (auth()->user()->role ?? '') === 'Operator';
@endphp

<!-- DISCLAIMER BANNER -->
<div class="mb-6 p-4 rounded-3xl bg-gradient-to-r from-indigo-900 to-purple-900 text-white shadow-lg flex flex-wrap items-center justify-between gap-4 font-heading">
    <div class="flex items-center space-x-3.5">
        <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-2xl flex-shrink-0">
            <i class="fa-solid fa-brain text-amber-300"></i>
        </div>
        <div>
            <div class="flex items-center space-x-2">
                <h3 class="text-base font-black tracking-wide">Lapisan Analisis Prediktif & Pola Belajar AI</h3>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-gray-950 uppercase tracking-wider">
                    Data Simulasi
                </span>
            </div>
            <p class="text-xs text-indigo-200 font-medium mt-0.5">
                Model K-Means & Isolation Forest lokal (deterministik tanpa API luar). Digunakan sebagai instrumen pendukung keputusan pendidik.
            </p>
        </div>
    </div>
    @if($isOperator)
    <div class="flex items-center space-x-2">
        <a href="{{ route('operator.early-warning.ai.evaluasi', ['semester_id' => $selectedSemesterId]) }}" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold border border-white/20 transition flex items-center space-x-1.5">
            <i class="fa-solid fa-chart-pie"></i>
            <span>Evaluasi Model</span>
        </a>
        <form action="{{ route('operator.early-warning.ai.run') }}" method="POST" onsubmit="return confirm('Jalankan ulang klastering K-Means dan deteksi anomali pada semester terpilih?')">
            @csrf
            <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
            <input type="hidden" name="kelas_id" value="{{ $selectedKelasId }}">
            <button type="submit" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-gray-950 text-xs font-black shadow-md transition flex items-center space-x-1.5">
                <i class="fa-solid fa-bolt"></i>
                <span>Jalankan Ulang AI</span>
            </button>
        </form>
    </div>
    @endif
</div>

<!-- FILTER BAR -->
<div class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 mb-6 font-heading">
    <form action="{{ url()->current() }}" method="GET" class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-4 flex-1">
            <!-- Semester -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                <select name="semester_id" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer">
                    @foreach($semesterList as $sm)
                        <option value="{{ $sm->id_semester }}" {{ $selectedSemesterId == $sm->id_semester ? 'selected' : '' }}>{{ $sm->nama_semester }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Kelas -->
            <div>
                <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas</label>
                <select name="kelas_id" onchange="this.form.submit()" class="bg-gray-50 border border-gray-300 text-gray-800 text-xs font-semibold py-2 px-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer">
                    @if($isOperator)
                        <option value="">Semua Kelas (Kohor Penuh)</option>
                    @endif
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}" {{ $selectedKelasId == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="flex items-center space-x-2">
            <a href="{{ route($rolePrefix . '.early-warning', ['semester_id' => $selectedSemesterId, 'kelas_id' => $selectedKelasId]) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-bold py-2 px-4 rounded-xl transition flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Early Warning</span>
            </a>
        </div>
    </form>
</div>

@if($latestRun)
<!-- STAT CARDS METRIK MODEL -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-6 font-heading">
    <!-- Total Diproses -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-500 uppercase">Siswa Teranalisis</p>
            <h4 class="text-xl font-black text-gray-900 mt-0.5">{{ $latestRun->metrik['total_diproses'] ?? count($insights) }} Siswa</h4>
        </div>
    </div>

    <!-- Klaster Terbentuk -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-shapes"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-500 uppercase">Jumlah Klaster (k)</p>
            <h4 class="text-xl font-black text-gray-900 mt-0.5">{{ $latestRun->metrik['k'] ?? count($clusterSummary) }} Pola</h4>
        </div>
    </div>

    <!-- Silhouette Score -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-chart-line"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-500 uppercase">Silhouette Score</p>
            <h4 class="text-xl font-black text-gray-900 mt-0.5">
                {{ number_format((float) ($latestRun->metrik['silhouette_score'] ?? 0.0), 3) }}
                <span class="text-[10px] font-semibold text-emerald-600">(Kuat)</span>
            </h4>
        </div>
    </div>

    <!-- Anomali Terdeteksi -->
    <div class="bg-white rounded-3xl p-5 shadow-sm border border-gray-100 flex items-center space-x-4">
        <div class="w-12 h-12 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center text-xl flex-shrink-0">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div>
            <p class="text-[11px] font-bold text-gray-500 uppercase">Pola Anomali</p>
            <h4 class="text-xl font-black text-rose-600 mt-0.5">{{ $anomalies->count() }} Siswa</h4>
        </div>
    </div>
</div>

<!-- SECTION 1: PCA 2D SCATTER PLOT & KLASTER CARDS -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
    <!-- LEFT: PCA 2D SCATTER PLOT (SPAN 7) -->
    <div class="lg:col-span-7 bg-white rounded-3xl p-6 shadow-md border border-gray-200">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
            <div>
                <h3 class="text-base font-black text-gray-900 font-heading flex items-center space-x-2">
                    <i class="fa-solid fa-circle-nodes text-indigo-600"></i>
                    <span>Peta Proyeksi 2D Pola Siswa (PCA)</span>
                </h3>
                <p class="text-xs text-gray-500 font-medium mt-0.5">
                    Titik merepresentasikan siswa berdasarkan multi-fitur (nilai TP/SAS, stabilitas kehadiran, anomali).
                </p>
            </div>
            <span class="text-[10px] text-gray-400 font-bold uppercase tracking-wider">Chart.js</span>
        </div>

        <div class="relative h-[360px] w-full">
            <canvas id="pcaScatterChart"></canvas>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 flex flex-wrap items-center justify-between text-xs text-gray-500">
            <div class="flex items-center space-x-4">
                <span class="inline-flex items-center"><span class="w-3 h-3 rounded-full bg-indigo-500 mr-1.5"></span> Klaster 0</span>
                <span class="inline-flex items-center"><span class="w-3 h-3 rounded-full bg-purple-500 mr-1.5"></span> Klaster 1</span>
                <span class="inline-flex items-center"><span class="w-3 h-3 rounded-full bg-amber-500 mr-1.5"></span> Klaster 2+</span>
                <span class="inline-flex items-center"><span class="w-3 h-3 rounded-full border-2 border-rose-500 bg-rose-200 mr-1.5"></span> Anomali</span>
            </div>
            <span class="text-[11px] text-gray-400 italic">*Klik titik untuk melihat NIS/Nama</span>
        </div>
    </div>

    <!-- RIGHT: RINGKASAN CENTROID KLASTER (SPAN 5) -->
    <div class="lg:col-span-5 bg-white rounded-3xl p-6 shadow-md border border-gray-200 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <h3 class="text-base font-black text-gray-900 font-heading flex items-center space-x-2">
                    <i class="fa-solid fa-layer-group text-purple-600"></i>
                    <span>Karakteristik Klaster Belajar</span>
                </h3>
            </div>

            <div class="space-y-3.5 max-h-[380px] overflow-y-auto pr-1">
                @foreach($clusterSummary as $cs)
                    <div class="p-4 rounded-2xl border border-gray-200 bg-gray-50/70 hover:bg-white hover:shadow-sm transition">
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-xs font-black text-indigo-900 font-heading">
                                Klaster {{ $cs['cluster_id'] }}: {{ $cs['label'] }}
                            </span>
                            <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800">
                                {{ $cs['jumlah_siswa'] }} Siswa ({{ $cs['persentase'] }}%)
                            </span>
                        </div>
                        <p class="text-xs text-gray-600 leading-relaxed mb-2.5">
                            {{ $cs['deskripsi'] }}
                        </p>
                        <div class="grid grid-cols-3 gap-2 text-center text-[10px] pt-2 border-t border-gray-200/60 font-heading">
                            <div class="bg-white p-1.5 rounded-lg border border-gray-200">
                                <span class="text-gray-400 block text-[9px]">Rata Nilai</span>
                                <strong class="text-gray-800 font-black">{{ $cs['rata_rata_fitur']['rata_nilai_akhir'] ?? '-' }}</strong>
                            </div>
                            <div class="bg-white p-1.5 rounded-lg border border-gray-200">
                                <span class="text-gray-400 block text-[9px]">Kehadiran</span>
                                <strong class="text-gray-800 font-black">{{ $cs['rata_rata_fitur']['persen_hadir'] ?? '-' }}%</strong>
                            </div>
                            <div class="bg-white p-1.5 rounded-lg border border-gray-200">
                                <span class="text-gray-400 block text-[9px]">Rata Alpha</span>
                                <strong class="text-gray-800 font-black">{{ $cs['rata_rata_fitur']['jumlah_alpha'] ?? '-' }} hr</strong>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-gray-100 text-[11px] text-gray-500">
            <i class="fa-solid fa-circle-check text-emerald-500 mr-1"></i> Centroid dihitung otomatis lintas seluruh kohor semester.
        </div>
    </div>
</div>

<!-- SECTION 2: TABEL SISWA TERINDIKASI ANOMALI -->
<div class="bg-white rounded-3xl p-6 shadow-md border border-gray-200 mb-6">
    <div class="flex flex-wrap items-center justify-between pb-4 border-b border-gray-100 gap-3 mb-4">
        <div>
            <h3 class="text-base font-black text-gray-900 font-heading flex items-center space-x-2">
                <i class="fa-solid fa-bolt text-rose-500"></i>
                <span>Daftar Siswa Terindikasi Anomali (Isolation Forest + Robust Z-Score)</span>
            </h3>
            <p class="text-xs text-gray-500 font-medium mt-0.5">
                Siswa dengan deviasi statistik tajam terhadap rata-rata kelas/sekolah atau terdeteksi perubahan mendadak.
            </p>
        </div>
        <span class="text-xs font-bold text-rose-600 bg-rose-50 px-3 py-1 rounded-full border border-rose-200">
            Total {{ $anomalies->count() }} Terdeteksi
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full excel-table border-collapse text-xs text-left">
            <thead>
                <tr class="bg-gray-800 text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                    <th class="p-3 w-12 border border-black">No.</th>
                    <th class="p-3 w-28 border border-black">NIS</th>
                    <th class="p-3 min-w-[180px] border border-black">Nama Siswa</th>
                    <th class="p-3 w-20 border border-black">Kelas</th>
                    <th class="p-3 w-28 border border-black">Skor Anomali</th>
                    <th class="p-3 min-w-[280px] border border-black">Penjelasan Konkret (Explainable AI)</th>
                    <th class="p-3 w-32 border border-black">Proyeksi Nilai</th>
                    <th class="p-3 w-24 border border-black">Aksi</th>
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
                                {{ number_format($anom->anomaly_score, 3) }}
                            </span>
                        </td>
                        <td class="p-2.5 border border-black">
                            @if(!empty($anom->alasan))
                                <div class="space-y-1">
                                    @foreach($anom->alasan as $als)
                                        <div class="text-[11px] text-gray-800 flex items-start space-x-1.5">
                                            <i class="fa-solid fa-circle-exclamation text-rose-500 mt-0.5 text-[10px] flex-shrink-0"></i>
                                            <span>{{ $als['teks_penjelasan'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400 italic">Penyimpangan multivariat Isolation Forest.</span>
                            @endif
                        </td>
                        <td class="p-2.5 text-center border border-black font-heading">
                            <span class="font-extrabold text-gray-800">{{ number_format($anom->prediksi_nilai, 1) }}</span>
                            <span class="text-[9px] block {{ $anom->slope_nilai < 0 ? 'text-rose-600' : 'text-emerald-600' }} font-bold">
                                {{ $anom->slope_nilai >= 0 ? '+' : '' }}{{ number_format($anom->slope_nilai, 2) }}/per
                            </span>
                        </td>
                        <td class="p-2.5 text-center border border-black">
                            <a href="{{ route($rolePrefix . '.early-warning', ['semester_id' => $selectedSemesterId, 'kelas_id' => $anom->siswa->id_kelas ?? null, 'selected_id' => $anom->id_siswa]) }}" class="px-2.5 py-1 rounded bg-edu-blue hover:bg-blue-700 text-white text-[10px] font-bold transition inline-flex items-center space-x-1">
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
<div class="bg-white rounded-3xl p-12 shadow-sm border border-gray-100 text-center font-heading">
    <i class="fa-solid fa-brain text-5xl text-gray-300 mb-3"></i>
    <h3 class="text-base font-bold text-gray-700">Analisis AI Belum Dijalankan</h3>
    <p class="text-xs text-gray-400 mt-1 max-w-sm mx-auto">
        Silakan klik tombol "Jalankan Ulang AI" di atas untuk memproses data kohor siswa dengan model klastering dan anomali.
    </p>
</div>
@endif

@endsection

@push('scripts')
@if(!empty($pcaPoints))
<script>
document.addEventListener('DOMContentLoaded', function () {
    const rawData = @json($pcaPoints);

    // Color palettes for clusters
    const clusterColors = [
        'rgba(99, 102, 241, 0.85)',   // Indigo (Klaster 0)
        'rgba(168, 85, 247, 0.85)',  // Purple (Klaster 1)
        'rgba(245, 158, 11, 0.85)',  // Amber (Klaster 2)
        'rgba(16, 185, 129, 0.85)',  // Emerald (Klaster 3)
        'rgba(14, 165, 233, 0.85)',  // Sky (Klaster 4)
        'rgba(236, 72, 153, 0.85)',  // Pink (Klaster 5)
    ];

    // Group points by cluster or anomaly
    const datasets = [];
    const clustersMap = {};

    rawData.forEach(p => {
        const cId = p.cluster_id;
        if (!clustersMap[cId]) {
            clustersMap[cId] = {
                label: `Klaster ${cId}: ${p.cluster_label}`,
                data: [],
                backgroundColor: clusterColors[cId % clusterColors.length],
                pointRadius: [],
                pointHoverRadius: 8,
                borderColor: [],
                borderWidth: []
            };
        }

        clustersMap[cId].data.push({ x: p.x, y: p.y, meta: p });

        if (p.is_anomaly) {
            clustersMap[cId].pointRadius.push(8);
            clustersMap[cId].borderColor.push('#e11d48'); // Red border for anomaly
            clustersMap[cId].borderWidth.push(3);
        } else {
            clustersMap[cId].pointRadius.push(5);
            clustersMap[cId].borderColor.push('rgba(255,255,255,0.8)');
            clustersMap[cId].borderWidth.push(1);
        }
    });

    for (const key in clustersMap) {
        datasets.push(clustersMap[key]);
    }

    const ctx = document.getElementById('pcaScatterChart').getContext('2d');
    new Chart(ctx, {
        type: 'scatter',
        data: { datasets: datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        font: { size: 10, family: 'sans-serif' }
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function (ctx) {
                            const meta = ctx.raw.meta;
                            const anomalyText = meta.is_anomaly ? ' [ANOMALI]' : '';
                            return `${meta.nama} (${meta.kelas}) - ${meta.cluster_label}${anomalyText} | (x:${meta.x.toFixed(2)}, y:${meta.y.toFixed(2)})`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    title: { display: true, text: 'Komponen Utama 1 (PCA X)', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y: {
                    title: { display: true, text: 'Komponen Utama 2 (PCA Y)', font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                }
            }
        }
    });
});
</script>
@endif
@endpush
