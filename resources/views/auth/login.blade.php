@extends('layouts.app')

@section('content')
    <main class="min-h-screen bg-slate-100 p-4 sm:p-6 lg:grid lg:place-items-center lg:p-8">
        <section class="mx-auto grid w-full max-w-5xl overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_24px_70px_rgba(15,23,42,0.14)] lg:grid-cols-[0.9fr_1.1fr]">
            <aside class="hidden min-h-[640px] flex-col justify-between bg-[#20126c] p-10 text-white lg:flex">
                <div>
                    <img src="{{ asset('images/logo-kominfo-circle.png') }}" alt="Logo Diskominfo" class="h-20 w-20 drop-shadow-[0_10px_16px_rgba(0,0,0,0.24)]">
                    <p class="mt-10 text-xs font-semibold tracking-[0.2em] text-sky-200 uppercase">Pemerintah Kabupaten Kutai Barat</p>
                    <h1 class="mt-4 max-w-sm text-4xl font-bold leading-tight tracking-tight">Dashboard Realisasi Fisik</h1>
                    <p class="mt-5 max-w-sm text-base leading-7 text-indigo-100">Satu ruang kerja untuk memantau perkembangan pelaksanaan kegiatan secara terarah.</p>
                </div>

                <div class="border-t border-white/15 pt-6">
                    <p class="text-sm font-medium text-white">Diskominfo Kabupaten Kutai Barat</p>
                    <p class="mt-1 text-sm text-indigo-200">Akses khusus operator</p>
                </div>
            </aside>

            <div class="flex min-h-[640px] items-center px-6 py-10 sm:px-10 lg:px-14">
                <div class="w-full max-w-md">
                    <div class="flex items-center gap-3 lg:hidden">
                        <img src="{{ asset('images/logo-kominfo-circle.png') }}" alt="Logo Diskominfo" class="h-14 w-14 shrink-0">
                        <div>
                            <p class="text-xs font-semibold tracking-[0.16em] text-sky-800 uppercase">Pemerintah Kabupaten Kutai Barat</p>
                            <p class="mt-1 text-sm font-medium text-slate-600">Diskominfo Kabupaten Kutai Barat</p>
                        </div>
                    </div>

                    <div class="mt-10 lg:mt-0">
                        <p class="text-sm font-semibold text-sky-800">Selamat datang kembali</p>
                        <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Masuk ke dashboard</h2>
                        <p class="mt-3 text-base leading-7 text-slate-600">Gunakan akun operator Anda untuk melanjutkan ke Dashboard Realisasi Fisik.</p>
                    </div>

                    @if ($errors->any())
                        <div class="mt-7 rounded-2xl border border-red-200 bg-red-50 px-4 py-3.5 text-sm font-medium text-red-800" role="alert">
                            Email atau kata sandi tidak sesuai. Silakan periksa kembali data Anda.
                        </div>
                    @endif

                    <form class="mt-8 grid gap-5" method="POST" action="{{ route('login.store') }}">
                        @csrf

                        <div class="grid gap-2">
                            <label for="email" class="text-sm font-semibold text-slate-800">Email</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus placeholder="nama@instansi.go.id" class="rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-sky-700 focus:ring-4 focus:ring-sky-100">
                        </div>

                        <div class="grid gap-2">
                            <label for="password" class="text-sm font-semibold text-slate-800">Password</label>
                            <div class="relative">
                                <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3.5 pr-28 text-slate-900 outline-none transition focus:border-sky-700 focus:ring-4 focus:ring-sky-100">
                                <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 px-4 text-sm font-semibold text-sky-800 transition hover:text-sky-950 focus:outline-none focus:ring-2 focus:ring-sky-200" aria-controls="password" aria-pressed="false">Tampilkan</button>
                            </div>
                        </div>

                        <label class="flex items-center gap-2.5 text-sm text-slate-700">
                            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-sky-700 focus:ring-sky-600">
                            Ingat saya di perangkat ini
                        </label>

                        <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-[#21146f] px-4 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#180e56] focus:outline-none focus:ring-4 focus:ring-indigo-200">Masuk</button>
                    </form>

                    <p class="mt-8 border-t border-slate-200 pt-5 text-sm leading-6 text-slate-500">Sistem ini digunakan untuk kebutuhan pemantauan internal Diskominfo Kabupaten Kutai Barat.</p>
                </div>
            </div>
        </section>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('toggle-password');

        passwordToggle?.addEventListener('click', () => {
            const isHidden = passwordInput?.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            passwordToggle.textContent = isHidden ? 'Sembunyikan' : 'Tampilkan';
            passwordToggle.setAttribute('aria-pressed', String(isHidden));
        });
    </script>
@endsection
