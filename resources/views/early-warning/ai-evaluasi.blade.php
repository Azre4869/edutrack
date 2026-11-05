@extends('layouts.app')

@section('title', 'Evaluasi Model Machine Learning')
@section('page_title', 'Evaluasi Model AI')
@section('page_subtitle', 'Dashboard > Early Warning > Evaluasi Model AI')

@section('content')
@php
    $aiStatusText  = $aiServiceActive ? 'Layanan AI Aktif' : 'Layanan AI Tidak Aktif';
    $aiStatusColor = $aiServiceActive ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-rose-100 text-rose-700 border-rose-300';
    $aiStatusIcon  = $aiServiceActive ? 'fa-circle-check' : 'fa-circle-xmark';
@endphp

{{-- ======= HEADER BANNER ======= --}}
<div class="mb-6 p-4 rounded-3xl bg-gradient-to-r from-gray-900 via-indigo-950 to-purple-950 text-white shadow-lg font-heading">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center space-x-3.5">
            <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-2xl flex-shrink-0">
                <i class="fa-solid fa-microchip text-indigo-400"></i>
            </div>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-base font-black tracking-wide">Evaluasi & Validasi Model ML Lokal</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-gray-950 uppercase tracking-wider whitespace-nowrap">
                        Data Simulasi (Ground Truth: persona_label.csv)
                    </span>
                </div>
                <p class="text-xs text-indigo-200 font-medium mt-0.5">
                    Pengukuran objektif ketepatan klastering, deteksi anomali, dan proyeksi tren terhadap dataset persona terkontrol.
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            {{-- Chip status layanan AI --}}
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold border {{ $aiStatusColor }} whitespace-nowrap">
                <i class="fa-solid {{ $aiStatusIcon }} text-[10px]"></i>
                {{ $aiStatusText }}
            </span>
            <a href="{{ route('operator.early-warning.ai', ['semester_id' => $selectedSemesterId]) }}"
               class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold border border-white/20 transition flex items-center space-x-1.5">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Kembali ke Peta AI</span>
            </a>
        </div>
    </div>
</div>

{{-- ======= FLASH MESSAGES ======= --}}
@if(session('eval_success'))
<div class="mb-4 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-2xl flex items-center space-x-2 text-sm text-emerald-800 font-heading">
    <i class="fa-solid fa-circle-check text-emerald-600"></i>
    <span>{{ session('eval_success') }}</span>
</div>
@endif

{{-- ======= MAIN CONTENT ======= --}}
@if(!empty($evalData) && ($evalData['status'] ?? '') === 'success')

{{-- Waktu evaluasi tersimpan --}}
@if($evalRunAt)
<div class="mb-4 px-4 py-2.5 bg-blue-50/70 border border-blue-100 rounded-2xl flex flex-wrap items-center justify-between text-xs text-blue-900 font-heading gap-2">
    <div class="flex items-center space-x-2">
        <i class="fa-solid fa-clock-rotate-left text-edu-blue"></i>
        <span><strong>Evaluasi Terakhir:</strong>
            {{ \Carbon\Carbon::parse($evalRunAt)->translatedFormat('d M Y, H:i') }}
        </span>
    </div>
    <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-bold">Tersimpan</span>
</div>
@endif

{{-- METRIK GRID --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 font-heading">
    {{-- METRIK 1: CLUSTERING (ARI & PURITY) --}}
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <span class="text-xs font-bold text-indigo-600 uppercase tracking-wider">Klastering Unsupervised</span>
                <span class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-shapes"></i>
                </span>
            </div>
            <h4 class="text-sm font-black text-gray-900 mb-1">Adjusted Rand Index (ARI)</h4>
            <div class="flex items-baseline space-x-2 mb-3">
                <span class="text-3xl font-black text-indigo-600">{{ number_format((float)($evalData['clustering']['ari'] ?? 0), 3, ',', '.') }}</span>
                <span class="text-xs font-bold text-gray-400">/ 1,000</span>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed mb-4">
                Mengukur tingkat kecocokan partisi klaster K-Means alami terhadap 8 persona perilaku siswa tanpa bantuan label (unsupervised).
            </p>
        </div>
        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
            <span class="text-gray-500 font-semibold">Cluster Purity:</span>
            <strong class="text-indigo-900 font-black text-sm">
                {{ number_format(($evalData['clustering']['purity'] ?? 0) * 100, 1, ',', '.') }}%
            </strong>
        </div>
    </div>

    {{-- METRIK 2: DETEKSI ANOMALI PERSONA P8 --}}
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <span class="text-xs font-bold text-rose-600 uppercase tracking-wider">Deteksi Anomali (P8)</span>
                <span class="w-8 h-8 rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-bolt"></i>
                </span>
            </div>
            <h4 class="text-sm font-black text-gray-900 mb-1">F1-Score Persona P8</h4>
            <div class="flex items-baseline space-x-2 mb-3">
                <span class="text-3xl font-black text-rose-600">{{ number_format((float)($evalData['anomali_p8']['f1'] ?? 0), 3, ',', '.') }}</span>
                <span class="text-xs font-bold text-gray-400">/ 1,000</span>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed mb-4">
                Kemampuan model Isolation Forest dan aturan perubahan mendadak dalam mendeteksi persona P8 (siswa penurunan tajam 3 pekan terakhir).
            </p>
        </div>
        <div class="pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-center text-xs">
            <div>
                <span class="text-[10px] text-gray-400 block">Presisi (P8)</span>
                <strong class="text-gray-800 font-black">
                    {{ number_format(($evalData['anomali_p8']['precision'] ?? 0) * 100, 1, ',', '.') }}%
                </strong>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 block">Recall (P8)</span>
                <strong class="text-gray-800 font-black">
                    {{ number_format(($evalData['anomali_p8']['recall'] ?? 0) * 100, 1, ',', '.') }}%
                </strong>
            </div>
        </div>
    </div>

    {{-- METRIK 3: PROYEKSI TREN vs BASELINE NAIVE --}}
    <div class="bg-white rounded-3xl p-6 shadow-md border border-gray-100 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 mb-4">
                <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Akurasi Prediksi Tren</span>
                <span class="w-8 h-8 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs">
                    <i class="fa-solid fa-chart-line"></i>
                </span>
            </div>
            <h4 class="text-sm font-black text-gray-900 mb-1">Mean Absolute Error (MAE)</h4>
            <div class="flex items-baseline space-x-2 mb-3">
                <span class="text-3xl font-black text-emerald-600">
                    {{ number_format((float)($evalData['tren_prediksi']['mae_model'] ?? 0), 2, ',', '.') }}
                </span>
                <span class="text-xs font-bold text-gray-400">poin nilai</span>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed mb-4">
                Deviasi rata-rata proyeksi regresi linier TP berikutnya dibanding nilai riil, diuji dengan teknik <em>leave-last-out</em>.
            </p>
        </div>
        <div class="pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-center text-xs">
            <div>
                <span class="text-[10px] text-gray-400 block">MAE Baseline Naive</span>
                <strong class="text-gray-700 font-bold">
                    {{ number_format((float)($evalData['tren_prediksi']['mae_baseline_naive'] ?? 0), 2, ',', '.') }} poin
                </strong>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 block">Efisiensi Model</span>
                <strong class="text-emerald-600 font-black">
                    +{{ number_format((float)($evalData['tren_prediksi']['peningkatan_akurasi_pct'] ?? 0), 1, ',', '.') }}%
                </strong>
            </div>
        </div>
    </div>
</div>

{{-- TOMBOL EVALUASI ULANG (hanya jika service aktif) --}}
@if($aiServiceActive)
<div class="mb-6 flex justify-end">
    <form action="{{ route('operator.early-warning.ai.evaluasi.run') }}" method="POST"
          onsubmit="return confirm('Jalankan evaluasi ulang model AI sekarang? Proses ini memerlukan service AI aktif dan beberapa saat.')">
        @csrf
        <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
        <button type="submit"
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md flex items-center space-x-2 transition">
            <i class="fa-solid fa-rotate"></i>
            <span>Jalankan Evaluasi Ulang</span>
        </button>
    </form>
</div>
@else
<div class="mb-6 px-4 py-3 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800 font-medium flex items-center space-x-2">
    <i class="fa-solid fa-triangle-exclamation text-amber-500 flex-shrink-0"></i>
    <span>Tombol <strong>Evaluasi Ulang</strong> hanya tersedia saat layanan AI aktif. Jalankan service AI terlebih dahulu.</span>
</div>
@endif

@else

{{-- ======= STATE: DATA EVALUASI BELUM TERSEDIA ======= --}}
<div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div class="p-10 text-center">
        <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-400 flex items-center justify-center text-3xl mx-auto mb-4">
            <i class="fa-solid fa-flask-vial"></i>
        </div>
        <h3 class="text-base font-bold text-gray-800 font-heading mb-2">Hasil Evaluasi Belum Tersedia</h3>
        <p class="text-xs text-gray-500 max-w-sm mx-auto leading-relaxed mb-6">
            Belum ada data evaluasi model yang tersimpan. Jalankan analisis AI terlebih dahulu, lalu gunakan tombol
            <strong>Jalankan Evaluasi Ulang</strong> saat layanan AI aktif.
        </p>

        @if($aiServiceActive)
        <form action="{{ route('operator.early-warning.ai.evaluasi.run') }}" method="POST"
              onsubmit="return confirm('Jalankan evaluasi model AI sekarang?')"
              class="inline-block">
            @csrf
            <input type="hidden" name="semester_id" value="{{ $selectedSemesterId }}">
            <button type="submit"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md flex items-center space-x-2 transition mx-auto">
                <i class="fa-solid fa-rotate"></i>
                <span>Jalankan Evaluasi Sekarang</span>
            </button>
        </form>
        @else
        {{-- Service mati — tampilkan panduan startup yang ramah --}}
        <div class="mt-2 p-5 bg-rose-50 border border-rose-200 rounded-2xl text-left max-w-lg mx-auto">
            <div class="flex items-start space-x-3">
                <i class="fa-solid fa-circle-xmark text-rose-500 text-lg mt-0.5 flex-shrink-0"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-rose-800 mb-1">Layanan AI tidak dapat dijangkau</p>
                    <p class="text-xs text-rose-700 mb-3 leading-relaxed">
                        Jalankan perintah berikut di terminal dari folder proyek untuk mengaktifkan layanan AI:
                    </p>
                    <div class="relative">
                        <code id="startup-cmd" class="block bg-gray-900 text-emerald-300 text-[11px] font-mono px-4 py-3 rounded-xl overflow-x-auto whitespace-nowrap">
                            cd ai-service &amp;&amp; python -m uvicorn app.main:app --host 127.0.0.1 --port 8001
                        </code>
                        <button onclick="copyStartupCmd()"
                                class="absolute top-2 right-2 px-2 py-1 bg-white/10 hover:bg-white/20 text-white text-[9px] font-bold rounded border border-white/20 transition">
                            <i class="fa-regular fa-copy"></i> Salin
                        </button>
                    </div>
                    <p class="text-[10px] text-rose-500 mt-2 italic">
                        Setelah service aktif, muat ulang halaman ini dan gunakan tombol Evaluasi Ulang.
                    </p>

                    @if(isset($evalError) && $evalError)
                    <details class="mt-3">
                        <summary class="text-[10px] text-rose-600 font-bold cursor-pointer select-none hover:text-rose-800">
                            <i class="fa-solid fa-code mr-1"></i> Detail teknis (klik untuk lihat)
                        </summary>
                        <div class="mt-2 p-2 bg-white border border-rose-100 rounded-lg overflow-hidden">
                            <p class="text-[10px] text-gray-600 font-mono break-words line-clamp-4" title="{{ $evalError }}">
                                {{ $evalError }}
                            </p>
                        </div>
                    </details>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endif

{{-- ======= SECTION PENJELASAN PRAKTIS ======= --}}
<div class="bg-white rounded-3xl p-6 shadow-md border border-gray-200 font-body">
    <h3 class="text-base font-black text-gray-900 font-heading mb-4 flex items-center space-x-2">
        <i class="fa-solid fa-circle-question text-edu-blue"></i>
        <span>Panduan Interpretasi untuk Guru & Operator Sekolah</span>
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-gray-700 leading-relaxed">
        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200 space-y-2">
            <h5 class="font-bold text-gray-900 font-heading text-sm flex items-center space-x-1.5">
                <i class="fa-solid fa-bullseye text-edu-orange"></i>
                <span>Mengapa Menggunakan Model Lokal Tanpa LLM?</span>
            </h5>
            <p>
                1. <strong>Privasi & Keamanan Data:</strong> Tidak ada data nama siswa, NIS, atau catatan pribadi yang dikirim ke penyedia AI publik. Seluruh komputasi berjalan 100% di server lokal sekolah.
            </p>
            <p>
                2. <strong>Deterministik & Dapat Diulang:</strong> Hasil klastering dan anomali tidak berubah secara acak (fixed random state), sehingga keputusan evaluasi dapat dipertanggungjawabkan.
            </p>
            <p>
                3. <strong>Bebas Biaya Operasional:</strong> Sekolah tidak memerlukan langganan API berbayar atau kuota token bulanan.
            </p>
        </div>

        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200 space-y-2">
            <h5 class="font-bold text-gray-900 font-heading text-sm flex items-center space-x-1.5">
                <i class="fa-solid fa-scale-balanced text-indigo-600"></i>
                <span>Bagaimana Cara Membaca Angka Evaluasi Ini?</span>
            </h5>
            <p>
                &bull; <strong>Adjusted Rand Index (ARI):</strong> Nilai di atas 0,40 menunjukkan model unsupervised berhasil mengelompokkan siswa ke dalam pola belajar yang sesuai dengan karakteristik aslinya.
            </p>
            <p>
                &bull; <strong>Deteksi Anomali P8:</strong> Siswa persona P8 adalah siswa yang tadinya memiliki nilai bagus, namun 3 pekan terakhir mengalami penurunan mendadak. Model memberikan peringatan dini sebelum siswa tersebut terlanjur gagal.
            </p>
            <p>
                &bull; <strong>Status Resmi EWS:</strong> Status risiko (Tinggi, Sedang, Kecil, Normal) tetap diputuskan oleh aturan resmi sekolah — AI bertindak sebagai instrumen observasi awal.
            </p>
        </div>
    </div>
</div>

<script>
function copyStartupCmd() {
    const text = 'cd ai-service && python -m uvicorn app.main:app --host 127.0.0.1 --port 8001';
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector('[onclick="copyStartupCmd()"]');
        if (btn) { btn.innerHTML = '<i class="fa-solid fa-check"></i> Tersalin'; setTimeout(() => { btn.innerHTML = '<i class="fa-regular fa-copy"></i> Salin'; }, 2000); }
    });
}
</script>
@endsection
