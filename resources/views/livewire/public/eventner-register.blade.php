@php
    $siteTitle = get_setting('site_title', 'Berbaris App');
@endphp

<div class="lg:grid lg:grid-cols-[21rem_minmax(0,1fr)]">

    {{-- Panel kiri: brand (hanya desktop). Sama bahasa desain dengan /login. --}}
    <aside class="relative hidden overflow-hidden bg-deep-slate lg:sticky lg:top-0 lg:flex lg:h-screen lg:flex-col lg:justify-between p-12">
        <div class="absolute -left-24 -top-24 h-80 w-80 rounded-full bg-primary/25 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-16 h-96 w-96 rounded-full bg-secondary/15 blur-3xl"></div>

        <a href="{{ url('/') }}" class="relative z-10 flex items-center gap-3">
            @if(get_setting('logo_light'))
                <img src="{{ Storage::url(get_setting('logo_light')) }}" alt="{{ $siteTitle }}" class="h-10 w-auto" style="max-height: 40px; object-fit: contain;">
            @else
                <span class="font-display text-xl font-extrabold tracking-tight text-white">{{ $siteTitle }}</span>
            @endif
        </a>

        <div class="relative z-10">
            <span class="overline">
                <span class="h-px w-6 bg-primary"></span>
                Akun Eventner
            </span>
            <h1 class="mt-5 font-display text-3xl font-extrabold leading-tight tracking-tight text-white">
                Satu akun untuk seluruh event Anda.
            </h1>
            <p class="mt-5 text-sm leading-relaxed text-white/70">
                Buat akun penyelenggara, pilih paket, lalu kelola lomba dari pendaftaran sampai pengumuman juara.
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
            &copy; {{ date('Y') }} {{ $siteTitle }}
        </p>
    </aside>

    {{-- Panel kanan: form pendaftaran / pembayaran --}}
    <main class="min-h-screen bg-surface px-6 py-10 sm:px-10 sm:py-14">
        <div class="mx-auto w-full max-w-2xl">

            {{-- Logo tampil di layar kecil / tablet --}}
            <a href="{{ url('/') }}" class="mb-8 flex items-center gap-3 lg:hidden">
                @if(get_setting('logo_dark'))
                    <img src="{{ Storage::url(get_setting('logo_dark')) }}" alt="{{ $siteTitle }}" class="h-10 w-auto" style="max-height: 40px; object-fit: contain;">
                @else
                    <span class="font-display text-lg font-extrabold tracking-tight text-deep-slate">{{ $siteTitle }}</span>
                @endif
            </a>

            @if($showPayment)
                {{-- ================= Pembayaran QRIS ================= --}}
                <div class="mx-auto w-full max-w-md">
                    <div class="text-center">
                        <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <i class="ti ti-qrcode text-2xl"></i>
                        </span>
                        <h1 class="mt-4 font-display text-2xl font-bold text-deep-slate">Bayar Pendaftaran</h1>
                        <p class="mt-2 text-sm text-on-surface-variant">Scan QRIS untuk mengaktifkan akun eventner.</p>
                    </div>

                    <div class="surface-card mt-6 p-5">
                        <div class="flex items-center justify-between border-b border-outline-variant/60 pb-3">
                            <span class="text-sm text-on-surface-variant">Paket</span>
                            <span class="text-sm font-semibold text-deep-slate">Berbayar</span>
                        </div>
                        <div class="mt-3 flex items-center justify-between">
                            <span class="text-sm text-on-surface-variant">Total</span>
                            <span class="font-display text-xl font-extrabold text-primary">Rp {{ number_format($paymentAmount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @if($paymentQrUrl)
                        <div class="surface-card mt-6 p-4 text-center">
                            <img src="{{ $paymentQrUrl }}" alt="QRIS" class="mx-auto" style="width: 220px; height: 220px; object-fit: contain;">
                        </div>
                    @endif

                    <p class="mt-4 text-center text-sm text-on-surface-variant">
                        Scan QRIS di atas menggunakan aplikasi e-wallet atau mobile banking.
                    </p>

                    <button type="button" class="btn-primary mt-6 w-full" wire:click="checkPayment" wire:loading.attr="disabled" wire:target="checkPayment">
                        <span wire:loading.remove wire:target="checkPayment"><i class="ti ti-refresh"></i> Cek Pembayaran</span>
                        <span wire:loading wire:target="checkPayment"><i class="ti ti-loader-2 animate-spin"></i> Memeriksa...</span>
                    </button>

                    @error('payment')
                        <div class="mt-4 rounded-lg border border-error/30 bg-error/5 px-4 py-3 text-sm text-error">
                            <i class="ti ti-alert-circle"></i> {{ $message }}
                        </div>
                    @enderror

                    <p class="mt-5 text-center text-xs text-on-surface-variant/80">
                        <i class="ti ti-info-circle"></i> Halaman ini otomatis mengecek pembayaran. Klik tombol di atas untuk mengecek manual.
                    </p>
                </div>
            @else
                {{-- ================= Form Pendaftaran ================= --}}
                <h1 class="font-display text-2xl font-bold text-deep-slate">Daftar akun eventner baru</h1>
                <p class="mt-2 text-sm text-on-surface-variant">Pilih paket, lalu isi data akun dan event Anda.</p>

                @if(session('error'))
                    <div class="mt-6 rounded-lg border border-error/30 bg-error/5 px-4 py-3">
                        <div class="flex items-start gap-3">
                            <i class="ti ti-alert-circle mt-0.5 text-lg text-error"></i>
                            <p class="text-sm text-error">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                <form wire:submit="save" class="mt-8 space-y-8">

                    {{-- ---------- Pilih Paket ---------- --}}
                    <div>
                        <div class="flex items-baseline justify-between">
                            <span class="block text-sm font-semibold text-deep-slate">Pilih Paket <span class="text-error">*</span></span>
                            <span class="text-xs text-on-surface-variant">Sekali bayar per event</span>
                        </div>

                        <div class="mt-3 grid gap-3 sm:grid-cols-2">
                            @foreach($plans as $planOption)
                                @php $selected = $plan === $planOption->slug; @endphp
                                <label wire:key="plan-{{ $planOption->slug }}"
                                       wire:click="$set('plan', '{{ $planOption->slug }}')" role="button"
                                       class="relative flex cursor-pointer flex-col rounded-xl border-2 bg-surface-container-lowest p-4 transition-all duration-200
                                              {{ $selected
                                                    ? 'border-primary shadow-[0_10px_30px_rgba(0,98,255,0.16)]'
                                                    : ($planOption->highlight ? 'border-secondary/70 hover:border-secondary' : 'border-outline-variant/60 hover:border-primary/40') }}">

                                    <span class="absolute right-3 top-3 flex h-5 w-5 items-center justify-center rounded-full border-2 {{ $selected ? 'border-primary bg-primary text-white' : 'border-outline-variant' }}">
                                        @if($selected)<i class="ti ti-check text-xs"></i>@endif
                                    </span>

                                    <div class="flex items-center gap-2 pr-7">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $selected ? 'bg-primary/10 text-primary' : 'bg-surface-container-low text-on-surface-variant' }}">
                                            <i class="ti {{ $planOption->is_free ? 'ti-gift' : 'ti-crown' }} text-lg"></i>
                                        </span>
                                        <span class="font-display text-sm font-bold {{ $selected ? 'text-primary' : 'text-deep-slate' }}">{{ $planOption->name }}</span>
                                    </div>

                                    <div class="mt-3">
                                        <span class="font-display text-xl font-extrabold {{ $selected ? 'text-primary' : 'text-deep-slate' }}">
                                            Rp {{ number_format($planOption->price, 0, ',', '.') }}
                                        </span>
                                        @if($planOption->highlight)
                                            <span class="chip ml-1 align-middle">Rekomendasi</span>
                                        @endif
                                    </div>

                                    <p class="mt-1 text-xs text-on-surface-variant">
                                        {{ $planOption->is_free ? 'Trial 3 hari, fitur terbatas' : ($planOption->description ?? 'Bayar sekali, akses fitur paket') }}
                                    </p>

                                    @unless($planOption->is_free)
                                        @php $featureKeys = $planOption->features->take(3)->pluck('feature_key'); @endphp
                                        @if($featureKeys->isNotEmpty())
                                            <ul class="mt-3 space-y-1.5 border-t border-outline-variant/50 pt-3 text-xs text-on-surface-variant">
                                                @foreach($featureKeys as $key)
                                                    <li class="flex items-start gap-1.5">
                                                        <i class="ti ti-check mt-0.5 shrink-0 text-[#5a7d00]"></i>
                                                        <span>{{ config("eventner_features.{$key}.label", $key) }}</span>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endunless
                                </label>
                            @endforeach
                        </div>

                        @error('plan')
                            <p class="mt-2 text-xs text-error"><i class="ti ti-alert-circle"></i> {{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ---------- Data Akun ---------- --}}
                    <div class="space-y-5">
                        <h2 class="border-t border-outline-variant/60 pt-6 font-display text-sm font-bold uppercase tracking-wide text-on-surface-variant">
                            Data Akun
                        </h2>

                        <div>
                            <label for="name" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                Nama Lengkap <span class="text-error">*</span>
                            </label>
                            <input type="text" wire:model.blur="name" id="name"
                                   class="field-input w-full @error('name') border-error @enderror"
                                   placeholder="Nama penyelenggara">
                            @error('name') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="username" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                    Username <span class="text-error">*</span>
                                </label>
                                <input type="text" wire:model.blur="username" id="username"
                                       class="field-input w-full @error('username') border-error @enderror"
                                       placeholder="contoh: eventku2026">
                                @error('username') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                    Email <span class="text-error">*</span>
                                </label>
                                <input type="email" wire:model.blur="email" id="email"
                                       class="field-input w-full @error('email') border-error @enderror"
                                       placeholder="email@contoh.com">
                                @error('email') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="no_hp" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                Nomor HP <span class="text-error">*</span>
                            </label>
                            <input type="tel" wire:model.blur="no_hp" id="no_hp" inputmode="tel"
                                   class="field-input w-full @error('no_hp') border-error @enderror"
                                   placeholder="08123456789">
                            @error('no_hp')
                                <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-on-surface-variant">
                                    Nomor WhatsApp aktif. Dipakai admin untuk menghubungi Anda soal pendaftaran &amp; pembayaran.
                                </p>
                            @enderror
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="password" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                    Password <span class="text-error">*</span>
                                </label>
                                <input type="password" wire:model.blur="password" id="password"
                                       class="field-input w-full @error('password') border-error @enderror"
                                       placeholder="Min. 8 karakter" autocomplete="new-password">
                                @error('password') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                    Konfirmasi Password <span class="text-error">*</span>
                                </label>
                                <input type="password" wire:model.blur="password_confirmation" id="password_confirmation"
                                       class="field-input w-full" placeholder="Ulangi password" autocomplete="new-password">
                            </div>
                        </div>
                    </div>

                    {{-- ---------- Data Event ---------- --}}
                    <div class="space-y-5">
                        <h2 class="border-t border-outline-variant/60 pt-6 font-display text-sm font-bold uppercase tracking-wide text-on-surface-variant">
                            Data Event
                        </h2>

                        <div>
                            <label for="nama_event" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                Nama Event <span class="text-error">*</span>
                            </label>
                            <input type="text" wire:model.blur="nama_event" id="nama_event"
                                   class="field-input w-full @error('nama_event') border-error @enderror"
                                   placeholder="Misal: Lomba PBB Tingkat Kabupaten 2026">
                            @error('nama_event') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="lokasi" class="mb-1.5 block text-sm font-semibold text-deep-slate">
                                Lokasi Event <span class="text-error">*</span>
                            </label>
                            <input type="text" wire:model.blur="lokasi" id="lokasi"
                                   class="field-input w-full @error('lokasi') border-error @enderror"
                                   placeholder="Misal: Lapangan Upacara SMAN 1 Sukaresmi">
                            @error('lokasi') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- ---------- Syarat & Ketentuan ---------- --}}
                    <div class="border-t border-outline-variant/60 pt-6">
                        <label class="flex cursor-pointer items-start gap-3 text-sm text-on-surface-variant">
                            <input type="checkbox" wire:model="agreeTerms" id="agreeTerms"
                                   class="mt-0.5 h-4 w-4 rounded border-cool-gray/40 text-primary focus:ring-2 focus:ring-primary/30">
                            <span>
                                Saya menyetujui
                                <a href="{{ route('terms') }}" target="_blank" class="font-semibold text-primary hover:underline">syarat &amp; ketentuan</a>
                                dan
                                <a href="{{ route('privacy') }}" target="_blank" class="font-semibold text-primary hover:underline">kebijakan privasi</a>
                                yang berlaku.
                            </span>
                        </label>
                        @error('agreeTerms') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Daftar Sekarang</span>
                        <span wire:loading wire:target="save"><i class="ti ti-loader-2 animate-spin"></i> Memproses...</span>
                    </button>
                </form>

                <div class="mt-8 border-t border-outline-variant/60 pt-6 text-center">
                    <p class="text-sm text-on-surface-variant">
                        Sudah punya akun?
                        <a href="{{ route('login') }}" class="font-semibold text-primary hover:underline">Masuk</a>
                    </p>
                </div>

                <p class="mt-6 text-center text-xs text-on-surface-variant/70">
                    <a href="{{ url('/') }}" class="hover:text-primary">Kembali ke beranda</a>
                </p>
            @endif
        </div>
    </main>
</div>
