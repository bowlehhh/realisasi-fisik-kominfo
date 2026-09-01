@extends('layouts.app')

@section('content')
    <main class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#dbeafe,_transparent_36%),linear-gradient(180deg,_#f8fafc,_#e2e8f0)] py-3 sm:py-4">
        <header class="mx-auto mb-3 w-[calc(100vw-24px)] max-w-none sm:w-[calc(100vw-32px)] lg:w-[calc(100vw-48px)]">
            <div class="rounded-3xl border border-sky-100 bg-white/95 px-5 py-5 shadow-sm sm:px-6 sm:py-6">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-center gap-4">
                        <img src="{{ asset('images/logo-kominfo-circle.png') }}" alt="Logo Diskominfo" class="h-15 w-15 shrink-0 drop-shadow-sm sm:h-16 sm:w-16">
                        <div>
                            <p class="mb-1 text-sm font-semibold tracking-[0.2em] text-sky-700 uppercase">Pemerintah Kabupaten Kutai Barat</p>
                            <h1 class="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">{{ $title }}</h1>
                            <p class="mt-1.5 text-base text-slate-600">{{ $agency }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Baca Saja</span>
                        @if ($embedUrl)
                            <button type="button" id="refresh-dashboard" class="inline-flex items-center justify-center rounded-xl border border-sky-200 bg-white px-4 py-2.5 text-sm font-semibold text-sky-800 shadow-sm transition hover:border-sky-300 hover:bg-sky-50 focus:outline-none focus:ring-4 focus:ring-sky-100">Muat Ulang Data</button>
                        @endif
                        <a href="{{ $apbdUrl }}" class="inline-flex items-center justify-center rounded-xl bg-sky-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-800 focus:outline-none focus:ring-4 focus:ring-sky-200">Buka Dashboard APBD</a>
                        <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                            <span class="max-w-36 truncate font-medium" title="{{ $user?->name }}">{{ $user?->name }}</span>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="font-semibold text-sky-800 transition hover:text-sky-950 focus:outline-none focus:ring-2 focus:ring-sky-200">Keluar</button>
                            </form>
                        </div>
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
                        <div class="overflow-x-auto lg:overflow-x-visible">
                            <div class="excel-dashboard-stage relative min-w-[760px] lg:min-w-0">
                                <iframe id="excel-dashboard" class="relative z-10 block h-[calc(100vh-10.625rem)] min-h-[760px] w-full border-0" src="{{ $embedUrl }}" title="Dashboard Realisasi Fisik Diskominfo" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" scrolling="no" allowfullscreen onload="document.getElementById('excel-loading')?.classList.add('hidden')"><p>Browser Anda tidak mendukung iframe. Gunakan tombol “Buka Excel Online” bila tersedia.</p></iframe>
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

        <p class="mx-auto mt-4 max-w-5xl px-4 text-center text-sm leading-6 text-slate-600">Data bersumber dari file Excel Online yang sama. Setelah operator menyimpan dan memperbarui dashboard Excel, tekan “Muat Ulang Data” untuk melihat perubahan terbaru.</p>
    </main>

    @if ($embedUrl)
        <script>
            const iframe = document.getElementById('excel-dashboard');
            const loading = document.getElementById('excel-loading');
            const refreshButton = document.getElementById('refresh-dashboard');

            refreshButton?.addEventListener('click', () => {
                if (!iframe) {
                    return;
                }

                loading?.classList.remove('hidden');
                iframe.src = iframe.getAttribute('src') || iframe.src;
            });
        </script>
    @endif
@endsection
