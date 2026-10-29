<?php

namespace App\Services;

use App\Models\Eventner;
use App\Models\Participant;
use App\Models\Registration;
use Illuminate\Support\Collection;

/**
 * Pencarian sertifikat berdasarkan NISN — satu NISN, seluruh event.
 *
 * Dipakai laman publik e-sertifikat. Bentuk keluarannya sudah siap tampil
 * (dikelompokkan per event, tiap baris membawa sebabnya sendiri bila belum
 * bisa diunduh) supaya view-nya tidak perlu tahu apa pun tentang model.
 *
 * Dua hal yang sengaja dikerjakan di sini dan bukan di view:
 *
 * 1. **NISN dicari di dua kolom.** Peserta bisa terdaftar sebagai anggota
 *    pasukan (`participants.nisn`) atau sebagai danton
 *    (`registrations.danton_nisn`) — danton tidak punya baris di tabel
 *    participants. Mencari satu kolom saja membuat danton tidak menemukan
 *    sertifikatnya sendiri.
 * 2. **Baris yang belum bisa diunduh tetap dikembalikan**, lengkap dengan
 *    alasannya. Menyembunyikannya membuat peserta yang lombanya belum terbit
 *    melihat halaman kosong dan menyimpulkan NISN-nya salah.
 */
class PencarianSertifikat
{
    /** @var array<int, array{terbit: bool, alasan: ?string}> */
    private array $gerbangCache = [];

    public function __construct(
        private SertifikatKontingen $kontingen,
        private CertificatePdfBuilder $builder,
    ) {}

    /**
     * Buang semua kecuali digit.
     *
     * NISN selalu 10 angka dan kerap disalin dari rapor atau kartu pelajar
     * dalam bentuk "007 1234 5678" atau "007.1234.5678". Membandingkannya apa
     * adanya berarti orang yang menyalin persis seperti yang tercetak justru
     * tidak menemukan apa pun.
     *
     * Sengaja string, bukan integer: NISN berawalan nol, dan mengubahnya jadi
     * angka akan membuang digit pertama.
     */
    public static function normalkan(?string $nisn): string
    {
        return preg_replace('/\D+/', '', (string) $nisn) ?? '';
    }

    /** Panjang NISN yang dianggap sah. */
    public const PANJANG_NISN = 10;

    public static function formatSah(string $nisn): bool
    {
        return strlen($nisn) === self::PANJANG_NISN;
    }

    /**
     * Cari seluruh sertifikat satu NISN.
     *
     * @return array{
     *     nama: ?string, sekolah: ?string, jumlah_event: int, jumlah_baris: int,
     *     grup: array<int, array{event_id:int, nama:string, tanggal:?string,
     *                            venue:?string, logo:?string, baris:array<int, array>}>
     * }
     */
    public function cari(string $nisn): array
    {
        $registrations = $this->registrasiUntukNisn($nisn);

        if ($registrations->isEmpty()) {
            return [
                'nama' => null,
                'sekolah' => null,
                'jumlah_event' => 0,
                'jumlah_baris' => 0,
                'grup' => [],
            ];
        }

        $baris = [];

        foreach ($registrations as $reg) {
            foreach ($this->barisUntukRegistrasi($reg) as $b) {
                $baris[] = $b;
            }
        }

        return [
            'nama' => $this->namaTerdeteksi($nisn, $registrations),
            'sekolah' => $this->sekolahTerdeteksi($registrations),
            'jumlah_event' => $registrations->pluck('eventner_id')->unique()->count(),
            'jumlah_baris' => count($baris),
            'grup' => $this->kelompokkanPerEvent($baris),
        ];
    }

    /**
     * Registrasi yang memuat NISN ini — sebagai anggota pasukan ATAU sebagai
     * danton.
     *
     * Event yang belum disetujui dan pendaftaran yang dibatalkan disaring di
     * sini, bukan di view: keduanya bukan sertifikat yang boleh terbit, dan
     * menyaring lebih awal berarti keduanya juga tidak ikut terhitung di
     * ringkasan jumlah.
     *
     * @return Collection<int, Registration>
     */
    private function registrasiUntukNisn(string $nisn): Collection
    {
        $dariAnggota = Participant::where('nisn', $nisn)->pluck('registration_id');

        return Registration::query()
            ->where(function ($q) use ($nisn, $dariAnggota) {
                $q->where('danton_nisn', $nisn);

                if ($dariAnggota->isNotEmpty()) {
                    $q->orWhereIn('id', $dariAnggota);
                }
            })
            ->where('status_berkas', '!=', 'dibatalkan')
            ->whereHas('eventner', fn ($q) => $q->where('status', 'approved'))
            ->with(['eventner', 'competitionCategory.parent', 'participants'])
            ->get()
            ->sortByDesc(fn ($reg) => $reg->eventner?->tanggal ?? '')
            ->values();
    }

    /**
     * Baris sertifikat satu registrasi: satu per kemenangan, atau satu PESERTA.
     *
     * @return array<int, array>
     */
    private function barisUntukRegistrasi(Registration $reg): array
    {
        $eventner = $reg->eventner;
        $kemenangan = $this->kontingen->kemenangan($reg);

        // Gelar yang ditampilkan per baris jatuh ke PESERTA bila pasukan ini
        // tidak masuk jajaran juara mana pun — sertifikat keikutsertaan tetap
        // terbit, bukan 404.
        $gelar = $kemenangan === []
            ? [null]
            : array_map(fn ($k) => $k, $kemenangan);

        return array_map(
            fn ($k) => $this->bentukBaris($reg, $eventner, $k),
            $gelar
        );
    }

    private function bentukBaris(Registration $reg, ?Eventner $eventner, ?array $kemenangan): array
    {
        $kategori = $reg->competitionCategory;
        $mataLomba = $kategori
            ? (($kategori->parent_id && $kategori->parent)
                ? $kategori->parent->name . ' — ' . $kategori->name
                : $kategori->name)
            : 'Kategori lomba';

        $terbit = $this->bisaDiterbitkan($eventner);

        return [
            'registration_id' => $reg->id,
            'event_id' => $eventner?->id,
            'event_nama' => $eventner?->nama_event ?? '',
            'event_tanggal' => $eventner?->tanggal,
            'event_venue' => $eventner?->venue,
            'event_logo' => $eventner?->logo_event,
            'mata_lomba' => $mataLomba,
            'pasukan' => $reg->label_pasukan ?: null,
            'gelar' => $kemenangan['title'] ?? 'PESERTA',
            'juara' => $kemenangan !== null,
            'rank' => $kemenangan['rank'] ?? null,
            'kategori_juara' => $kemenangan['championCategory']->name ?? null,
            'terbit' => $terbit['terbit'],
            'alasan' => $terbit['alasan'],
        ];
    }

    /**
     * Boleh tidaknya sertifikat event ini terbit, beserta sebabnya bila tidak.
     *
     * Dua gerbangnya sama dengan unduhan portal, dan kalimat alasannya pun
     * sama supaya yang dibaca peserta memang syarat yang ditegakkan server.
     *
     * Hasilnya di-cache per event di dalam satu permintaan: satu NISN bisa
     * punya beberapa pasukan di event yang sama, dan memeriksa ulang berarti
     * menembak query template yang sama berulang kali.
     *
     * @return array{terbit: bool, alasan: ?string}
     */
    private function bisaDiterbitkan(?Eventner $eventner): array
    {
        if (! $eventner) {
            return ['terbit' => false, 'alasan' => 'Event tidak ditemukan.'];
        }

        if (isset($this->gerbangCache[$eventner->id])) {
            return $this->gerbangCache[$eventner->id];
        }

        if (! $eventner->canAccessFeature('certificate')) {
            return $this->gerbangCache[$eventner->id] = [
                'terbit' => false,
                'alasan' => 'Fitur sertifikat belum aktif untuk event ini.',
            ];
        }

        if (! $this->builder->activeTemplate($eventner->id)) {
            return $this->gerbangCache[$eventner->id] = [
                'terbit' => false,
                'alasan' => 'Panitia belum memasang template sertifikat untuk event ini.',
            ];
        }

        return $this->gerbangCache[$eventner->id] = ['terbit' => true, 'alasan' => null];
    }

    /**
     * Kelompokkan baris per event, urut terbaru dulu.
     *
     * Di dalam satu event, baris juara tampil lebih dulu (peringkat terbaik di
     * atas), lalu PESERTA, dan baris yang belum bisa diunduh paling bawah —
     * yang dicari hampir semua orang adalah sertifikat juaranya.
     *
     * @param  array<int, array>  $baris
     */
    private function kelompokkanPerEvent(array $baris): array
    {
        $grup = [];

        foreach ($baris as $b) {
            $grup[$b['event_id']]['event_id'] = $b['event_id'];
            $grup[$b['event_id']]['nama'] = $b['event_nama'];
            $grup[$b['event_id']]['tanggal'] = $b['event_tanggal'];
            $grup[$b['event_id']]['venue'] = $b['event_venue'];
            $grup[$b['event_id']]['logo'] = $b['event_logo'];
            $grup[$b['event_id']]['baris'][] = $b;
        }

        foreach ($grup as &$g) {
            usort($g['baris'], function ($a, $b) {
                if ($a['terbit'] !== $b['terbit']) {
                    return $a['terbit'] ? -1 : 1;
                }

                $ra = $a['rank'] ?? PHP_INT_MAX;
                $rb = $b['rank'] ?? PHP_INT_MAX;

                if ($ra !== $rb) {
                    return $ra <=> $rb;
                }

                return strcmp($a['mata_lomba'], $b['mata_lomba']);
            });

            $g['jumlah_terbit'] = count(array_filter($g['baris'], fn ($b) => $b['terbit']));
            $g['jumlah_baris'] = count($g['baris']);
        }

        unset($g);

        // Event terbaru lebih dulu; yang tanggalnya kosong paling bawah supaya
        // tidak mendorong event yang jelas tanggalnya ke bawah lipatan.
        usort($grup, function ($a, $b) {
            return strcmp((string) ($b['tanggal'] ?? ''), (string) ($a['tanggal'] ?? ''));
        });

        return array_values($grup);
    }

    /**
     * Nama orang yang cocok dengan NISN ini, null bila ambigu atau tak ada.
     *
     * Satu NISN bisa tercatat pada beberapa baris: sebagai anggota di satu
     * pasukan dan sebagai danton di pasukan lain. Bila nama yang ditemukan
     * berbeda-beda, tidak ada satu nama yang benar untuk ditulis di judul —
     * lebih baik judulnya menyebut NISN saja daripada menampilkan nama orang
     * yang salah.
     *
     * @param  Collection<int, Registration>  $registrations
     */
    private function namaTerdeteksi(string $nisn, Collection $registrations): ?string
    {
        $nama = [];

        foreach ($registrations as $reg) {
            foreach ($reg->participants as $p) {
                if (self::normalkan($p->nisn) === $nisn && $p->nama) {
                    $nama[$p->nama] = true;
                }
            }

            // Danton tidak punya baris participants; namanya di registrasi.
            if (self::normalkan($reg->danton_nisn) === $nisn && $reg->danton_nama) {
                $nama[$reg->danton_nama] = true;
            }
        }

        return count($nama) === 1 ? array_key_first($nama) : null;
    }

    /** Nama sekolah, null bila NISN ini muncul di lebih dari satu sekolah. */
    private function sekolahTerdeteksi(Collection $registrations): ?string
    {
        $sekolah = $registrations->pluck('nama_sekolah')->filter()->unique();

        return $sekolah->count() === 1 ? $sekolah->first() : null;
    }
}
