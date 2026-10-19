@extends('layouts.app')

@section('title', 'Dashboard Guru Mapel')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Selamat Datang, Ibu Siti Aminah, M.Pd.')

@section('content')
<!-- 4 STAT CARDS -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <!-- Card 1: Total Siswa -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col items-center justify-center text-center">
        <h3 class="text-lg font-bold text-gray-900 font-heading mb-4">Total Siswa</h3>
        <div class="flex items-center space-x-4">
            <div class="text-edu-orange text-4xl">
                <i class="fa-solid fa-users"></i>
            </div>
            <span class="text-4xl font-black text-edu-orange font-heading">{{ $totalSiswa }}</span>
        </div>
    </div>

    <!-- Card 2: Kehadiran Hari ini -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col items-center justify-center text-center">
        <h3 class="text-lg font-bold text-gray-900 font-heading mb-4">Kehadiran Hari ini</h3>
        <div class="flex items-center space-x-4">
            <div class="text-edu-orange text-4xl">
                <i class="fa-regular fa-calendar-check"></i>
            </div>
            <span class="text-4xl font-black text-edu-orange font-heading">{{ $kehadiranHariIni }}%</span>
        </div>
    </div>

    <!-- Card 3: Rata-rata Nilai Kelas -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col items-center justify-center text-center">
        <h3 class="text-lg font-bold text-gray-900 font-heading mb-4">Rata-rata Nilai Kelas</h3>
        <div class="flex items-center space-x-4">
            <div class="text-edu-orange text-4xl">
                <i class="fa-solid fa-chart-line"></i>
            </div>
            <span class="text-4xl font-black text-edu-orange font-heading">{{ number_format($rataRataNilai, 1, ',', '.') }}</span>
        </div>
    </div>

    <!-- Card 4: Perlu Perhatian -->
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col items-center justify-center text-center">
        <h3 class="text-lg font-bold text-gray-900 font-heading mb-4">Perlu Perhatian</h3>
        <div class="flex items-center space-x-4">
            <div class="text-edu-orange text-4xl">
                <i class="fa-solid fa-user-gear"></i>
            </div>
            <span class="text-4xl font-black text-edu-orange font-heading">{{ $perluPerhatian }}</span>
        </div>
    </div>
</div>

<!-- 3 BOTTOM SECTIONS -->
<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- 1. GRAFIK KEHADIRAN -->
    <div class="lg:col-span-5 bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col justify-between">
        <div>
            <h3 class="text-lg font-extrabold text-gray-900 font-heading mb-6">Grafik Kehadiran</h3>
            <div class="relative h-64 w-full">
                <canvas id="kehadiranChartGuru"></canvas>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-600 font-medium">
            <div class="flex items-center space-x-3">
                <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-edu-blue inline-block mr-1"></span> Hadir Tepat Waktu</span>
                <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-amber-400 inline-block mr-1"></span> Sakit & Izin</span>
                <span class="flex items-center"><span class="w-3 h-3 rounded-full bg-red-400 inline-block mr-1"></span> Alpa / Tanpa Ket.</span>
            </div>
            <span class="text-[11px] font-bold text-gray-700 bg-gray-100 px-2 py-1 rounded">Rata-rata: 93.8%</span>
        </div>
    </div>

    <!-- 2. PERKEMBANGAN NILAI -->
    <div class="lg:col-span-4 bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col justify-between">
        <div>
            <h3 class="text-lg font-extrabold text-gray-900 font-heading mb-4">Perkembangan Nilai</h3>

            <!-- Donut Chart & Legend -->
            <div class="flex items-center justify-around mb-6">
                <div class="relative w-28 h-28">
                    <canvas id="nilaiDonutChartGuru"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center flex-col">
                        <span class="text-lg font-black text-gray-800 font-heading">84 %</span>
                    </div>
                </div>
                <div class="text-xs space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                        <span class="font-semibold text-gray-700">27 Siswa</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-edu-orange inline-block"></span>
                        <span class="font-semibold text-gray-700">5 Siswa</span>
                    </div>
                </div>
            </div>

            <!-- Progress Bars -->
            <div class="space-y-4">
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider font-heading">RATA-RATA EVALUASI BERKALA</p>

                <div>
                    <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                        <span>Ulangan Harian 1 (UH 1)</span>
                        <span>78.0 / 100</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-edu-blue h-2 rounded-full" style="width: 78%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                        <span>Ulangan Harian 2 (UH 2)</span>
                        <span>82.5 / 100</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-edu-blue h-2 rounded-full" style="width: 82.5%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                        <span>Penilaian Tengah Semester (PTS)</span>
                        <span>85.0 / 100</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-edu-blue h-2 rounded-full" style="width: 85%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-xs">
            <span class="text-gray-500 flex items-center">
                <span class="w-2 h-2 rounded-full bg-amber-400 inline-block mr-1.5"></span>
                Batas Akhir Input PTS: <strong>10 Okt 2026</strong>
            </span>
            <a href="{{ route('guru.nilai') }}" class="text-edu-blue hover:underline font-bold">Input Nilai Siswa &rarr;</a>
        </div>
    </div>

    <!-- 3. SISWA PERLU PERHATIAN -->
    <div class="lg:col-span-3 bg-white rounded-3xl p-6 shadow-md border border-gray-100">
        <h3 class="text-lg font-extrabold text-gray-900 font-heading mb-6">Siswa Perlu Perhatian</h3>

        <div class="space-y-5">
            @foreach($siswaPerhatian as $sp)
                <div class="flex items-center space-x-3.5 p-2 rounded-2xl hover:bg-gray-50 transition">
                    <div class="w-12 h-12 rounded-full bg-edu-orange flex items-center justify-center text-white text-xl shadow-sm flex-shrink-0">
                        <i class="fa-solid fa-user"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 font-heading">{{ $sp['nama'] }}</h4>
                        <span class="text-xs text-gray-500 font-semibold">{{ $sp['kelas'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Kehadiran Bar Chart
    const ctxKehadiran = document.getElementById('kehadiranChartGuru').getContext('2d');
    new Chart(ctxKehadiran, {
        type: 'bar',
        data: {
            labels: ['Senin\n28 Sep', 'Selasa\n29 Sep', 'Rabu\n30 Sep', 'Kamis\nHari ini', 'Jumat\n2 Okt'],
            datasets: [
                {
                    label: 'Hadir',
                    data: [92, 94, 91, 93, 89],
                    backgroundColor: ['#E2E8F0', '#E2E8F0', '#E2E8F0', '#2F5CAB', '#E2E8F0'],
                    borderRadius: 6,
                },
                {
                    label: 'Sakit/Izin',
                    data: [5, 4, 6, 5, 7],
                    backgroundColor: ['#EDF2F7', '#EDF2F7', '#EDF2F7', '#F59E0B', '#EDF2F7'],
                    borderRadius: 6,
                },
                {
                    label: 'Alpha',
                    data: [3, 2, 3, 2, 4],
                    backgroundColor: ['#F1F5F9', '#F1F5F9', '#F1F5F9', '#EF4444', '#F1F5F9'],
                    borderRadius: 6,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + '%';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    max: 100,
                    grid: { color: '#F3F4F6' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Donut Chart
    const ctxDonut = document.getElementById('nilaiDonutChartGuru').getContext('2d');
    new Chart(ctxDonut, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [84, 16],
                backgroundColor: ['#2F5CAB', '#E04F26'],
                borderWidth: 0,
                cutout: '72%'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: { enabled: false }
            }
        }
    });
</script>
@endpush
