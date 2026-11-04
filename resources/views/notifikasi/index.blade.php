@extends('layouts.app')

@section('title', 'Notifikasi')
@section('page_title', 'Notifikasi')
@section('page_subtitle', 'Dashboard > Notifikasi')

@section('content')
@php
    $role = auth()->user()->role;
    $readRouteName = 'wali.notifikasi.read';
    if ($role === 'Operator') {
        $readRouteName = 'operator.notifikasi.read';
    } elseif ($role === 'Guru Mapel') {
        $readRouteName = 'guru.notifikasi.read';
    }
@endphp

<div class="space-y-4">
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <i class="fa-solid fa-circle-check text-emerald-600"></i>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        {{-- Semua --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4 shadow-sm border border-gray-100">
            <div class="w-14 h-14 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                <i class="fas fa-bell text-2xl text-edu-blue"></i>
            </div>
            <div>
                <p class="text-edu-blue text-sm font-semibold">Semua</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $total }}</p>
            </div>
        </div>
        {{-- Belum dibaca --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4 shadow-sm border border-gray-100">
            <div class="w-14 h-14 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                <i class="fas fa-envelope text-2xl text-red-500"></i>
            </div>
            <div>
                <p class="text-red-500 text-sm font-semibold">Belum dibaca</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $belumDibaca }}</p>
            </div>
        </div>
        {{-- Tugas --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4 shadow-sm border border-gray-100">
            <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center shrink-0">
                <i class="fas fa-clipboard-list text-2xl text-green-500"></i>
            </div>
            <div>
                <p class="text-green-600 text-sm font-semibold">Tugas</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $tugas }}</p>
            </div>
        </div>
        {{-- Pengumuman --}}
        <div class="bg-white rounded-xl p-5 flex items-center gap-4 shadow-sm border border-gray-100">
            <div class="w-14 h-14 rounded-full bg-yellow-100 flex items-center justify-center shrink-0">
                <i class="fas fa-bullhorn text-2xl text-yellow-500"></i>
            </div>
            <div>
                <p class="text-yellow-600 text-sm font-semibold">Pengumuman</p>
                <p class="text-4xl font-bold text-gray-800 font-montserrat">{{ $pengumuman }}</p>
            </div>
        </div>
    </div>

    {{-- Notification List --}}
    <div class="bg-white rounded-xl p-6 space-y-6 shadow-sm border border-gray-100">
        <div class="flex items-center justify-between border-b border-gray-100 pb-4">
            <h2 class="text-lg font-bold text-gray-800">Daftar Notifikasi</h2>
            @if($isDemoData)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    <i class="fa-solid fa-circle-info mr-1"></i> Data contoh
                </span>
            @endif
        </div>

        @if(empty($hariIni) && empty($kemarin))
            <div class="text-center py-12 text-gray-500">
                <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center text-gray-400 text-2xl">
                    <i class="fa-regular fa-bell-slash"></i>
                </div>
                <p class="font-semibold text-gray-700">Belum ada notifikasi baru</p>
                <p class="text-xs text-gray-400 mt-1">Semua aktivitas dan pemberitahuan penting akan muncul di sini.</p>
            </div>
        @else
            {{-- Hari ini --}}
            @if(!empty($hariIni))
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-3 h-3 rounded-full bg-edu-blue inline-block"></span>
                    <h3 class="font-bold text-gray-700 text-sm">Hari ini</h3>
                </div>
                <div class="space-y-2">
                    @foreach($hariIni as $notif)
                    <div class="flex items-center justify-between gap-4 p-4 rounded-xl {{ $notif['unread'] ? 'bg-blue-50/70 border border-blue-100' : 'bg-gray-50 border border-gray-100 opacity-80' }} transition">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                                {{ $notif['tipe'] === 'warning' ? 'bg-red-100' : ($notif['tipe'] === 'success' ? 'bg-emerald-100' : 'bg-blue-100') }}">
                                <i class="fas {{ $notif['tipe'] === 'warning' ? 'fa-triangle-exclamation text-red-500' : ($notif['tipe'] === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-bullhorn text-blue-500') }}"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800 text-sm">{{ $notif['judul'] }}</p>
                                <p class="text-gray-600 text-xs mt-0.5">{{ $notif['deskripsi'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @if($notif['unread'])
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block" title="Belum dibaca"></span>
                                <form method="POST" action="{{ route($readRouteName, $notif['id']) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-edu-blue hover:text-edu-darkblue font-semibold px-2 py-1 bg-white border border-blue-200 rounded-md hover:bg-blue-50 transition" title="Tandai sudah dibaca">
                                        <i class="fa-solid fa-check mr-1"></i> Dibaca
                                    </button>
                                </form>
                            @endif
                            <span class="text-gray-400 text-xs font-mono">{{ $notif['waktu'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Kemarin & Sebelumnya --}}
            @if(!empty($kemarin))
            <div>
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-3 h-3 rounded-full bg-gray-400 inline-block"></span>
                    <h3 class="font-bold text-gray-700 text-sm">Kemarin & Sebelumnya</h3>
                </div>
                <div class="space-y-2">
                    @foreach($kemarin as $notif)
                    <div class="flex items-center justify-between gap-4 p-4 rounded-xl {{ $notif['unread'] ? 'bg-blue-50/70 border border-blue-100' : 'bg-gray-50 border border-gray-100 opacity-80' }} transition">
                        <div class="flex items-center gap-4 flex-1">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center shrink-0
                                {{ $notif['tipe'] === 'warning' ? 'bg-red-100' : ($notif['tipe'] === 'success' ? 'bg-emerald-100' : 'bg-blue-100') }}">
                                <i class="fas {{ $notif['tipe'] === 'warning' ? 'fa-triangle-exclamation text-red-500' : ($notif['tipe'] === 'success' ? 'fa-circle-check text-emerald-600' : 'fa-circle-info text-blue-500') }}"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-700 text-sm">{{ $notif['judul'] }}</p>
                                <p class="text-gray-500 text-xs mt-0.5">{{ $notif['deskripsi'] }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 shrink-0">
                            @if($notif['unread'])
                                <span class="w-2.5 h-2.5 rounded-full bg-red-500 inline-block" title="Belum dibaca"></span>
                                <form method="POST" action="{{ route($readRouteName, $notif['id']) }}">
                                    @csrf
                                    <button type="submit" class="text-xs text-edu-blue hover:text-edu-darkblue font-semibold px-2 py-1 bg-white border border-blue-200 rounded-md hover:bg-blue-50 transition" title="Tandai sudah dibaca">
                                        <i class="fa-solid fa-check mr-1"></i> Dibaca
                                    </button>
                                </form>
                            @endif
                            <span class="text-gray-400 text-xs font-mono">{{ $notif['waktu'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        @endif

    </div>
</div>
@endsection
