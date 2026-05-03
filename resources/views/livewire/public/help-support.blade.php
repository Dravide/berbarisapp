<div>
    {{-- Hero Banner --}}
    <div class="section zubuz-hero-section" style="background-image: url({{ asset('templates/zubaz/assets/images/v1/hero-shape1.png') }}); padding: 100px 0 60px;">
        <div class="container">
            <div class="zubuz-hero-content center position-relative wow fadeInUp">
                <span style="display:inline-block; background: rgba(16,185,129,0.1); color: #10b981; padding: 6px 18px; border-radius: 20px; font-size: 13px; font-weight: 600; margin-bottom: 16px;">
                    <i class="fa fa-headset"></i> Support
                </span>
                <h1 style="font-size: 40px;">Bantuan & Support</h1>
                <p style="font-size: 17px; color: #6b7280; max-width: 600px; margin: 12px auto 0;">
                    Kami siap membantu Anda. Temukan jawaban atau hubungi tim support kami.
                </p>
            </div>
        </div>
    </div>

    {{-- Quick Contact Cards --}}
    <div class="section" style="padding: 50px 0 20px;">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon3.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>WhatsApp</h3>
                            <p>Hubungi kami langsung via WhatsApp untuk respon cepat.</p>
                            <a href="https://wa.me/6281234567890" target="_blank" class="zubuz-default-btn" style="background: #25D366;">
                                <span><i class="fab fa-whatsapp"></i> Chat Sekarang</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon4.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Email</h3>
                            <p>Kirim pertanyaan detail melalui email untuk respon lengkap.</p>
                            <a href="mailto:support@berbaris.app" class="zubuz-default-btn">
                                <span><i class="fa fa-envelope"></i> Kirim Email</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4 wow fadeInUp">
                    <div class="zubuz-iconbox-wrap">
                        <div class="zubuz-iconbox-icon">
                            <img src="{{ asset('templates/zubaz/assets/images/v1/icon5.png') }}" alt="">
                        </div>
                        <div class="zubuz-iconbox-data">
                            <h3>Instagram</h3>
                            <p>Ikuti kami di Instagram untuk update dan tips penggunaan.</p>
                            <a href="https://instagram.com/berbaris.app" target="_blank" class="zubuz-default-btn" style="background: linear-gradient(45deg, #f09433, #dc2743, #bc1888);">
                                <span><i class="fab fa-instagram"></i> Follow Kami</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- FAQ Section --}}
    <div class="section zubuz-section-padding3 bg-light">
        <div class="container">
            <div class="zubuz-section-title center wow fadeInUp">
                <h2>Pertanyaan yang Sering Diajukan</h2>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="zubuz-accordion-wrap" id="support-faq">

                        <div class="zubuz-accordion-item open wow fadeInUp">
                            <div class="zubuz-accordion-header">
                                <h3>Bagaimana cara mendaftar di sebuah event?</h3>
                                <div class="zubuz-active-icon">
                                    <svg width="22" height="13" viewBox="0 0 22 13" fill="none"><path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="zubuz-accordion-body">
                                <p>Buka halaman event yang ingin Anda ikuti, klik tombol "Booking Pendaftaran". Pilih kategori lomba, isi data sekolah, lalu konfirmasi booking. Anda akan menerima link magic untuk mengelola data pasukan selanjutnya.</p>
                            </div>
                        </div>

                        <div class="zubuz-accordion-item wow fadeInUp">
                            <div class="zubuz-accordion-header">
                                <h3>Bagaimana cara voting digital?</h3>
                                <div class="zubuz-active-icon">
                                    <svg width="22" height="13" viewBox="0 0 22 13" fill="none"><path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="zubuz-accordion-body">
                                <p>Masuk ke halaman voting event, pilih kategori lomba, pilih kontingen yang ingin didukung, tentukan jumlah vote, lalu lakukan pembayaran via QRIS. Setiap vote seharga Rp 1.000.</p>
                            </div>
                        </div>

                        <div class="zubuz-accordion-item wow fadeInUp">
                            <div class="zubuz-accordion-header">
                                <h3>Bagaimana cara membeli tiket event?</h3>
                                <div class="zubuz-active-icon">
                                    <svg width="22" height="13" viewBox="0 0 22 13" fill="none"><path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="zubuz-accordion-body">
                                <p>Klik tombol "Beli Tiket" di halaman event. Isi data pembeli, tentukan jumlah tiket, lalu bayar via QRIS. QR code tiket akan langsung tersedia setelah pembayaran berhasil.</p>
                            </div>
                        </div>

                        <div class="zubuz-accordion-item wow fadeInUp">
                            <div class="zubuz-accordion-header">
                                <h3>Saya lupa password, bagaimana cara reset?</h3>
                                <div class="zubuz-active-icon">
                                    <svg width="22" height="13" viewBox="0 0 22 13" fill="none"><path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="zubuz-accordion-body">
                                <p>Gunakan link magic yang dikirim saat booking pendaftaran. Jika link sudah kadaluarsa, hubungi penyelenggara event atau tim support kami via WhatsApp untuk bantuan reset password.</p>
                            </div>
                        </div>

                        <div class="zubuz-accordion-item wow fadeInUp">
                            <div class="zubuz-accordion-header">
                                <h3>Apakah bisa membatalkan pendaftaran?</h3>
                                <div class="zubuz-active-icon">
                                    <svg width="22" height="13" viewBox="0 0 22 13" fill="none"><path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="zubuz-accordion-body">
                                <p>Pendaftaran dapat dibatalkan oleh penyelenggara event atau melalui permintaan ke tim support. Status pendaftaran akan berubah menjadi "Dibatalkan". Pengembalian dana voting/tiket mengikuti kebijakan masing-masing event.</p>
                            </div>
                        </div>

                        <div class="zubuz-accordion-item wow fadeInUp">
                            <div class="zubuz-accordion-header">
                                <h3>Bagaimana cara melihat hasil kompetisi?</h3>
                                <div class="zubuz-active-icon">
                                    <svg width="22" height="13" viewBox="0 0 22 13" fill="none"><path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="zubuz-accordion-body">
                                <p>Hasil kompetisi ditampilkan di halaman event setelah penyelenggara mempublikasikannya. Anda juga dapat melihat rekapitulasi penilaian dan peringkat peserta jika fitur tersebut diaktifkan oleh penyelenggara.</p>
                            </div>
                        </div>

                        <div class="zubuz-accordion-item wow fadeInUp">
                            <div class="zubuz-accordion-header">
                                <h3>Apakah data saya aman?</h3>
                                <div class="zubuz-active-icon">
                                    <svg width="22" height="13" viewBox="0 0 22 13" fill="none"><path d="M19.75 2.25L11 11L2.25 2.25" stroke="#111827" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </div>
                            </div>
                            <div class="zubuz-accordion-body">
                                <p>Ya, kami menggunakan enkripsi SSL/TLS dan menyimpan password dalam bentuk hash. Data Anda dilindungi sesuai <a href="{{ route('privacy') }}" style="color: #0072FF; font-weight: 600;">Kebijakan Privasi</a> kami. Akses data dibatasi hanya untuk pihak yang berkepentingan.</p>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- CTA --}}
    <div class="zubuz-cta-section blue-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-7 d-flex align-items-center">
                    <div class="zubuz-default-content light">
                        <h2>Masih butuh bantuan?</h2>
                        <p>Tim support kami siap membantu Anda kapan saja. Jangan ragu untuk menghubungi kami.</p>
                        <div class="zubuz-extara-mt">
                            <a href="https://wa.me/6281234567890" target="_blank" class="zubuz-default-btn" style="background: #25D366;">
                                <span><i class="fab fa-whatsapp"></i> Hubungi via WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="zubuz-cta-thumb">
                        <img src="{{ asset('templates/zubaz/assets/images/v1/cta-mocup.png') }}" alt="">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
