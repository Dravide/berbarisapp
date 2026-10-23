<div>
    {{-- /sponsor: tiga tingkat kerja sama. Wireframe halaman 6.
         Halaman untuk dinas, bank daerah, toko seragam, merek minuman.
         Angka paket sengaja tidak ada: nilai ditentukan per acara bersama
         sponsor, dan harga yang sudah terlihat selalu harus ditarik lagi. --}}

    <section class="eo-section-tight">
        <div class="eo-wrap">
            <div class="max-w-[640px]">
                <span class="eo-eyebrow">Sponsor &amp; Mitra</span>
                <h1 class="eo-h1">Yang didapat pihak yang membiayai.</h1>
                <p class="eo-lede">
                    Tiga tingkat. Daftar di tiap tingkat berlaku untuk semua event yang kami tangani, dan bisa disesuaikan bila acaranya lebih besar.
                </p>
            </div>
        </div>
    </section>

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap">
            {{-- Tidak ada kartu yang disorot. Ketiganya bertingkat, jadi tidak
                 ada satu tingkat yang "paling benar" untuk diangkat; yang
                 dicari sponsor adalah apa yang BERTAMBAH di tiap langkah, dan
                 itu ditulis di baris atas tiap kartu; lihat catatan di
                 EoProfile::sponsor(). --}}
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
                @foreach($tingkat as $i => $t)
                    <article class="eo-card">
                        <div class="flex items-center justify-between gap-2">
                            <span class="eo-chip">Tingkat {{ $i + 1 }}</span>
                            <span class="eo-chip eo-chip-gold">{{ $t['nama'] }}</span>
                        </div>

                        <p class="eo-small" style="color: var(--color-ink);">{{ $t['ringkas'] }}</p>

                        {{-- Yang membedakan tingkat ini dari tingkat sebelumnya.
                             Inilah yang dibaca sponsor, bukan daftar panjangnya. --}}
                        <p class="eo-small eo-muted">{{ $t['tambah'] }}</p>

                        <ul class="mt-1 flex-1 space-y-1.5 border-t pt-3" style="border-color: var(--color-line-soft);">
                            @foreach($t['butir'] as $butir)
                                <li class="text-[13px]" style="color: var(--color-ink);">{{ $butir }}</li>
                            @endforeach
                        </ul>

                        <p class="eo-mono" style="font-size: 11px;">Nilai ditentukan per acara</p>
                    </article>
                @endforeach
            </div>

            <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <div class="eo-card">
                    <h2 class="eo-h3">Yang didapat mitra di semua tingkat</h2>
                    {{-- Satu sumber: EoProfile::mitra(). Jangan menambah butir
                         langsung di sini: daftar ini juga dipakai di tempat
                         lain, dan dua sumber membuat isinya bisa berselisih
                         tanpa ada yang menyadari. --}}
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        @foreach($mitra as $m)
                            <div class="eo-fx">
                                <span class="eo-fx-ic" aria-hidden="true"></span>
                                <span class="eo-fx-nm">
                                    {{ $m['nama'] }}
                                    <span class="eo-fx-ket">{{ $m['ket'] }}</span>
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="eo-box eo-box-gold">
                    <span class="eo-box-k">Angka paket belum ditetapkan</span>
                    <p class="eo-small eo-muted">
                        Nilai kerja sama ditentukan per acara bersama sponsor, karena bergantung pada jumlah peserta, jangkauan tayangan, dan lama acara.
                        Menaruh angka di halaman ini berarti harus ditarik lagi setelah rapat pertama.
                    </p>
                </div>
            </div>

            <div class="eo-card eo-card-hi mt-6 flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
                <div class="max-w-[560px]">
                    <h2 class="eo-h3">Ingin membahas kerja sama?</h2>
                    <p class="eo-small eo-muted mt-1">
                        Tulis kebutuhan instansi Anda di formulir pengajuan. Kami balas dengan proposal yang menyebut jumlah peserta dan jangkauan tayangannya.
                    </p>
                </div>
                <a href="{{ route('eo.ajukan') }}" class="eo-btn eo-btn-primary shrink-0">Hubungi Kami</a>
            </div>
        </div>
    </section>
</div>
