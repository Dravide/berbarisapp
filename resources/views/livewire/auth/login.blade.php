<div class="grid min-h-screen lg:grid-cols-2">

    {{-- Panel kiri: brand (hanya desktop). Gelap supaya form di kanan jadi fokus. --}}
    <aside class="relative hidden overflow-hidden bg-deep-slate lg:flex lg:flex-col lg:justify-between p-12">
        <div class="absolute -left-24 -top-24 h-80 w-80 rounded-full bg-primary/25 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-secondary/15 blur-3xl"></div>

        <a href="{{ url('/') }}" class="relative z-10 flex items-center gap-3">
            @if(get_setting('logo_light'))
                <img src="{{ Storage::url(get_setting('logo_light')) }}" alt="{{ get_setting('site_title', 'Berbaris App') }}" class="h-10 w-auto" style="max-height: 40px; object-fit: contain;">
            @else
                <span class="font-display text-xl font-extrabold tracking-tight text-white">
                    {{ get_setting('site_title', 'Berbaris App') }}
                </span>
            @endif
        </a>

        <div class="relative z-10 max-w-md">
            <span class="overline">
                <span class="h-px w-6 bg-primary"></span>
                Platform Event &amp; Kompetisi
            </span>
            <h1 class="mt-5 font-display text-3xl font-extrabold leading-tight tracking-tight text-white xl:text-4xl">
                Kelola event dan kompetisi dari satu dasbor.
            </h1>
            <p class="mt-5 text-sm leading-relaxed text-white/70">
                Pendaftaran peserta, penilaian juri, voting, e-tiket, dan live scoreboard — semuanya terhubung.
            </p>

            <ul class="mt-8 space-y-3 text-sm text-white/80">
                @foreach([
                    ['icon' => 'ti-clipboard-check', 'text' => 'Pendaftaran peserta & verifikasi berkas'],
                    ['icon' => 'ti-gavel', 'text' => 'Penilaian juri langsung dari tablet'],
                    ['icon' => 'ti-chart-bar', 'text' => 'Live scoreboard & rekapitulasi juara'],
                    ['icon' => 'ti-ticket', 'text' => 'E-tiket dan check-in per gerbang'],
                ] as $item)
                <li class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-white/10 text-secondary">
                        <i class="ti {{ $item['icon'] }} text-base"></i>
                    </span>
                    {{ $item['text'] }}
                </li>
                @endforeach
            </ul>
        </div>

        <p class="relative z-10 text-xs text-white/40">
            &copy; {{ date('Y') }} {{ get_setting('site_title', 'Berbaris App') }}
        </p>
    </aside>

    {{-- Panel kanan: form --}}
    <main class="flex items-center justify-center bg-surface px-6 py-12 sm:px-10">
        <div class="w-full max-w-sm">

            {{-- Logo tampil di layar kecil / tablet --}}
            <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                @if(get_setting('logo_dark'))
                    <img src="{{ Storage::url(get_setting('logo_dark')) }}" alt="{{ get_setting('site_title', 'Berbaris App') }}" class="h-10 w-auto" style="max-height: 40px; object-fit: contain;">
                @else
                    <span class="font-display text-lg font-extrabold tracking-tight text-deep-slate">
                        {{ get_setting('site_title', 'Berbaris App') }}
                    </span>
                @endif
            </a>

            <h2 class="font-display text-2xl font-bold text-deep-slate">Masuk ke akun Anda</h2>
            <p class="mt-2 text-sm text-on-surface-variant">Gunakan username atau email terdaftar.</p>

            @if($errors->any())
                <div class="mt-6 rounded-lg border border-error/30 bg-error/5 px-4 py-3">
                    <div class="flex items-start gap-3">
                        <i class="ti ti-alert-circle mt-0.5 text-lg text-error"></i>
                        <div class="text-sm text-error">
                            @foreach($errors->all() as $error)
                                <p>{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit="authenticate" class="mt-6 space-y-5">
                <div>
                    <label for="inputLogin" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                        Username atau Email <span class="text-error">*</span>
                    </label>
                    <input type="text" wire:model="login" id="inputLogin"
                           class="field-input w-full @error('login') border-error @enderror"
                           placeholder="mis. nama@email.com" required autofocus autocomplete="username">
                    @error('login')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="inputPassword" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                        Password <span class="text-error">*</span>
                    </label>
                    <input type="password" wire:model="password" id="inputPassword"
                           class="field-input w-full" placeholder="Masukkan password"
                           required autocomplete="current-password">
                    @error('password')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-on-surface-variant">
                        <input type="checkbox" wire:model="remember" class="h-4 w-4 rounded border-cool-gray/40 text-primary focus:ring-2 focus:ring-primary/30">
                        Ingat saya
                    </label>
                    <a href="{{ route('help') }}" class="text-sm font-semibold text-primary hover:underline">Butuh bantuan?</a>
                </div>

                <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="authenticate">
                    <span wire:loading.remove wire:target="authenticate">Masuk</span>
                    <span wire:loading wire:target="authenticate">Memproses...</span>
                </button>
            </form>

            <div class="mt-8 border-t border-outline-variant/60 pt-6 text-center">
                <p class="text-sm text-on-surface-variant">
                    Belum punya akun eventner?
                    <a href="{{ route('register.eventner') }}" class="font-semibold text-primary hover:underline">Daftar di sini</a>
                </p>
            </div>

            <p class="mt-6 text-center text-xs text-on-surface-variant/70">
                <a href="{{ url('/') }}" class="hover:text-primary">Kembali ke beranda</a>
            </p>
        </div>
    </main>
</div>
