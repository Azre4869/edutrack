@extends('layouts.app')

@section('title', 'Dashboard Guru Mapel')
@section('page_title', 'Dashboard')
@section('page_subtitle')
    Selamat Datang, {{ auth()->user()->guru ? auth()->user()->guru->nama_guru : auth()->user()->username }}
@endsection

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
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-extrabold text-gray-900 font-heading">Grafik Kehadiran</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="fa-solid fa-chart-line mr-1"></i> Data Real-time
                </span>
            </div>
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
            <span class="text-[11px] font-bold text-gray-700 bg-gray-100 px-2 py-1 rounded">Rata-rata: {{ $kehadiranRataRataPekan }}%</span>
        </div>
    </div>

    <!-- 2. PERKEMBANGAN NILAI -->
    <div class="lg:col-span-4 bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-extrabold text-gray-900 font-heading">Perkembangan Nilai</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                    <i class="fa-solid fa-chart-pie mr-1"></i> Data Real-time
                </span>
            </div>

            <!-- Donut Chart & Legend -->
            <div class="flex items-center justify-around mb-6">
                <div class="relative w-28 h-28">
                    <canvas id="nilaiDonutChartGuru"></canvas>
                    <div class="absolute inset-0 flex items-center justify-center flex-col">
                        <span class="text-lg font-black text-gray-800 font-heading">{{ $persentaseKetuntasan }} %</span>
                    </div>
                </div>
                <div class="text-xs space-y-2">
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                        <span class="font-semibold text-gray-700">{{ $tuntasCount }} Siswa Tuntas</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="w-3 h-3 rounded-full bg-edu-orange inline-block"></span>
                        <span class="font-semibold text-gray-700">{{ $belumTuntasCount }} Siswa Perlu Bimbingan</span>
                    </div>
                </div>
            </div>

            <!-- Progress Bars -->
            <div class="space-y-4">
                <p class="text-[11px] font-bold text-gray-500 uppercase tracking-wider font-heading">RATA-RATA EVALUASI BERKALA</p>

                <div>
                    <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                        <span>Ulangan Harian 1 (UH 1)</span>
                        <span>{{ number_format($avgUh1, 1, ',', '.') }} / 100</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-edu-blue h-2 rounded-full" style="width: {{ min(100, $avgUh1) }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                        <span>Ulangan Harian 2 (UH 2)</span>
                        <span>{{ number_format($avgUh2, 1, ',', '.') }} / 100</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-edu-blue h-2 rounded-full" style="width: {{ min(100, $avgUh2) }}%"></div>
                    </div>
                </div>

                <div>
                    <div class="flex justify-between text-xs font-semibold text-gray-700 mb-1">
                        <span>Penilaian Tengah Semester (PTS)</span>
                        <span>{{ number_format($avgPts, 1, ',', '.') }} / 100</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-edu-blue h-2 rounded-full" style="width: {{ min(100, $avgPts) }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-6 pt-4 border-t border-gray-100 flex items-center justify-between text-xs">
            <span class="text-gray-500 flex items-center">
                <span class="w-2 h-2 rounded-full bg-amber-400 inline-block mr-1.5"></span>
                Total Data: <strong>{{ $totalBernilai }} Siswa</strong>
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
    const kehadiranLabels = {!! json_encode($kehadiranLabels) !!};
    const hadirData = {!! json_encode($kehadiranHadir) !!};
    const sakitIzinData = {!! json_encode($kehadiranSakitIzin) !!};
    const alpaData = {!! json_encode($kehadiranAlpa) !!};

    const hadirColors = hadirData.map((_, i) => i === hadirData.length - 1 ? '#2F5CAB' : '#CBD5E1');
    const sakitColors = sakitIzinData.map((_, i) => i === sakitIzinData.length - 1 ? '#F59E0B' : '#EDF2F7');
    const alpaColors = alpaData.map((_, i) => i === alpaData.length - 1 ? '#EF4444' : '#F1F5F9');

    new Chart(ctxKehadiran, {
        type: 'bar',
        data: {
            labels: kehadiranLabels,
            datasets: [
                {
                    label: 'Hadir',
                    data: hadirData,
                    backgroundColor: hadirColors,
                    borderRadius: 6,
                },
                {
                    label: 'Sakit/Izin',
                    data: sakitIzinData,
                    backgroundColor: sakitColors,
                    borderRadius: 6,
                },
                {
                    label: 'Alpha',
                    data: alpaData,
                    backgroundColor: alpaColors,
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
                data: [{{ $persentaseKetuntasan }}, {{ max(0, 100 - $persentaseKetuntasan) }}],
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
