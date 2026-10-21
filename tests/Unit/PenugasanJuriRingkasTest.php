<?php

namespace Tests\Unit;

use App\Models\CompetitionGroup;
use App\Support\PenugasanJuri;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * ringkas(): satu baris tugas juri, nama induk ditulis sekali.
 *
 * Inti berkas ini adalah kasus yang dilaporkan dari data nyata — seorang juri
 * memegang dua induk kali tiga tingkat. Bentuk lama menulis "Induk — Tingkat"
 * untuk TIAP tingkat lalu menyambungnya dengan " · ", jadi enam ruas yang
 * menyebut nama kedua induknya dua kali; di kartu potret selebar 200 px,
 * ruas-ruas itu melipat jadi enam baris dan kartunya setinggi tiga kartu
 * tetangganya. Bentuk baru menulis nama induk SEKALI per kelompok.
 */
class PenugasanJuriRingkasTest extends TestCase
{
    /**
     * Satu tingkat sebagai array bentuk perJuri().
     *
     * @param  Collection<int, string>|null  $items
     * @return array{name: string, induk: ?string, tingkat: ?string, grup: bool, items: Collection<int, string>}
     */
    private function tingkat(string $induk, ?string $tingkat, bool $grup = false, ?Collection $items = null): array
    {
        return [
            'name' => PenugasanJuri::namaTingkat($induk, $tingkat),
            'induk' => $induk,
            'tingkat' => $tingkat,
            'grup' => $grup,
            'items' => $items ?? collect(),
        ];
    }

    /** Tingkat tanpa grup: baris scope 'level'/'final'/'ungrouped' tak punya nama grup. */
    private function polos(string $induk, string $tingkat): array
    {
        return $this->tingkat($induk, $tingkat);
    }

    /** Tingkat yang jurinya hanya memegang sebagian grup. */
    private function perGrup(string $induk, string $tingkat, array $namaGrup): array
    {
        return $this->tingkat($induk, $tingkat, true, collect($namaGrup));
    }

    /**
     * Kasus yang dilaporkan: dua induk kali tiga tingkat jadi DUA ruas, bukan
     * enam — dan tiap nama induk muncul tepat sekali.
     */
    public function test_dua_induk_kali_tiga_tingkat_menulis_nama_induk_sekali()
    {
        $baris = collect([
            $this->polos('LOBB', 'U13 - SD / MI'),
            $this->polos('LOBB', 'U16 - SMP / MTS'),
            $this->polos('LOBB', 'U19 - SMA / SMK / MA'),
            $this->polos('VARMUS', 'U13 - SD / MI'),
            $this->polos('VARMUS', 'U16 - SMP / MTS'),
            $this->polos('VARMUS', 'U19 - SMA / SMK / MA'),
        ]);

        $hasil = PenugasanJuri::ringkas($baris);

        $this->assertSame(
            'LOBB — U13 - SD / MI, U16 - SMP / MTS, U19 - SMA / SMK / MA'
            . ' · VARMUS — U13 - SD / MI, U16 - SMP / MTS, U19 - SMA / SMK / MA',
            $hasil
        );

        // Yang benar-benar dijaga: nama induk tak diulang tiap tingkat.
        $this->assertSame(1, substr_count($hasil, 'LOBB'));
        $this->assertSame(1, substr_count($hasil, 'VARMUS'));
    }

    /**
     * Urutan induk mengikuti urutan tingkatnya, bukan abjad.
     *
     * Query mengurutkan per competition_category_id, dan pembaca membandingkan
     * daftar ini antar juri; urutan abjad akan menukar-nukar kelompoknya tiap
     * kali nama induk berubah.
     */
    public function test_urutan_induk_mengikuti_urutan_tingkat_bukan_abjad()
    {
        $hasil = PenugasanJuri::ringkas(collect([
            $this->polos('VARMUS', 'U16'),
            $this->polos('ARKA', 'U16'),
        ]));

        $this->assertSame('VARMUS — U16 · ARKA — U16', $hasil);
    }

    /** Satu induk, satu tingkat: bentuknya sama seperti sebelumnya. */
    public function test_satu_induk_satu_tingkat_tetap_induk_tingkat()
    {
        $this->assertSame(
            'PBB Putri — Regu Inti',
            PenugasanJuri::ringkas(collect([$this->polos('PBB Putri', 'Regu Inti')]))
        );
    }

    /**
     * Tingkat yang jurinya hanya memegang sebagian grup berdiri sendiri,
     * dengan nama grupnya.
     *
     * Nama grup berlaku untuk tingkat itu saja. Meleburnya ke dalam daftar
     * tingkat akan membuat "Regu Cadangan" terbaca berlaku untuk semua tingkat
     * di induk yang sama.
     */
    public function test_tingkat_dengan_grup_tidak_dilebur_ke_daftar_tingkat()
    {
        $hasil = PenugasanJuri::ringkas(collect([
            $this->polos('PBB Putri', 'U16 - SMP / MTS'),
            $this->perGrup('PBB Putri', 'U19 - SMA / SMK / MA', ['Regu Cadangan']),
        ]));

        $this->assertSame(
            'PBB Putri — U16 - SMP / MTS · U19 - SMA / SMK / MA · Regu Cadangan',
            $hasil
        );
    }

    /** Beberapa grup di satu tingkat ditulis berderet koma. */
    public function test_beberapa_grup_di_satu_tingkat_ditulis_berderet()
    {
        $hasil = PenugasanJuri::ringkas(collect([
            $this->perGrup('PBB Putra', 'U16 - SMP / MTS', ['Regu A', 'Regu B']),
        ]));

        $this->assertSame('PBB Putra — U16 - SMP / MTS · Regu A, Regu B', $hasil);
    }

    /**
     * Baris scope 'level' TIDAK menulis "Seluruh Tingkat".
     *
     * Nama tingkatnya sudah menyatakan itu; menambahkannya membuat kalimatnya
     * berbunyi "PBB Putri — Regu Inti · Seluruh Tingkat" untuk satu fakta yang
     * sama. Yang disaring adalah LABEL scope-nya; nama grup sungguhan yang
     * kebetulan berbunyi sama tetap ikut tercetak.
     */
    public function test_label_scope_tidak_ikut_ditulis_sebagai_nama_grup()
    {
        $hasil = PenugasanJuri::ringkas(collect([
            $this->tingkat('PBB Putri', 'Regu Inti', true, collect([
                'Regu Cadangan',
                CompetitionGroup::SCOPE_LABELS[CompetitionGroup::SCOPE_LEVEL],
            ])),
        ]));

        $this->assertSame('PBB Putri — Regu Inti · Regu Cadangan', $hasil);
        $this->assertStringNotContainsString('Seluruh Tingkat', $hasil);
    }

    /**
     * Tingkat yang kategorinya sudah dihapus tidak mencetak pemisah menggantung.
     *
     * leftJoin di perJuri() membuat tingkatnya null — dan `name`-nya ikut
     * kosong, karena nama itu dirakit dari induk + tingkat. Induknya tetap
     * dicetak supaya penugasan yang masih ada di pivot tidak lenyap tanpa
     * jejak; yang dibuang cuma "Induk — " yang menggantung tanpa isi.
     */
    public function test_tingkat_tanpa_nama_kategori_tidak_mencetak_pemisah_menggantung()
    {
        $hasil = PenugasanJuri::ringkas(collect([
            ['name' => '', 'induk' => 'PBB Putri', 'tingkat' => null, 'grup' => false, 'items' => collect()],
        ]));

        $this->assertSame('PBB Putri', $hasil);

        // Tanpa induk dan tanpa tingkat, tak ada yang bisa dicetak.
        $hasil = PenugasanJuri::ringkas(collect([
            ['name' => '', 'induk' => null, 'tingkat' => '', 'grup' => false, 'items' => collect()],
        ]));

        $this->assertSame('', $hasil);
    }

    /** Juri tanpa penugasan tidak menulis apa pun — pemanggil yang memilih kata "Penilai". */
    public function test_koleksi_kosong_menghasilkan_string_kosong()
    {
        $this->assertSame('', PenugasanJuri::ringkas(collect()));
    }

    /** Induk tanpa nama (tingkatnya yatim) tetap dicetak tanpa awalan " — ". */
    public function test_induk_kosong_tidak_mencetak_pemisah()
    {
        $hasil = PenugasanJuri::ringkas(collect([
            $this->polos('', 'U16'),
            $this->polos('', 'U19'),
        ]));

        $this->assertSame('U16, U19', $hasil);
    }
}
