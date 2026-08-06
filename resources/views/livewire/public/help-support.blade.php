<div>
    {{-- Header --}}
    <div class="section-pad bg-surface">
        <div class="container-landing">
            <div class="text-center">
                <span class="overline justify-center">Support</span>
                <h1 class="mt-4 text-3xl font-bold leading-tight md:text-4xl font-display text-deep-slate">Bantuan & Support</h1>
                <p class="mt-4 max-w-2xl mx-auto text-on-surface-variant">Kami siap membantu Anda. Temukan jawaban atau hubungi tim support kami.</p>
            </div>
        </div>
    </div>

    {{-- Quick Contact Cards --}}
    @php
        $phone = $contact['phone'] ?? '+62 812-3456-7890';
        $email = $contact['email'] ?? 'halo@berbaris.local';
        $waNumber = preg_replace('/[^0-9]/', '', $phone);
        $instagram = $social['instagram'] ?? '';
    @endphp
    <div class="section-pad bg-surface-container-lowest">
        <div class="container-landing">
            <div class="grid gap-6 md:grid-cols-3">
                <div class="surface-card p-8 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-500">
                        <i class="ti ti-brand-whatsapp text-3xl"></i>
                    </div>
                    <h3 class="font-display text-lg font-bold text-deep-slate mb-2">WhatsApp</h3>
                    <p class="text-sm text-on-surface-variant mb-5">Hubungi kami langsung via WhatsApp untuk respon cepat.</p>
                    <a href="https://wa.me/{{ $waNumber }}" target="_blank"
                       class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 text-white px-5 py-2.5 text-sm font-bold transition hover:brightness-105 text-decoration-none">
                        <i class="ti ti-brand-whatsapp"></i> Chat Sekarang
                    </a>
                </div>

                <div class="surface-card p-8 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                        <i class="ti ti-mail text-3xl"></i>
                    </div>
                    <h3 class="font-display text-lg font-bold text-deep-slate mb-2">Email</h3>
                    <p class="text-sm text-on-surface-variant mb-5">Kirim pertanyaan detail melalui email untuk respon lengkap.</p>
                    <a href="mailto:{{ $email }}"
                       class="inline-flex items-center gap-2 rounded-xl bg-primary text-white px-5 py-2.5 text-sm font-bold transition hover:brightness-105 text-decoration-none">
                        <i class="ti ti-mail"></i> Kirim Email
                    </a>
                </div>

                <div class="surface-card p-8 text-center">
                    <div class="mx-auto mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-pink-500/10 text-pink-500">
                        <i class="ti ti-brand-instagram text-3xl"></i>
                    </div>
                    <h3 class="font-display text-lg font-bold text-deep-slate mb-2">Instagram</h3>
                    <p class="text-sm text-on-surface-variant mb-5">Ikuti kami di Instagram untuk update dan tips penggunaan.</p>
                    <a href="{{ $instagram ?: 'https://instagram.com/berbaris.app' }}" target="_blank"
                       class="inline-flex items-center gap-2 rounded-xl text-white px-5 py-2.5 text-sm font-bold transition hover:brightness-105 text-decoration-none"
                       style="background: linear-gradient(45deg,#f09433,#dc2743,#bc1888);">
                        <i class="ti ti-brand-instagram"></i> Follow Kami
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- FAQ --}}
    <div class="section-pad bg-surface">
        <div class="container-landing">
            <div class="mx-auto max-w-2xl text-center">
                <span class="overline justify-center">FAQ</span>
                <h2 class="mt-4 text-3xl font-bold md:text-4xl">Pertanyaan yang Sering Diajukan</h2>
            </div>

            <div class="mx-auto mt-10 max-w-3xl space-y-3">
                @php
                    $faqs = [
                        ['q' => 'Bagaimana cara mendaftar di sebuah event?', 'a' => 'Buka halaman event yang ingin Anda ikuti, klik tombol "Booking Pendaftaran". Pilih kategori lomba, isi data sekolah, lalu konfirmasi booking. Anda akan menerima link magic untuk mengelola data pasukan selanjutnya.'],
                        ['q' => 'Bagaimana cara voting digital?', 'a' => 'Masuk ke halaman voting event, pilih kategori lomba, pilih kontingen yang ingin didukung, tentukan jumlah vote, lalu lakukan pembayaran via QRIS.'],
                        ['q' => 'Bagaimana cara membeli tiket event?', 'a' => 'Klik tombol "Beli Tiket" di halaman event. Isi data pembeli, tentukan jumlah tiket, lalu bayar via QRIS. QR code tiket akan langsung tersedia setelah pembayaran berhasil.'],
                        ['q' => 'Saya lupa password, bagaimana cara reset?', 'a' => 'Gunakan link magic yang dikirim saat booking pendaftaran. Jika link sudah kadaluarsa, hubungi penyelenggara event atau tim support kami via WhatsApp untuk bantuan reset password.'],
                        ['q' => 'Apakah bisa membatalkan pendaftaran?', 'a' => 'Pendaftaran dapat dibatalkan oleh penyelenggara event atau melalui permintaan ke tim support. Status pendaftaran akan berubah menjadi "Dibatalkan". Pengembalian dana voting/tiket mengikuti kebijakan masing-masing event.'],
                        ['q' => 'Bagaimana cara melihat hasil kompetisi?', 'a' => 'Hasil kompetisi ditampilkan di halaman event setelah penyelenggara mempublikasikannya. Anda juga dapat melihat rekapitulasi penilaian dan peringkat peserta jika fitur tersebut diaktifkan oleh penyelenggara.'],
                        ['q' => 'Apakah data saya aman?', 'a' => 'Ya, kami menggunakan enkripsi SSL/TLS dan menyimpan password dalam bentuk hash. Data Anda dilindungi sesuai Kebijakan Privasi kami. Akses data dibatasi hanya untuk pihak yang berkepentingan.'],
                    ];
                @endphp
                @foreach($faqs as $index => $faq)
                <details class="surface-card group overflow-hidden p-0" wire:key="help-faq-{{ $index }}">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5 font-semibold text-deep-slate transition hover:bg-surface-container-low">
                        <span>{{ $faq['q'] }}</span>
                        <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary transition group-open:rotate-180">
                            <i class="ti ti-chevron-down"></i>
                        </span>
                    </summary>
                    <div class="px-5 pb-5 text-sm leading-relaxed text-on-surface-variant">
                        {{ $faq['a'] }}
                        @if($index === 6)
                            <a href="{{ route('privacy') }}" class="text-primary font-bold hover:underline">Kebijakan Privasi</a>
                        @endif
                    </div>
                </details>
                @endforeach
            </div>
        </div>
    </div>

    {{-- CTA --}}
    <div class="section-pad bg-primary text-white">
        <div class="container-landing">
            <div class="flex flex-col items-center justify-between gap-6 md:flex-row">
                <div class="max-w-xl text-center md:text-left">
                    <h2 class="font-display text-2xl font-bold md:text-3xl">Masih butuh bantuan?</h2>
                    <p class="mt-3 text-white/80">Tim support kami siap membantu Anda kapan saja. Jangan ragu untuk menghubungi kami.</p>
                </div>
                <a href="https://wa.me/{{ $waNumber }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 text-white px-6 py-3 text-sm font-bold transition hover:brightness-105 text-decoration-none shadow-lg">
                    <i class="ti ti-brand-whatsapp"></i> Hubungi via WhatsApp
                </a>
            </div>
        </div>
    </div>
</div>
