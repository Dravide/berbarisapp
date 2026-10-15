<div>
    {{-- Katalog fitur lengkap. Landing cuma menjual sembilan ringkasan; daftar
         penuhnya (8 kelompok) hidup di sini. Isinya dari
         App\Support\ProductFeatures — kode, bukan Setting, jadi tidak ada
         editor untuk halaman ini di panel.

         Kartu sengaja TIDAK menautkan apa pun. `route` di katalog hanya
         rujukan untuk tes & penelusuran: hampir semua route itu butuh login
         atau konteks event (slug), jadi menautkannya ke pengunjung anonim
         berarti mendarat di halaman login atau 500. --}}
    <div class="section-pad bg-surface">
        <div class="container-landing">
            <div class="mx-auto max-w-2xl text-center">
                <span class="overline justify-center">Fitur</span>
                <h1 class="mt-4 font-display text-3xl font-bold leading-tight text-deep-slate md:text-4xl">Semua yang Bisa Anda Lakukan</h1>
                <p class="mt-4 text-on-surface-variant">{{ $total }} fitur dalam {{ count($groups) }} kelompok kerja — dari formulir pendaftaran pertama sampai laporan keuangan setelah acara selesai.</p>
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register.eventner') }}" class="btn-primary">Daftar Eventner</a>
                <a href="{{ route('pricing') }}" class="btn-ghost">Lihat Harga</a>
            </div>

            {{-- Chip kategori. Setiap anchor punya section-nya sendiri di
                 bawah — nol tautan mati di halaman ini. --}}
            <nav class="mt-10 flex flex-wrap items-center justify-center gap-2" aria-label="Kelompok fitur">
                @foreach($groups as $group)
                <a href="#g-{{ $group['key'] }}" class="chip transition hover:bg-primary/20">{{ $group['title'] }}</a>
                @endforeach
            </nav>
        </div>
    </div>

    @foreach($groups as $group)
    <section id="g-{{ $group['key'] }}" class="section-pad {{ $loop->even ? 'bg-surface-container-low' : 'bg-surface-container-lowest' }}">
        <div class="container-landing">
            <div class="max-w-2xl">
                <h2 class="text-2xl font-bold md:text-3xl">{{ $group['title'] }}</h2>
                <p class="mt-3 text-on-surface-variant">{{ $group['description'] }}</p>
            </div>

            {{-- Tiga kolom tetap, jadi tidak ada satu pun kelas grid yang
                 dirakit dari variabel — Tailwind memindai berkas dan tidak
                 pernah menjalankan Blade (pelajaran di komponen harga). --}}
            <div class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($group['items'] as $item)
                <div class="surface-card surface-card-hover p-6">
                    <div class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <i class="ti {{ $item['icon'] }} text-xl"></i>
                    </div>

                    <x-landing.feature-miniature :item="['miniature' => $item['miniature']]" />

                    <h3 class="text-lg font-bold text-deep-slate">{{ $item['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $item['description'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>
    @endforeach

    <section class="section-pad bg-deep-slate">
        <div class="container-landing text-center">
            <h2 class="text-2xl font-bold text-white md:text-3xl">Siap mencoba?</h2>
            <p class="mx-auto mt-4 max-w-2xl text-white/70">Kelola perlombaan sekolah dengan gratis. Fitur lanjutan dibuka sekali bayar per event — tanpa langganan bulanan.</p>
            <div class="mt-8 flex flex-wrap items-center justify-center gap-3">
                <a href="{{ route('register.eventner') }}" class="btn-primary">Mulai Sekarang</a>
                <a href="{{ route('help') }}" class="btn-ghost-light">Hubungi Kami</a>
            </div>
        </div>
    </section>
</div>
