{{-- Beranda + hasil pencarian e-sertifikat.

     Satu view untuk dua keadaan, dipilih dari `$this->hasil`: null = beranda,
     terisi = hasil atau keadaan kosong. Dipisah berkas bukan berarti lebih
     rapi — yang sama (judul, catatan NISN, langkah) justru jadi tiga salinan.

     Kolomnya sempit (`es-wrap`, 720px) dan isinya satu kolom: yang membuka
     halaman ini datang dari ponsel lewat tautan grup, dan yang ia cari hanya
     satu baris tombol unduh. --}}

@php
    $hasil = $this->hasil;
    $adaHasil = $hasil && $hasil['jumlah_baris'] > 0;
    $nisnDicari = \App\Services\PencarianSertifikat::normalkan($this->nisn);
    $salahFormat = $errors->has('nisn');
@endphp

<div class="grid gap-6">

    @if (! $adaHasil)

        {{-- ============================================================ beranda --}}

        <div>
            <h1 class="es-h1">
                @if ($hasil)
                    NISN {{ $nisnDicari }} belum terdaftar di lomba mana pun
                @else
                    Unduh e-sertifikat lomba Anda
                @endif
            </h1>

            <p class="es-lede">
                @if ($hasil)
                    Kami tidak menemukan sertifikat atas nomor itu. Periksa kembali angkanya sebelum menghubungi panitia.
                @else
                    Masukkan NISN untuk melihat sertifikat dari seluruh lomba baris-berbaris yang pernah Anda ikuti.
                @endif
            </p>
        </div>

        <form wire:submit="cari" class="es-card p-4 sm:p-5">
            <label for="nisn" class="es-label">NISN</label>

            <div class="flex flex-col gap-2.5 sm:flex-row">
                <input
                    id="nisn"
                    type="text"
                    wire:model="nisn"
                    inputmode="numeric"
                    autocomplete="off"
                    maxlength="14"
                    autofocus
                    class="es-field {{ $salahFormat ? 'es-field-error' : '' }}"
                    placeholder="0071234567">

                <button type="submit" class="es-btn es-btn-primary shrink-0" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="cari">Cari Sertifikat</span>
                    <span wire:loading wire:target="cari">Mencari…</span>
                </button>
            </div>

            @error('nisn')
                <p class="mt-2 text-[12.5px] text-[color:var(--color-warn)]">{{ $message }}</p>
            @enderror

            {{-- NISN vs NPSN: kekeliruan yang paling sering terjadi. Disebut
                 sekali di sini, bukan sebagai peringatan mencolok. --}}
            <p class="es-mono mt-2.5">
                10 digit, tertera di kartu pelajar, rapor, atau data pokok pendidikan sekolah. Ini NISN milik siswa, bukan NPSN sekolah.
            </p>
        </form>

        @if ($hasil)
            {{-- NISN sah tapi tidak ketemu: bukan jalan buntu, dan sebab yang
                 disebut harus sebab yang nyata. --}}
            <div class="es-card es-kosong">
                <div class="es-saran mt-0">
                    <div class="es-saran-i">
                        <span class="es-saran-n">1</span>
                        <span>Pastikan angkanya <b>10 digit</b> dan tidak tertukar dengan NPSN sekolah.</span>
                    </div>
                    <div class="es-saran-i">
                        <span class="es-saran-n">2</span>
                        <span>NISN diisi panitia saat pendaftaran — kalau salah ketik, sertifikatnya ikut tidak ketemu.</span>
                    </div>
                    <div class="es-saran-i">
                        <span class="es-saran-n">3</span>
                        <span>Peserta yang terdaftar sebagai <b>danton</b> dicari dari NISN danton, bukan NISN anggota.</span>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <button type="button" wire:click="ulangi" class="es-btn es-btn-ghost">Coba NISN lain</button>
                    <a href="{{ route('help') }}" class="es-btn es-btn-ghost">Hubungi panitia</a>
                </div>
            </div>
        @else
            <div class="es-steps">
                <div class="es-step">
                    <div class="es-step-n">01</div>
                    <div class="es-step-t">Masukkan NISN</div>
                    <div class="es-step-d">Nomor induk siswa, bukan NPSN sekolah.</div>
                </div>
                <div class="es-step">
                    <div class="es-step-n">02</div>
                    <div class="es-step-t">Pilih lomba</div>
                    <div class="es-step-d">Semua lomba Anda tampil sekaligus.</div>
                </div>
                <div class="es-step">
                    <div class="es-step-n">03</div>
                    <div class="es-step-t">Unduh PDF</div>
                    <div class="es-step-d">Satu berkas per lomba, siap cetak.</div>
                </div>
            </div>
        @endif

    @else

        {{-- =============================================================== hasil --}}

        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="es-h1 text-[19px] sm:text-[22px]">
                    {{ $hasil['nama'] ?? 'Sertifikat Anda' }}
                </h1>
                <p class="es-mono mt-1">
                    NISN {{ $nisnDicari }}
                    @if ($hasil['sekolah'])
                        · {{ $hasil['sekolah'] }}
                    @endif
                </p>
            </div>

            <button type="button" wire:click="ulangi" class="es-chip es-chip-acc">
                <span class="sm:hidden">{{ $nisnDicari }}</span>
                <span aria-hidden="true">·</span>
                Cari lagi
            </button>
        </div>

        <div class="grid gap-4">
            @foreach ($hasil['grup'] as $grup)
                <div class="es-grup">
                    <div class="es-grup-hd">
                        <span class="es-logo">
                            @if ($grup['logo'])
                                <img src="{{ asset('storage/' . $grup['logo']) }}" alt="">
                            @else
                                {{ mb_strtoupper(mb_substr($grup['nama'], 0, 2)) }}
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <div class="text-[14px] font-semibold leading-snug">{{ $grup['nama'] }}</div>
                            <div class="es-kat-s">
                                @if ($grup['venue'])
                                    <span class="hidden sm:inline">{{ $grup['venue'] }} · </span>
                                @endif
                                {{ $grup['tanggal'] ? \Carbon\Carbon::parse($grup['tanggal'])->translatedFormat('d F Y') : 'Tanggal belum ditetapkan' }}
                            </div>
                        </div>

                        <span class="es-chip shrink-0">{{ $grup['jumlah_terbit'] }}/{{ $grup['jumlah_baris'] }} siap</span>
                    </div>

                    @foreach ($grup['baris'] as $baris)
                        <div class="es-baris flex-wrap sm:flex-nowrap {{ $baris['terbit'] ? '' : 'es-baris-mati' }}">
                            <span class="es-badge shrink-0 {{ $baris['juara'] ? 'es-badge-juara' : 'es-badge-peserta' }} {{ $baris['terbit'] ? '' : 'es-badge-mati' }}">
                                {{ $baris['terbit'] ? $baris['gelar'] : 'BELUM TERBIT' }}
                            </span>

                            <div class="min-w-0 flex-1">
                                <div class="es-kat-t">{{ $baris['mata_lomba'] }}</div>
                                @if ($baris['terbit'])
                                    <div class="es-kat-s">
                                        @if ($baris['pasukan'])
                                            Pasukan {{ $baris['pasukan'] }}
                                        @else
                                            Sertifikat tingkat sekolah
                                        @endif
                                    </div>
                                @else
                                    {{-- Alasan ditulis apa adanya, sama dengan
                                         kalimat yang ditegakkan server di
                                         controller — bukan versi lain. --}}
                                    <div class="es-kat-s text-[color:var(--color-warn)]">{{ $baris['alasan'] }}</div>
                                @endif
                            </div>

                            @if ($baris['terbit'])
                                <a
                                    href="{{ route('cert.unduh', ['registration' => $baris['registration_id']]) }}"
                                    @if ($baris['kategori_juara']) title="Kategori {{ $baris['kategori_juara'] }}" @endif
                                    class="es-btn es-btn-primary w-full sm:w-auto">
                                    Unduh PDF
                                </a>
                            @else
                                <span class="es-btn es-btn-mati w-full sm:w-auto" aria-disabled="true">Unduh PDF</span>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <p class="es-mono">
            {{ $hasil['jumlah_baris'] }} sertifikat dari {{ $hasil['jumlah_event'] }} event ·
            tautan unduh berlaku {{ \App\Support\AksesUnduhSertifikat::MASA_BERLAKU_MENIT }} menit sejak pencarian.
        </p>

    @endif

</div>
