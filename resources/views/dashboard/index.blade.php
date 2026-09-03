@extends('layouts.app')

@section('content')
    <main class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#dbeafe,_transparent_36%),linear-gradient(180deg,_#f8fafc,_#e2e8f0)] py-3 sm:py-4">
        <header class="mx-auto mb-3 w-[calc(100vw-24px)] max-w-none sm:w-[calc(100vw-32px)] lg:w-[calc(100vw-48px)]">
            <div class="rounded-3xl border border-sky-100 bg-white/95 px-5 py-4 shadow-sm sm:px-6 sm:py-5">
                <div class="grid gap-4 lg:grid-cols-[auto_20rem_1fr] lg:items-start">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/logo-kominfo-circle.png') }}" alt="Logo Diskominfo" class="h-15 w-15 shrink-0 drop-shadow-sm sm:h-16 sm:w-16">
                        <div>
                            <p class="mb-1 text-sm font-semibold tracking-[0.2em] text-sky-700 uppercase">Pemerintah Kabupaten Kutai Barat</p>
                            <h1 class="text-2xl leading-tight font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h1>
                            <p class="mt-1 text-base text-slate-600">{{ $agency }}</p>
                        </div>
                    </div>

                    <div class="flex min-w-60 flex-col gap-3 lg:mt-6">
                        <a href="{{ $apbdUrl }}" class="inline-flex items-center justify-center rounded-xl bg-sky-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200">Detail Realisasi Fisik dan Keuangan</a>
                        <a href="{{ route('dashboard.iku') }}" class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-white px-4 py-2 text-sm font-semibold text-sky-800 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100">Dashboard IKU</a>
                        <a href="{{ route('dashboard.ikk') }}" class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-white px-4 py-2 text-sm font-semibold text-sky-800 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100">Dashboard IKK</a>
                    </div>

                    <div class="flex w-fit items-center gap-2 self-start rounded-xl border border-slate-200 bg-slate-50/90 px-2 py-1.5 shadow-sm lg:mt-6 lg:justify-self-end">
                        <div class="flex min-w-0 items-center gap-1.5 text-sm text-slate-700">
                            <span class="grid h-6 w-6 shrink-0 place-items-center rounded-lg bg-sky-100 text-[0.6875rem] font-bold text-sky-800" aria-hidden="true">A</span>
                            <span class="max-w-32 truncate font-medium" title="{{ $user?->name }}">{{ $user?->name }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-xs font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100 hover:text-rose-800 focus:outline-none focus:ring-4 focus:ring-rose-100">
                                <span aria-hidden="true">↪</span>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <section id="excel-dashboard-panel" class="mx-auto w-[calc(100vw-24px)] max-w-none border border-slate-200 bg-white shadow-xl shadow-slate-300/30 sm:w-[calc(100vw-32px)] lg:w-[calc(100vw-48px)]">
                <div class="flex flex-col gap-4 border-b border-slate-200 bg-slate-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Dashboard Excel Online</h2>
                        <p class="mt-1 text-sm text-slate-600">Data diisi oleh operator melalui spreadsheet. Website ini tidak menyediakan pengubahan data.</p>
                    </div>

                    @if ($excelSourceUrl)
                        <a href="{{ $excelSourceUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex shrink-0 items-center justify-center rounded-xl border border-sky-200 bg-white px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:border-sky-300 hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100">Buka Excel Online</a>
                    @endif
                </div>

                @if ($embedUrl)
                    <div class="relative w-full min-w-0 bg-slate-100">
                        <div id="excel-loading" class="absolute inset-0 z-30 grid place-items-center bg-slate-100 text-center text-sm text-slate-600" role="status" aria-live="polite"><div><div class="mx-auto mb-3 h-9 w-9 animate-spin rounded-full border-4 border-sky-100 border-t-sky-700"></div>Memuat Dashboard Excel Online…</div></div>
                        <div class="border-b border-slate-200 bg-white px-4 py-3 sm:hidden">
                            <a href="{{ $embedUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex w-full items-center justify-center rounded-xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-800 transition hover:border-sky-300 hover:bg-sky-100 focus:outline-none focus:ring-4 focus:ring-sky-100">Buka Excel layar penuh</a>
                            <p class="mt-2 text-center text-xs leading-5 text-slate-600">Geser dashboard ke samping untuk melihat seluruh kolom.</p>
                        </div>
                        <div class="overflow-x-auto overscroll-x-contain lg:overflow-x-visible">
                            <div class="excel-dashboard-stage relative min-w-[640px] sm:min-w-[760px] lg:min-w-0">
                                <iframe id="excel-dashboard" class="relative z-10 block h-[70svh] min-h-[30rem] w-full border-0 sm:h-[calc(100vh-10.625rem)] sm:min-h-[760px]" src="{{ $embedUrl }}" title="Dashboard Realisasi Fisik Diskominfo" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" scrolling="no" allowfullscreen onload="document.getElementById('excel-loading')?.classList.add('hidden')"><p>Browser Anda tidak mendukung iframe. Gunakan tombol “Buka Excel Online” bila tersedia.</p></iframe>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="grid min-h-96 place-items-center px-6 py-14 text-center">
                        <div class="max-w-xl">
                            <div class="mx-auto mb-5 grid h-14 w-14 place-items-center rounded-2xl bg-sky-100 text-2xl" aria-hidden="true">▦</div>
                            <h2 class="text-xl font-bold text-slate-900">Dashboard Realisasi Fisik sedang disiapkan.</h2>
                        </div>
                    </div>
                @endif
        </section>

        <p class="mx-auto mt-4 max-w-5xl px-4 text-center text-sm leading-6 text-slate-600">Data bersumber dari file Excel Online yang sama. Perubahan yang disimpan operator akan tersedia saat halaman dibuka kembali.</p>
    </main>
@endsection
