@extends('layouts.app')

@section('title', $isWali ? 'Rapor Siswa' : 'Monitoring Kelengkapan Rapor')
@section('page_title', $isWali ? 'Rapor Siswa' : 'Monitoring Rapor')
@section('page_subtitle', $isWali ? 'Dashboard > Rapor' : 'Dashboard > Monitoring Rapor')

@section('content')
@php
    $rolePrefix = (auth()->user()->role ?? '') === 'Operator' ? 'operator' : 'wali';
    $isWali = auth()->user()->isWaliKelas();
@endphp

<div class="space-y-4">

    {{-- Filter Bar (Murni GET) --}}
    <form id="filterForm" method="GET" action="{{ route($rolePrefix . '.rapor') }}" class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 font-heading">
        <div class="flex flex-wrap items-center gap-4">
            <div>
                <label class="text-xs text-gray-700 mb-1 block font-bold">Tanggal</label>
                <input type="date" name="tanggal" value="{{ $selectedTanggal ?? date('Y-m-d') }}" onchange="document.getElementById('filterForm').submit()"
                    class="border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
            </div>
            <div>
                <label class="text-xs text-gray-700 mb-1 block font-bold">Tahun Ajaran</label>
                <select name="tahun_ajaran" class="border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <option value="2026/2027" selected>2026/2027</option>
                    <option value="2025/2026">2025/2026</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-700 mb-1 block font-bold">Semester</label>
                <select name="semester_id" onchange="document.getElementById('filterForm').submit()" class="border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($semesterList as $sm)
                        <option value="{{ $sm->id_semester }}" {{ ($selectedSemesterId ?? 2) == $sm->id_semester ? 'selected' : '' }}>{{ $sm->nama_semester }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-700 mb-1 block font-bold">
                    Kelas
                    @if($isWali)
                        <span class="text-[10px] text-edu-blue font-normal"><i class="fa-solid fa-lock text-[9px] mr-0.5"></i>Binaan</span>
                    @endif
                </label>
                <select name="kelas_id" onchange="document.getElementById('filterForm').submit()" class="border border-gray-300 rounded-xl px-3 py-2 text-xs font-semibold focus:ring-2 focus:ring-edu-orange focus:outline-none cursor-pointer">
                    @foreach($kelasList as $k)
                        <option value="{{ $k->id_kelas }}" {{ ($selectedKelasId ?? 1) == $k->id_kelas ? 'selected' : '' }}>{{ $k->nama_kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="text-xs text-gray-700 mb-1 block font-bold">Cari Siswa</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Cari nama, NIS, atau kelas siswa......"
                        class="w-full border border-gray-300 rounded-xl pl-9 pr-3 py-2 text-xs font-medium focus:ring-2 focus:ring-edu-orange focus:outline-none">
                    <i class="fas fa-search absolute left-3 top-2.5 text-edu-orange text-xs"></i>
                </div>
            </div>
        </div>
    </form>

    {{-- Main Content: Table + Sidebar --}}
    <div class="flex flex-col lg:flex-row gap-4">

        {{-- Table --}}
        <div class="flex-1 bg-white rounded-3xl p-4 shadow-xl border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto w-full max-h-[640px] pb-2">
                <table class="w-full text-xs border-collapse text-left excel-table">
                    <thead>
                        <tr class="bg-edu-orange text-white text-center font-heading font-black tracking-wider text-[11px] uppercase">
                            <th class="p-3 text-center font-semibold w-12 border border-black">No.</th>
                            <th class="p-3 text-center font-semibold w-28 border border-black">NIS/NISN</th>
                            <th class="p-3 text-center font-semibold min-w-[200px] border border-black">NAMA</th>
                            <th class="p-3 text-center font-semibold w-12 border border-black">L/P</th>
                            <th class="p-3 text-center font-semibold w-20 border border-black">Kelas</th>
                            <th class="p-3 text-center font-semibold w-32 border border-black">Status Rapor</th>
                            @if($isWali)
                            <th class="p-3 text-center font-semibold min-w-[160px] border border-black">Aksi</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-black bg-white font-body" id="raporTableBody">
                        @forelse($siswaList as $index => $s)
                        @php
                            $isRowLengkap = ($index % 5 !== 4);
                        @endphp
                        <tr class="hover:bg-blue-50/40 transition">
                            <td class="p-2.5 text-center text-gray-600 border border-black font-semibold">{{ $index + 1 }}.</td>
                            <td class="p-2.5 text-center text-gray-700 border border-black font-medium">{{ $s->nis }}</td>
                            <td class="p-2.5 font-bold text-gray-900 border border-black">{{ $s->nama }}</td>
                            <td class="p-2.5 text-center text-gray-600 border border-black">{{ $s->jenis_kelamin }}</td>
                            <td class="p-2.5 text-center text-gray-600 border border-black">{{ $s->kelas->nama_kelas ?? '-' }}</td>
                            <td class="p-2.5 text-center border border-black">
                                @if($isRowLengkap)
                                    <span class="status-badge bg-emerald-100 text-emerald-700 text-[11px] font-bold px-3 py-1 rounded-full">Lengkap</span>
                                @else
                                    <span class="status-badge bg-red-100 text-red-600 text-[11px] font-bold px-3 py-1 rounded-full">Belum Lengkap</span>
                                @endif
                            </td>
                            @if($isWali)
                            <td class="p-2.5 text-center border border-black">
                                <div class="flex items-center justify-center gap-1.5 font-heading">
                                    <a href="{{ route($rolePrefix . '.rapor.show', $s->id_siswa) }}" title="Lihat Rapor"
                                        class="w-7 h-7 rounded-lg bg-blue-100 text-edu-blue flex items-center justify-center hover:bg-blue-200 transition">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('wali.rapor.cetak', $s->id_siswa) }}" title="Cetak Rapor" target="_blank"
                                        class="w-7 h-7 rounded-lg bg-gray-100 text-gray-700 flex items-center justify-center hover:bg-gray-200 transition">
                                        <i class="fas fa-print text-xs"></i>
                                    </a>
                                    <button type="button" onclick="toggleStatusRapor(this)" title="Toggle Status Kelengkapan"
                                        class="w-7 h-7 rounded-lg bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 shadow-sm transition cursor-pointer">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                </div>
                            </td>
                            @endif
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $isWali ? 7 : 6 }}" class="p-8 text-center text-gray-500 border border-black">Tidak ada data siswa untuk kelas ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="w-full lg:w-72 shrink-0 space-y-4 font-heading">
            {{-- Status Rapor Chart --}}
            <div class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100">
                <h4 class="text-edu-blue font-bold font-heading mb-4 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-chart-pie text-edu-orange"></i>
                    <span>Status Rapor</span>
                </h4>
                <div class="flex justify-center mb-4">
                    <canvas id="raporDonut" width="160" height="160"></canvas>
                </div>
                <div class="space-y-2 text-xs font-semibold">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                            <span class="text-gray-700">Lengkap</span>
                        </div>
                        <span id="textLengkapCount" class="text-edu-blue font-bold">27 Siswa</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full bg-gray-300 inline-block"></span>
                            <span class="text-gray-700">Belum Lengkap</span>
                        </div>
                        <span id="textBelumLengkapCount" class="text-gray-500 font-bold">5 Siswa</span>
                    </div>
                </div>
            </div>

            {{-- Informasi Template --}}
            <div class="bg-white rounded-3xl p-5 shadow-lg border border-gray-100 text-xs">
                <h4 class="text-edu-blue font-bold mb-3 flex items-center gap-2 text-sm">
                    <i class="fas fa-file-lines text-edu-orange"></i> Informasi Template
                </h4>
                <p class="text-gray-400 mb-1">Template Aktif</p>
                <p class="text-edu-blue font-bold text-xs mb-2">SMP Negeri 1 Sampang - Rapor 2026/2027</p>
                <span class="inline-block bg-blue-50 text-edu-blue font-bold px-2.5 py-1 rounded-lg">Format Kurikulum Merdeka</span>
            </div>

            {{-- Info Note --}}
            <div class="bg-amber-50 border border-amber-200 rounded-3xl p-4 flex items-start gap-2.5">
                <i class="fas fa-circle-info text-amber-500 mt-0.5 shrink-0 text-sm"></i>
                <p class="text-amber-800 text-xs leading-relaxed font-body">Pastikan seluruh nilai sumatif, kehadiran, ekstrakurikuler, dan catatan prestasi sudah lengkap sebelum mencetak rapor.</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
let raporChart = null;

function updateChartAndCounts() {
    const badges = document.querySelectorAll('#raporTableBody .status-badge');
    let lengkap = 0;
    let belum = 0;

    badges.forEach(b => {
        if (b.innerText.trim() === 'Lengkap') lengkap++;
        else belum++;
    });

    const total = lengkap + belum;
    const pct = total > 0 ? Math.round((lengkap / total) * 100) : 0;

    const elLengkap = document.getElementById('textLengkapCount');
    const elBelum = document.getElementById('textBelumLengkapCount');
    if (elLengkap) elLengkap.innerText = lengkap + ' Siswa';
    if (elBelum) elBelum.innerText = belum + ' Siswa';

    if (raporChart) {
        raporChart.data.datasets[0].data = [lengkap, belum];
        raporChart.options.plugins.centerTextVal = pct + '%';
        raporChart.update();
    }
}

function toggleStatusRapor(btn) {
    const tr = btn.closest('tr');
    if (!tr) return;
    const badge = tr.querySelector('.status-badge');
    if (!badge) return;

    if (badge.innerText.trim() === 'Lengkap') {
        badge.innerText = 'Belum Lengkap';
        badge.className = 'status-badge bg-red-100 text-red-600 text-[11px] font-bold px-3 py-1 rounded-full';
    } else {
        badge.innerText = 'Lengkap';
        badge.className = 'status-badge bg-emerald-100 text-emerald-700 text-[11px] font-bold px-3 py-1 rounded-full';
    }

    updateChartAndCounts();
}

document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('raporDonut').getContext('2d');
    
    // Initial counts
    const badges = document.querySelectorAll('#raporTableBody .status-badge');
    let initLengkap = 0;
    let initBelum = 0;
    badges.forEach(b => {
        if (b.innerText.trim() === 'Lengkap') initLengkap++;
        else initBelum++;
    });
    if (initLengkap === 0 && initBelum === 0) {
        initLengkap = 27;
        initBelum = 5;
    }
    const total = initLengkap + initBelum;
    const initialPct = Math.round((initLengkap / total) * 100);

    const centerPlugin = {
        id: 'centerText',
        afterDraw(chart) {
            const { ctx, chartArea: { width, height, left, top } } = chart;
            ctx.save();
            ctx.font = 'bold 22px Montserrat, sans-serif';
            ctx.fillStyle = '#2F5CAB';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const pctText = chart.options.plugins.centerTextVal || (initialPct + '%');
            ctx.fillText(pctText, left + width / 2, top + height / 2 - 8);
            ctx.font = '11px sans-serif';
            ctx.fillStyle = '#6B7280';
            ctx.fillText('Lengkap', left + width / 2, top + height / 2 + 14);
            ctx.restore();
        }
    };

    raporChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Lengkap', 'Belum Lengkap'],
            datasets: [{
                data: [initLengkap, initBelum],
                backgroundColor: ['#2F5CAB', '#E5E7EB'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '75%',
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true },
                centerTextVal: initialPct + '%'
            },
            animation: { animateScale: true }
        },
        plugins: [centerPlugin]
    });

    updateChartAndCounts();
});
</script>
@endpush
@endsection
