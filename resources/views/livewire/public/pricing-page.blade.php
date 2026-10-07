{{--
    Halaman /pricing memakai komponen section yang sama dengan laman depan.
    Dulu kartunya disalin ulang di sini, jadi setiap perubahan tampilan harus
    dikerjakan dua kali dan keduanya sempat berbeda diam-diam: halaman ini
    menampilkan seluruh daftar fitur tanpa lipatan, sedangkan laman depan
    menyembunyikan sisanya di balik "Lihat N fitur lain".

    `$section` belum tentu ada — pemanggilnya adalah komponen Livewire
    PricingPage, bukan LandingPage — jadi variabelnya disiapkan di sini
    (section null = judul & subjudul bawaan).
--}}
@include('components.landing.pricing', ['section' => $section ?? null])
