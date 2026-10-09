@php
    $data = json_decode($section?->content ?? 'null', true) ?? [];
    $title = $data['title'] ?? 'Bandingkan Paket';
    $subtitle = $data['subtitle'] ?? 'Fitur dasar sudah terbuka di paket gratis. Fitur premium dibuka sekali bayar per event — tanpa langganan bulanan.';

    // Baris & kolomnya dihitung dari paket yang sama dengan section Harga,
    // jadi paket baru yang dibuat admin langsung ikut jadi kolom di sini.
    $matriks = \App\Support\Pricing::comparisonMatrix();
    $plans = $matriks['plans'];
    $rows = $matriks['rows'];

    // Kolom paket rekomendasi diberi latar tipis supaya terbaca sebagai
    // pilihan yang disarankan, selaras dengan kartu gelap di section Harga.
    $kolomRekomendasi = [];
    foreach ($plans as $i => $plan) {
        $kolomRekomendasi[$i] = (bool) ($plan['highlight'] ?? false);
    }
@endphp

<section id="perbandingan" class="section-pad bg-surface">
    <div class="container-landing">
        <div class="mx-auto max-w-2xl text-center">
            <span class="overline justify-center">Perbandingan</span>
            <h2 class="mt-4 text-3xl font-bold md:text-4xl">{{ $title }}</h2>
            <p class="mt-4 text-on-surface-variant">{{ $subtitle }}</p>
        </div>

        {{-- Tabel digulir mendatar di layar sempit, bukan ditumpuk jadi kartu:
             perbandingan hanya terbaca kalau kolom-kolomnya sejajar. --}}
        <div class="mt-12 overflow-x-auto">
            <table class="w-full min-w-[560px] border-collapse text-sm">
                <thead>
                    <tr>
                        <th scope="col" class="w-1/2 p-0"></th>
                        @foreach($plans as $i => $plan)
                            <th scope="col" class="p-0 align-bottom">
                                <div class="rounded-t-xl border border-b-0 border-outline-variant/60 px-4 py-4 text-center {{ $kolomRekomendasi[$i] ? 'bg-primary/5' : 'bg-surface-container-lowest' }}">
                                    <span class="block font-display text-base font-bold text-deep-slate">{{ $plan['name'] }}</span>
                                    <span class="mt-1 block text-xs text-on-surface-variant">
                                        @if($plan['is_free'])
                                            Rp 0
                                        @elseif($plan['is_contact'] ?? false)
                                            Kustom
                                        @elseif($plan['has_discount'] ?? false)
                                            <span class="line-through">Rp {{ number_format($plan['price'], 0, ',', '.') }}</span>
                                            Rp {{ number_format($plan['effective_price'], 0, ',', '.') }}
                                        @else
                                            Rp {{ number_format($plan['effective_price'], 0, ',', '.') }}
                                        @endif
                                    </span>
                                    @if($kolomRekomendasi[$i])
                                        <span class="mt-2 inline-block rounded-full bg-secondary px-2.5 py-0.5 text-[11px] font-bold text-deep-slate">Rekomendasi</span>
                                    @endif
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr class="border-t border-outline-variant/50">
                            <th scope="row" class="px-4 py-3.5 text-left font-medium text-on-surface">{{ $row['label'] }}</th>
                            @foreach($plans as $i => $plan)
                                @php $ada = $row['cells'][$i]; @endphp
                                <td class="px-4 py-3.5 text-center {{ $kolomRekomendasi[$i] ? 'bg-primary/5' : '' }}">
                                    @if($ada)
                                        <span class="sr-only">Termasuk</span>
                                        <i class="ti ti-check text-lg text-primary" aria-hidden="true"></i>
                                    @else
                                        <span class="sr-only">Tidak termasuk</span>
                                        <i class="ti ti-minus text-lg text-outline-variant" aria-hidden="true"></i>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="mt-8 text-center text-sm text-on-surface-variant">
            <a href="#pricing" class="font-semibold text-primary hover:underline">Lihat detail paket &amp; cara aktivasi</a>
        </p>
    </div>
</section>
