@extends('layouts.app')

@section('title', 'Evaluasi Model Machine Learning')
@section('page_title', 'Evaluasi Model AI')
@section('page_subtitle', 'Dashboard > Early Warning > Evaluasi Model AI')

@section('content')
<!-- DISCLAIMER BANNER -->
<div class="mb-6 p-4 rounded-3xl bg-gradient-to-r from-gray-900 via-indigo-950 to-purple-950 text-white shadow-lg flex flex-wrap items-center justify-between gap-4 font-heading">
    <div class="flex items-center space-x-3.5">
        <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur border border-white/20 flex items-center justify-center text-2xl flex-shrink-0">
            <i class="fa-solid fa-microchip text-indigo-400"></i>
        </div>
        <div>
            <div class="flex items-center space-x-2">
                <h3 class="text-base font-black tracking-wide">Evaluasi & Validasi Model ML Lokal</h3>
                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-gray-950 uppercase tracking-wider">
                    Data Simulasi (Ground Truth: persona_label.csv)
                </span>
            </div>
            <p class="text-xs text-indigo-200 font-medium mt-0.5">
                Pengukuran objektif ketepatan klastering, deteksi anomali, dan proyeksi tren terhadap dataset persona terkontrol.
            </p>
        </div>
    </div>
    <div>
        <a href="{{ route('operator.early-warning.ai', ['semester_id' => $selectedSemesterId]) }}" class="px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 text-white text-xs font-bold border border-white/20 transition flex items-center space-x-1.5">
            <i class="fa-solid fa-arrow-left"></i>
            <span>Kembali ke Peta AI</span>
        </a>
    </div>
</div>

@if(!empty($evalData) && ($evalData['status'] ?? '') === 'success')
<!-- METRIK GRID -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6 font-heading">
    <!-- METRIK 1: CLUSTERING (ARI & PURITY) -->
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
                <span class="text-3xl font-black text-indigo-600">{{ $evalData['clustering']['ari'] ?? 0 }}</span>
                <span class="text-xs font-bold text-gray-400">/ 1.000</span>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed mb-4">
                Mengukur tingkat kecocokan partisi klaster K-Means alami terhadap 8 persona perilaku siswa tanpa bantuan label (unsupervised).
            </p>
        </div>
        <div class="pt-3 border-t border-gray-100 flex items-center justify-between text-xs">
            <span class="text-gray-500 font-semibold">Cluster Purity:</span>
            <strong class="text-indigo-900 font-black text-sm">{{ number_format(($evalData['clustering']['purity'] ?? 0) * 100, 1) }}%</strong>
        </div>
    </div>

    <!-- METRIK 2: DETEKSI ANOMALI PERSONA P8 -->
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
                <span class="text-3xl font-black text-rose-600">{{ $evalData['anomali_p8']['f1'] ?? 0 }}</span>
                <span class="text-xs font-bold text-gray-400">/ 1.000</span>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed mb-4">
                Kemampuan model Isolation Forest dan aturan perubahan mendadak dalam mendeteksi persona P8 (siswa penurunan tajam 3 pekan terakhir).
            </p>
        </div>
        <div class="pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-center text-xs">
            <div>
                <span class="text-[10px] text-gray-400 block">Presisi (P8)</span>
                <strong class="text-gray-800 font-black">{{ number_format(($evalData['anomali_p8']['precision'] ?? 0) * 100, 1) }}%</strong>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 block">Recall (P8)</span>
                <strong class="text-gray-800 font-black">{{ number_format(($evalData['anomali_p8']['recall'] ?? 0) * 100, 1) }}%</strong>
            </div>
        </div>
    </div>

    <!-- METRIK 3: PROYEKSI TREN vs BASELINE NAIVE -->
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
                <span class="text-3xl font-black text-emerald-600">{{ $evalData['tren_prediksi']['mae_model'] ?? 0 }}</span>
                <span class="text-xs font-bold text-gray-400">poin nilai</span>
            </div>
            <p class="text-xs text-gray-500 leading-relaxed mb-4">
                Deviasi rata-rata proyeksi regresi linier TP berikutnya dibanding nilai riil, diuji dengan teknik <em>leave-last-out</em>.
            </p>
        </div>
        <div class="pt-3 border-t border-gray-100 grid grid-cols-2 gap-2 text-center text-xs">
            <div>
                <span class="text-[10px] text-gray-400 block">MAE Baseline Naive</span>
                <strong class="text-gray-700 font-bold">{{ $evalData['tren_prediksi']['mae_baseline_naive'] ?? 0 }} poin</strong>
            </div>
            <div>
                <span class="text-[10px] text-gray-400 block">Efisiensi Model</span>
                <strong class="text-emerald-600 font-black">+{{ $evalData['tren_prediksi']['peningkatan_akurasi_pct'] ?? 0 }}%</strong>
            </div>
        </div>
    </div>
</div>

<!-- SECTION PENJELASAN PRAKTIS & METODOLOGI UNTUK GURU & OPERATOR -->
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
                1. <strong>Privasi & Keamanan Data (Data Privacy):</strong> Tidak ada data nama siswa, NIS, atau catatan pribadi yang dikirim ke penyedia AI publik (OpenAI, Google Cloud, dsb). Seluruh komputasi berjalan 100% di server lokal sekolah.
            </p>
            <p>
                2. <strong>Deterministik & Dapat Diulang (Reproducible):</strong> Hasil klastering dan anomali tidak berubah secara acak (menggunakan fixed random state), sehingga keputusan evaluasi dapat dipertanggungjawabkan secara transparan.
            </p>
            <p>
                3. <strong>Bebas Biaya Operasional (Zero Cloud API Cost):</strong> Sekolah tidak memerlukan langganan API berbayar atau kuota token bulanan.
            </p>
        </div>

        <div class="p-4 rounded-2xl bg-gray-50 border border-gray-200 space-y-2">
            <h5 class="font-bold text-gray-900 font-heading text-sm flex items-center space-x-1.5">
                <i class="fa-solid fa-scale-balanced text-indigo-600"></i>
                <span>Bagaimana Cara Membaca Angka Evaluasi Ini?</span>
            </h5>
            <p>
                &bull; <strong>Adjusted Rand Index (ARI):</strong> Nilai di atas 0.40 menunjukkan model unsupervised berhasil mengelompokkan siswa ke dalam pola belajar yang sesuai dengan karakteristik aslinya.
            </p>
            <p>
                &bull; <strong>Deteksi Anomali P8:</strong> Siswa persona P8 adalah siswa yang tadinya memiliki nilai bagus, namun 3 pekan terakhir mengalami penurunan mendadak. Model berhasil memberikan peringatan dini sebelum siswa tersebut terlanjur gagal di penilaian akhir semester.
            </p>
            <p>
                &bull; <strong>Status Resmi EWS:</strong> Status risiko (Tinggi, Sedang, Kecil, Normal) pada EduTrack tetap diputuskan oleh aturan resmi Permendikbud / sekolah, sedangkan AI bertindak sebagai instrumen observasi awal bagi wali kelas.
            </p>
        </div>
    </div>
</div>
@else
<div class="bg-white rounded-3xl p-12 shadow-sm border border-gray-100 text-center font-heading">
    <i class="fa-solid fa-triangle-exclamation text-5xl text-amber-400 mb-3"></i>
    <h3 class="text-base font-bold text-gray-800">Evaluasi Model Belum Tersedia</h3>
    <p class="text-xs text-gray-500 mt-1 max-w-sm mx-auto">
        {{ $evalError ?? 'Pastikan service AI berjalan dan file ground-truth persona_label.csv tersedia di database/seeders/data/.' }}
    </p>
</div>
@endif

@endsection
