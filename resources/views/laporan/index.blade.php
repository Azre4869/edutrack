@extends('layouts.app')

@section('title', 'Laporan')
@section('page_title', 'Laporan')
@section('page_subtitle', 'Dashboard > Laporan')

@section('content')
<div class="space-y-4">

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Total Siswa --}}
        <div class="bg-white rounded-xl p-6 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                <i class="fas fa-users text-3xl text-edu-orange"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-semibold">Total Siswa</p>
                <p class="text-4xl font-bold text-edu-orange font-montserrat">{{ $totalSiswa ?? 990 }}</p>
            </div>
        </div>
        {{-- Kehadiran --}}
        <div class="bg-white rounded-xl p-6 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                <i class="fas fa-calendar-check text-3xl text-edu-orange"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-semibold">Kehadiran Hari ini</p>
                <p class="text-4xl font-bold text-edu-orange font-montserrat">{{ $kehadiranPct ?? 93 }}%</p>
            </div>
        </div>
        {{-- Rata-rata Nilai --}}
        <div class="bg-white rounded-xl p-6 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                <i class="fas fa-chart-line text-3xl text-edu-orange"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-semibold">Rata-rata Nilai Kelas</p>
                <p class="text-4xl font-bold text-edu-orange font-montserrat">{{ $rataRataNilai ?? 90.5 }}</p>
            </div>
        </div>
        {{-- Jumlah Guru --}}
        <div class="bg-white rounded-xl p-6 flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-orange-50 flex items-center justify-center shrink-0">
                <i class="fas fa-chalkboard-teacher text-3xl text-edu-orange"></i>
            </div>
            <div>
                <p class="text-gray-500 text-sm font-semibold">Jumlah Guru</p>
                <p class="text-4xl font-bold text-edu-orange font-montserrat">{{ $jumlahGuru ?? 78 }}</p>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Rekapitulasi Data Siswa (Donut) --}}
        <div class="bg-white rounded-xl p-6">
            <h3 class="font-bold text-gray-700 mb-4">Rekapitulasi Data Siswa</h3>
            <div class="flex justify-center mb-4">
                <canvas id="siswaDonutLaporan" width="200" height="200"></canvas>
            </div>
            <div class="space-y-2 text-sm">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                        <span class="text-gray-600">Kelas VII</span>
                    </div>
                    <span class="font-semibold text-gray-700">330 (33%)</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-edu-orange inline-block"></span>
                        <span class="text-gray-600">Kelas VIII</span>
                    </div>
                    <span class="font-semibold text-gray-700">334 (34%)</span>
                </div>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-green-500 inline-block"></span>
                        <span class="text-gray-600">Kelas IX</span>
                    </div>
                    <span class="font-semibold text-gray-700">326 (33%)</span>
                </div>
            </div>
        </div>

        {{-- Tren Kehadiran (Line Chart) --}}
        <div class="bg-white rounded-xl p-6 lg:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-gray-700">Tren Kehadiran Siswa</h3>
                <div class="flex items-center gap-4 text-xs text-gray-500">
                    <span class="flex items-center gap-1"><span class="w-4 h-0.5 bg-edu-blue inline-block"></span> Hadir</span>
                    <span class="flex items-center gap-1"><span class="w-4 h-0.5 bg-edu-orange inline-block"></span> Tidak Hadir</span>
                </div>
            </div>
            <canvas id="kehadiranLineLaporan" height="120"></canvas>
            <div class="flex items-center gap-2 mt-3 text-green-600 text-xs">
                <i class="fas fa-circle-check"></i>
                <span>Persentase kehadiran konsisten berada di atas target minimum 90%.</span>
            </div>
        </div>
    </div>

    {{-- Bottom Row: Bar Chart + Table --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Bar Chart: Rekap Nilai per Mapel --}}
        <div class="bg-white rounded-xl p-6 lg:col-span-2">
            <h3 class="font-bold text-gray-700 mb-4">Rekap Nilai Rata-rata per Mata Pelajaran</h3>
            <canvas id="nilaiBarLaporan" height="160"></canvas>
            <div class="flex items-center justify-center gap-6 mt-3 text-xs text-gray-500">
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-edu-blue inline-block"></span> Kelas VII</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-edu-orange inline-block"></span> Kelas VIII</span>
                <span class="flex items-center gap-1"><span class="w-3 h-3 rounded-sm bg-green-500 inline-block"></span> Kelas IX</span>
            </div>
        </div>

        {{-- Table: 10 Siswa Nilai Terendah --}}
        <div class="bg-white rounded-xl p-6">
            <h3 class="font-bold text-gray-700 mb-4">10 Siswa dengan Nilai Terendah</h3>
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-gray-400 border-b">
                        <th class="text-left py-1.5 font-medium">NO</th>
                        <th class="text-left py-1.5 font-medium">NAMA SISWA</th>
                        <th class="text-left py-1.5 font-medium">KELAS</th>
                        <th class="text-right py-1.5 font-medium">RATA-RATA</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700">
                    @forelse($siswaTerendah ?? [] as $i => $s)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="py-1.5">{{ $i+1 }}</td>
                        <td class="py-1.5">{{ is_array($s) ? ($s['nama'] ?? '') : ($s->nama ?? $s->nama_siswa ?? '') }}</td>
                        <td class="py-1.5">{{ is_array($s) ? ($s['kelas'] ?? '') : ($s->kelas ?? '') }}</td>
                        <td class="py-1.5 text-right font-semibold text-red-600">{{ is_array($s) ? ($s['nilai'] ?? '') : ($s->rata ?? '') }}</td>
                    </tr>
                    @empty
                    @foreach([
                        ['Ahmad Fauzi','VII A','65,2'],
                        ['Siti Aisyah','VII B','68,7'],
                        ['Rizky Pratama','VIII A','69,1'],
                        ['Dewi Lestari','VIII B','70,3'],
                        ['Bima Sakti','IX A','72,6'],
                        ['Nabila Putri','IX B','73,1'],
                        ['Fahri Ramadhan','VII A','74,5'],
                        ['Putri Handayani','VIII C','75,8'],
                        ['Agus Setiawan','IX A','76,2'],
                        ['Laila Sari','IX B','78,7'],
                    ] as $idx => $row)
                    <tr class="border-b border-gray-50 hover:bg-gray-50">
                        <td class="py-1.5">{{ $idx+1 }}</td>
                        <td class="py-1.5">{{ $row[0] }}</td>
                        <td class="py-1.5">{{ $row[1] }}</td>
                        <td class="py-1.5 text-right font-semibold text-red-600">{{ $row[2] }}</td>
                    </tr>
                    @endforeach
                    @endforelse
                </tbody>
            </table>
            <div class="mt-4 text-right">
                <a href="#" class="text-edu-blue text-xs font-semibold hover:underline">Lihat Semua &rarr;</a>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Donut - Rekapitulasi Siswa
    new Chart(document.getElementById('siswaDonutLaporan').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Kelas VII', 'Kelas VIII', 'Kelas IX'],
            datasets: [{
                data: [330, 334, 326],
                backgroundColor: ['#2F5CAB', '#E04F26', '#22C55E'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            cutout: '70%',
            plugins: {
                legend: { display: false },
            }
        },
        plugins: [{
            id: 'centerTextSiswa',
            afterDraw(chart) {
                const { ctx, chartArea: { width, height, left, top } } = chart;
                ctx.save();
                ctx.font = 'bold 26px Montserrat, sans-serif';
                ctx.fillStyle = '#374151';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('990', left + width / 2, top + height / 2 - 8);
                ctx.font = '12px Lora, serif';
                ctx.fillStyle = '#9CA3AF';
                ctx.fillText('Siswa', left + width / 2, top + height / 2 + 14);
                ctx.restore();
            }
        }]
    });

    // Line Chart - Tren Kehadiran
    new Chart(document.getElementById('kehadiranLineLaporan').getContext('2d'), {
        type: 'line',
        data: {
            labels: ['Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep'],
            datasets: [
                {
                    label: 'Hadir',
                    data: [92, 93, 91, 94, 93, 95],
                    borderColor: '#2F5CAB',
                    backgroundColor: 'rgba(47,92,171,0.08)',
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#2F5CAB',
                    fill: false
                },
                {
                    label: 'Tidak Hadir',
                    data: [8, 7, 9, 6, 7, 5],
                    borderColor: '#E04F26',
                    backgroundColor: 'rgba(224,79,38,0.08)',
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#E04F26',
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: { callback: v => v + '%', font: { size: 11 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: { size: 11 } },
                    grid: { display: false }
                }
            }
        }
    });

    // Bar Chart - Nilai per Mapel
    new Chart(document.getElementById('nilaiBarLaporan').getContext('2d'), {
        type: 'bar',
        data: {
            labels: ['Matematika', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPA', 'IPS', 'PKn'],
            datasets: [
                {
                    label: 'Kelas VII',
                    data: [82, 85, 80, 83, 86, 88],
                    backgroundColor: '#2F5CAB',
                    borderRadius: 3,
                    barPercentage: 0.6
                },
                {
                    label: 'Kelas VIII',
                    data: [78, 80, 76, 79, 82, 84],
                    backgroundColor: '#E04F26',
                    borderRadius: 3,
                    barPercentage: 0.6
                },
                {
                    label: 'Kelas IX',
                    data: [75, 77, 73, 75, 78, 80],
                    backgroundColor: '#22C55E',
                    borderRadius: 3,
                    barPercentage: 0.6
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                datalabels: { display: false }
            },
            scales: {
                y: {
                    min: 0,
                    max: 100,
                    ticks: { font: { size: 10 } },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: { size: 10 } },
                    grid: { display: false }
                }
            }
        }
    });
});
</script>
@endpush
@endsection
