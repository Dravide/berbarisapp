<div>
    {{-- /ajukan — formulir pengajuan event. Wireframe halaman 7.
         Empat isian wajib, sisanya boleh dikira-kira: formulir yang menuntut
         angka pasti membuat orang menutup halaman dan menelepon, dan telepon
         tidak tercatat di mana pun. --}}

    <section class="eo-section-tight">
        <div class="eo-wrap">
            <div class="max-w-[640px]">
                <span class="eo-eyebrow">Pengajuan</span>
                <h1 class="eo-h1">Ceritakan lomba yang ingin Anda selenggarakan.</h1>
                <p class="eo-lede">
                    Empat isian wajib. Perkiraan tanggal dan jumlah peserta boleh dikira-kira — kami balas lewat WhatsApp pada hari kerja berikutnya.
                </p>
            </div>
        </div>
    </section>

    <hr class="eo-rule">

    <section class="eo-section">
        <div class="eo-wrap">
            <div class="grid grid-cols-1 gap-3 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">

                {{-- Formulir. --}}
                <div>
                    @if($terkirim)
                        <div class="eo-box mb-3" style="border-color: var(--color-accent); background: var(--color-accent-soft);" role="status">
                            <p class="text-[13.5px] font-semibold" style="color: var(--color-ink);">Pengajuan Anda sudah kami terima.</p>
                            <p class="eo-small eo-muted">
                                Kami balas lewat WhatsApp pada hari kerja berikutnya. Kalau perlu menambah data, kirim pengajuan baru saja — keduanya kami catat.
                            </p>
                        </div>
                    @endif

                    <form wire:submit="kirim" class="eo-card" style="gap: 16px;">
                        <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-2">
                            @foreach($formulir as $f)
                                @php
                                    $nama = $f['nama'];
                                    // Kolom lebar penuh untuk instansi dan surel;
                                    // sisanya setengah supaya formulir tidak memanjang.
                                    $lebar = in_array($nama, ['instansi', 'email'], true) ? 'sm:col-span-2' : '';
                                @endphp

                                <div class="{{ $lebar }}">
                                    <label for="eo-{{ $nama }}" class="eo-label">
                                        {{ $f['label'] }}@if($f['wajib'])<span aria-hidden="true" style="color: var(--color-warn);"> *</span><span class="sr-only">(wajib)</span>@else<span style="text-transform: none; letter-spacing: 0;"> — opsional</span>@endif
                                    </label>

                                    @if($f['tipe'] === 'select')
                                        <select id="eo-{{ $nama }}" wire:model="jenis_lomba"
                                                class="eo-field @error($nama) eo-field-error @enderror"
                                                @if($f['wajib']) required @endif>
                                            @foreach($jenisLomba as $jenis)
                                                <option value="{{ $jenis }}">{{ $jenis }}</option>
                                            @endforeach
                                        </select>
                                    @elseif($f['tipe'] === 'date')
                                        <input type="date" id="eo-{{ $nama }}" wire:model="tanggal"
                                               min="{{ now()->toDateString() }}"
                                               class="eo-field @error($nama) eo-field-error @enderror">
                                    @elseif($f['tipe'] === 'number')
                                        <input type="number" id="eo-{{ $nama }}" wire:model="jumlah_peserta"
                                               min="1" inputmode="numeric" placeholder="mis. 500"
                                               class="eo-field @error($nama) eo-field-error @enderror">
                                    @elseif($f['tipe'] === 'tel')
                                        <input type="tel" id="eo-{{ $nama }}" wire:model="whatsapp"
                                               inputmode="tel" placeholder="0812xxxxxxx" autocomplete="tel"
                                               class="eo-field @error($nama) eo-field-error @enderror"
                                               @if($f['wajib']) required @endif>
                                    @else
                                        <input type="{{ $f['tipe'] }}" id="eo-{{ $nama }}" wire:model="{{ $nama }}"
                                               class="eo-field @error($nama) eo-field-error @enderror"
                                               @if($f['wajib']) required @endif>
                                    @endif

                                    @error($nama)
                                        <p class="mt-1 text-[12px]" style="color: var(--color-warn);">{{ $message }}</p>
                                    @enderror
                                </div>
                            @endforeach
                        </div>

                        {{-- Pekerjaan yang diinginkan. Daftarnya dari
                             EoProfile::pekerjaanPilihan() supaya tidak pernah
                             berselisih dengan halaman /layanan. --}}
                        <div class="border-t pt-4" style="border-color: var(--color-line-soft);">
                            <span class="eo-label">Pekerjaan yang dibutuhkan — boleh lebih dari satu</span>
                            <div class="flex flex-wrap gap-2">
                                @foreach($pekerjaanPilihan as $pilihan)
                                    <label class="eo-chip cursor-pointer" style="gap: 6px;">
                                        <input type="checkbox" wire:model="pekerjaan" value="{{ $pilihan }}"
                                               class="h-3.5 w-3.5 rounded-sm" style="accent-color: var(--color-accent);">
                                        <span>{{ $pilihan }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="border-t pt-4" style="border-color: var(--color-line-soft);">
                            <label for="eo-catatan" class="eo-label">Catatan — opsional</label>
                            <textarea id="eo-catatan" wire:model="catatan" rows="3"
                                      placeholder="Mis. dua hari, dua panggung, peserta dari luar kota."
                                      class="eo-field @error('catatan') eo-field-error @enderror"></textarea>
                            @error('catatan')
                                <p class="mt-1 text-[12px]" style="color: var(--color-warn);">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-col items-start gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between" style="border-color: var(--color-line-soft);">
                            <p class="eo-mono" style="font-size: 11px;">Data hanya dipakai untuk membalas pengajuan ini.</p>
                            <button type="submit" class="eo-btn eo-btn-primary w-full sm:w-auto"
                                    wire:loading.attr="disabled" wire:target="kirim">
                                <span wire:loading.remove wire:target="kirim">Kirim Pengajuan</span>
                                <span wire:loading wire:target="kirim">Mengirim…</span>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Sisi kanan: apa yang terjadi sesudahnya, plus kontak langsung
                     kalau admin sudah mengisinya. Tidak ada nomor contoh. --}}
                <div class="space-y-3">
                    <div class="eo-card">
                        <h2 class="eo-h3">Setelah dikirim</h2>
                        <div class="space-y-3">
                            @foreach([
                                ['Kami balas hari kerja berikutnya', 'lewat WhatsApp ke nomor yang Anda tulis'],
                                ['Konsultasi singkat 15 menit', 'untuk memastikan kategori dan tanggalnya masuk'],
                                ['Proposal & RAB dikirim', 'rincian per baris, tanpa biaya'],
                            ] as $i => [$judul, $ket])
                                <div class="eo-step">
                                    <span class="eo-step-n">{{ $i + 1 }}</span>
                                    <span class="eo-step-nm">
                                        {{ $judul }}
                                        <span class="eo-step-ket">{{ $ket }}</span>
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="eo-box">
                        <span class="eo-box-k">Kontak langsung</span>
                        @if($waUrl || !empty($kontak['email']))
                            <ul class="space-y-1.5 text-[13.5px]">
                                @if($waUrl)
                                    <li><a href="{{ $waUrl }}" target="_blank" rel="noopener" class="font-semibold no-underline hover:underline">WhatsApp tim EO</a></li>
                                @endif
                                @if(!empty($kontak['email']))
                                    <li><a href="mailto:{{ $kontak['email'] }}" class="font-semibold no-underline hover:underline">{{ $kontak['email'] }}</a></li>
                                @endif
                            </ul>
                        @else
                            <p class="eo-small eo-muted">
                                Kontak langsung belum diisi. Formulir di samping tetap sampai ke kami.
                            </p>
                        @endif
                    </div>

                    <div class="eo-box">
                        <span class="eo-box-k">Belum yakin lingkupnya?</span>
                        <p class="eo-small eo-muted">
                            Lihat dulu <a href="{{ route('eo.layanan') }}" class="font-semibold">lima kelompok kerja</a> dan
                            <a href="{{ route('eo.alur') }}" class="font-semibold">tujuh langkahnya</a>.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
