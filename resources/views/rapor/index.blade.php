@extends('layouts.app')

@section('title', 'Rapor')
@section('page_title', 'Rapor')
@section('page_subtitle', 'Dashboard > Rapor')

@section('content')
<div class="space-y-4">

    {{-- Filter Bar --}}
    <div class="bg-white rounded-xl p-4">
        <div class="flex flex-wrap items-center gap-3">
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Tanggal</label>
                <input type="date" value="{{ date('Y-m-d') }}"
                    class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Tahun Ajaran</label>
                <select class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                    <option>2026/2027</option>
                    <option>2025/2026</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Semester</label>
                <select class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                    <option>Genap</option>
                    <option>Ganjil</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 mb-1 block">Kelas</label>
                <select class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                    @foreach($kelasList ?? [] as $k)
                        <option>{{ $k->nama_kelas }}</option>
                    @endforeach
                    @if(empty($kelasList))
                        <option>VII A</option>
                    @endif
                </select>
            </div>
            <div class="flex-1">
                <label class="text-xs text-gray-500 mb-1 block">Cari</label>
                <div class="relative">
                    <input type="text" placeholder="Cari nama, NIS, atau kelas siswa......"
                        class="w-full border border-gray-300 rounded-lg pl-8 pr-3 py-1.5 text-sm focus:ring-2 focus:ring-edu-blue focus:outline-none">
                    <i class="fas fa-search absolute left-2.5 top-2 text-gray-400 text-sm"></i>
                </div>
            </div>
            <div class="pt-5">
                <button class="bg-edu-orange hover:bg-orange-600 text-white px-4 py-1.5 rounded-lg text-sm font-semibold flex items-center gap-2">
                    <i class="fas fa-floppy-disk"></i> Simpan Semua
                </button>
            </div>
        </div>
    </div>

    {{-- Main Content: Table + Sidebar --}}
    <div class="flex gap-4">

        {{-- Table --}}
        <div class="flex-1 bg-white rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-edu-orange text-white">
                            <th class="px-3 py-3 text-center font-semibold w-10">No.</th>
                            <th class="px-3 py-3 text-center font-semibold">NIS/NISN</th>
                            <th class="px-4 py-3 text-center font-semibold">NAMA</th>
                            <th class="px-3 py-3 text-center font-semibold w-10">L/P</th>
                            <th class="px-3 py-3 text-center font-semibold">Kelas</th>
                            <th class="px-3 py-3 text-center font-semibold">Status Rapor</th>
                            <th class="px-3 py-3 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswaList ?? [] as $index => $s)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $index + 1 }}.</td>
                            <td class="px-3 py-2.5 text-center text-gray-700">{{ $s->nis }}</td>
                            <td class="px-4 py-2.5 text-gray-800">{{ $s->nama_siswa }}</td>
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $s->jenis_kelamin }}</td>
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $s->kelas->nama_kelas ?? '-' }}</td>
                            <td class="px-3 py-2.5 text-center">
                                @if(rand(0,1))
                                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-0.5 rounded-full">Lengkap</span>
                                @else
                                    <span class="bg-red-100 text-red-600 text-xs font-semibold px-3 py-0.5 rounded-full">Belum Lengkap</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('wali.rapor.show', $s->id_siswa) }}" title="Lihat"
                                        class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-200">
                                        <i class="fas fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('wali.rapor.cetak', $s->id_siswa) }}" title="Cetak"
                                        class="w-7 h-7 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200">
                                        <i class="fas fa-print text-xs"></i>
                                    </a>
                                    <button title="Tambah" class="w-7 h-7 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                    <button title="Selesai" class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700">
                                        <i class="fas fa-check text-xs"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        {{-- Demo rows --}}
                        @foreach([['16389','Rani Arisanti Najma','P','VII A',true],['16390','Sintya Mariska J.','P','VII A',false]] as $idx => $row)
                        <tr class="border-b border-gray-100 hover:bg-gray-50">
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $idx+1 }}.</td>
                            <td class="px-3 py-2.5 text-center text-gray-700">{{ $row[0] }}</td>
                            <td class="px-4 py-2.5 text-gray-800">{{ $row[1] }}</td>
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $row[2] }}</td>
                            <td class="px-3 py-2.5 text-center text-gray-600">{{ $row[3] }}</td>
                            <td class="px-3 py-2.5 text-center">
                                @if($row[4])
                                    <span class="bg-green-100 text-green-700 text-xs font-semibold px-3 py-0.5 rounded-full">Lengkap</span>
                                @else
                                    <span class="bg-red-100 text-red-600 text-xs font-semibold px-3 py-0.5 rounded-full">Belum Lengkap</span>
                                @endif
                            </td>
                            <td class="px-3 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center hover:bg-blue-200"><i class="fas fa-eye text-xs"></i></button>
                                    <button class="w-7 h-7 rounded-full bg-gray-100 text-gray-600 flex items-center justify-center hover:bg-gray-200"><i class="fas fa-print text-xs"></i></button>
                                    <button class="w-7 h-7 rounded-full bg-green-100 text-green-600 flex items-center justify-center hover:bg-green-200"><i class="fas fa-plus text-xs"></i></button>
                                    <button class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700"><i class="fas fa-check text-xs"></i></button>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                        {{-- Empty rows --}}
                        @for($i = 0; $i < 18; $i++)
                        <tr class="border-b border-gray-100">
                            <td class="px-3 py-2.5">&nbsp;</td>
                            <td></td><td></td><td></td><td></td><td></td><td></td>
                        </tr>
                        @endfor
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="w-64 shrink-0 space-y-4">
            {{-- Status Rapor Chart --}}
            <div class="bg-white rounded-xl p-5">
                <h4 class="text-edu-blue font-bold font-montserrat mb-4">Status Rapor</h4>
                <div class="flex justify-center mb-4">
                    <canvas id="raporDonut" width="160" height="160"></canvas>
                </div>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                        <span class="text-gray-600">Lengkap 27 Siswa</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded-full bg-gray-300 inline-block"></span>
                        <span class="text-gray-600">Belum Lengkap 5 Siswa</span>
                    </div>
                </div>
            </div>

            {{-- Informasi Template --}}
            <div class="bg-white rounded-xl p-5">
                <h4 class="text-edu-blue font-bold font-montserrat mb-3 flex items-center gap-2">
                    <i class="fas fa-file-lines text-blue-400"></i> Informasi Tamplate
                </h4>
                <p class="text-gray-400 text-xs mb-1">Template Aktif</p>
                <p class="text-edu-blue font-semibold text-sm">SMP Negeri 1 Sampang - Rapor 2026/2027</p>
                <span class="inline-block mt-2 bg-gray-100 text-gray-600 text-xs px-2 py-0.5 rounded">Format Sekolah</span>
            </div>

            {{-- Info Note --}}
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-2">
                <i class="fas fa-circle-info text-blue-500 mt-0.5 shrink-0"></i>
                <p class="text-blue-700 text-xs">Pastikan semua data nilai, kehadiran, pengisisan ektrakulikuler, dan prestasi sudah lengkap sebelum cetak raport</p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('raporDonut').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Lengkap', 'Belum Lengkap'],
            datasets: [{
                data: [27, 5],
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
            },
            animation: { animateScale: true }
        },
        plugins: [{
            id: 'centerText',
            afterDraw(chart) {
                const { ctx, chartArea: { width, height, left, top } } = chart;
                ctx.save();
                ctx.font = 'bold 22px Montserrat, sans-serif';
                ctx.fillStyle = '#2F5CAB';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('84%', left + width / 2, top + height / 2 - 8);
                ctx.font = '11px Lora, serif';
                ctx.fillStyle = '#6B7280';
                ctx.fillText('Lengkap', left + width / 2, top + height / 2 + 14);
                ctx.restore();
            }
        }]
    });
});
</script>
@endpush
@endsection
