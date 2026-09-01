@extends('layouts.app')

@section('content')
    <main class="grid min-h-screen place-items-center bg-slate-100 px-6 py-12">
        <section class="max-w-lg rounded-3xl bg-white p-8 text-center shadow-xl shadow-slate-300/30">
            <img src="{{ asset('images/logo-kominfo-circle.png') }}" alt="Logo Diskominfo" class="mx-auto h-18 w-18 drop-shadow-sm">
            <p class="mt-6 text-sm font-semibold tracking-[0.2em] text-sky-700 uppercase">Dashboard APBD</p>
            <h1 class="mt-3 text-2xl font-bold text-slate-900">File dashboard tidak ditemukan</h1>
            <p class="mt-3 text-slate-600">Administrator perlu menyalin file Dashboard APBD ke lokasi publik yang telah ditentukan.</p>
            <a href="{{ route('dashboard.index') }}" class="mt-6 inline-flex rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white">Kembali ke Dashboard Realisasi Fisik</a>
        </section>
    </main>
@endsection
