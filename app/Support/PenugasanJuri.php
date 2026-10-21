<?php

namespace App\Support;

use App\Models\CompetitionGroup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Baris penugasan juri, dibaca dari competition_group_judge.
 *
 * Satu-satunya tempat aturan ini hidup. Sebelumnya salinannya ada dua —
 * Eventner\Judge\Index::assignmentsByJudge() dan
 * Admin\Eventner\Struktur::penugasanPerJuri() — dan halaman publik event
 * hampir menambah salinan ketiga. Dua salinan sudah cukup untuk membuktikan
 * bahayanya: setiap perubahan label harus diingat di dua tempat.
 *
 * `scope` memisahkan empat jenis baris yang bentuknya berbeda, dan hanya
 * scope 'group' yang punya nama grup:
 *
 *   group     -> nama baris competition_groups
 *   final     -> CompetitionGroup::SCOPE_LABELS['final']
 *   ungrouped -> idem
 *   level     -> idem
 *
 * Tingkat lomba tetap dibaca lewat competition_category_id, BUKAN diturunkan
 * dari grup: baris final/ungrouped/level tak punya competition_group_id, jadi
 * menurunkan tingkat dari grup akan menghilangkan baris-baris itu.
 */
class PenugasanJuri
{
    /**
     * Penugasan seluruh juri satu event, dikelompokkan per juri lalu per tingkat.
     *
     * Tiap tingkat membawa lima kunci:
     *
     *   name    label tingkat, "Induk — Tingkat"
     *   induk   nama induk saja, untuk chip ringkas
     *   tingkat nama tingkat saja — ringkas() menyusun ulang labelnya per
     *           induk, dan menyaringnya kembali dari `name` akan mengikat
     *           hasilnya pada bentuk penggabungan, bukan pada datanya
     *   grup    true bila ada baris scope 'group' di tingkat itu — dipakai
     *           memutuskan apakah nama grupnya perlu ikut ditulis
     *   items   label tiap baris (nama grup, atau label scope)
     *
     * @return Collection<int, Collection<int, array{name: string, induk: ?string, tingkat: ?string, grup: bool, items: Collection<int, string>}>>
     *         kunci = judge_id; kunci dalam = competition_category_id
     */
    public static function perJuri(int $eventnerId): Collection
    {
        return DB::table('competition_group_judge as cgj')
            ->join('judges as j', 'j.id', '=', 'cgj.judge_id')
            ->leftJoin('competition_groups as cg', 'cg.id', '=', 'cgj.competition_group_id')
            ->leftJoin('competition_categories as cc', 'cc.id', '=', 'cgj.competition_category_id')
            ->leftJoin('competition_categories as induk', 'induk.id', '=', 'cc.parent_id')
            ->where('j.eventner_id', $eventnerId)
            ->orderBy('cgj.competition_category_id')
            ->orderBy('cgj.scope')
            ->orderBy('cgj.competition_group_id')
            ->get([
                'cgj.judge_id',
                'cgj.scope',
                'cgj.competition_category_id',
                'cg.name as group_name',
                'cc.name as level_name',
                'induk.name as parent_name',
            ])
            ->groupBy('judge_id')
            ->map(fn ($baris) => $baris
                ->groupBy('competition_category_id')
                ->map(fn ($perTingkat) => [
                    'name' => self::namaTingkat(
                        $perTingkat->first()->parent_name,
                        $perTingkat->first()->level_name
                    ),
                    'induk' => $perTingkat->first()->parent_name,
                    'tingkat' => $perTingkat->first()->level_name,
                    'grup' => $perTingkat->contains(
                        fn ($b) => $b->scope === CompetitionGroup::SCOPE_GROUP
                    ),
                    'items' => $perTingkat
                        ->map(fn ($b) => self::namaBaris($b->scope, $b->group_name))
                        ->values(),
                ])
                ->values());
    }

    /**
     * Satu baris teks tugas untuk halaman publik: tingkat-tingkat yang
     * dipegang juri, dikelompokkan per INDUK.
     *
     * Bentuk lamanya menulis "Induk — Tingkat" untuk tiap tingkat lalu
     * menyambungnya dengan " · ". Untuk juri yang memegang dua induk kali tiga
     * tingkat — keadaan biasa di event PBB — hasilnya enam ruas yang menyebut
     * nama induknya dua kali, dan kartu potret 200 px melipatnya jadi enam
     * baris. Nama induk kini ditulis SEKALI, tingkat-tingkatnya berbagi satu
     * ruas:
     *
     *   LOBB — U16 - SMP / MTS, U19 - SMA / SMK / MA, U13 - SD / MI
     *   · VARMUS — U16 - SMP / MTS, U19 - SMA / SMK / MA, U13 - SD / MI
     *
     * Tingkat yang jurinya hanya memegang sebagian grup tidak ikut dilebur:
     * nama grupnya berlaku untuk tingkat itu saja, jadi ruasnya berdiri sendiri
     * ("PBB Putri — Regu Inti · Regu Cadangan").
     *
     * Baris scope 'level' TIDAK menulis "Seluruh Tingkat" — nama tingkatnya
     * sudah menyatakan itu, dan menambahkannya membuat kalimatnya berbunyi
     * "PBB Putri — Regu Inti · Seluruh Tingkat" untuk satu fakta yang sama.
     *
     * Urutan induk mengikuti urutan tingkatnya (query mengurutkan per
     * competition_category_id), bukan urutan abjad: urutan abjad akan
     * menukar-nukar kelompok tiap kali nama induk berubah, dan pembaca
     * membandingkan daftar ini antar juri.
     *
     * @param  Collection<int, array{name: string, induk: ?string, tingkat: ?string, grup: bool, items: Collection<int, string>}>  $perTingkat
     */
    public static function ringkas(Collection $perTingkat): string
    {
        if ($perTingkat->isEmpty()) {
            return '';
        }

        $bagian = [];

        foreach ($perTingkat->groupBy(fn ($t) => $t['induk'] ?? '') as $induk => $satuInduk) {
            $polos = [];
            $rinci = [];

            foreach ($satuInduk as $t) {
                // Kategori yang baris pivot-nya masih ada tapi kategorinya
                // sudah dihapus tak punya nama tingkat (leftJoin di
                // perJuri()) — dan `name`-nya ikut kosong, karena nama itu
                // dirakit dari induk + tingkat. Barisnya dilewati alih-alih
                // mencetak pemisah menggantung; tak ada label yang hilang
                // karenanya.
                if (trim((string) $t['tingkat']) === '') {
                    continue;
                }

                if (! $t['grup']) {
                    $polos[] = $t['tingkat'];
                    continue;
                }

                $grup = $t['items']
                    ->reject(fn ($label) => in_array($label, CompetitionGroup::SCOPE_LABELS, true))
                    ->join(', ');

                $rinci[] = $grup === '' ? $t['tingkat'] : $t['tingkat'] . ' · ' . $grup;
            }

            if ($polos !== []) {
                array_unshift($rinci, implode(', ', $polos));
            }

            $isi = implode(' · ', $rinci);

            // Seluruh tingkat di induk ini kategorinya sudah terhapus: yang
            // tersisa cuma nama induknya, dan itu tetap dicetak — pemisah
            // menggantung ("PBB Putri — ") tidak.
            if ($induk === '') {
                if ($isi !== '') {
                    $bagian[] = $isi;
                }

                continue;
            }

            $bagian[] = $isi === '' ? $induk : $induk . ' — ' . $isi;
        }

        return implode(' · ', $bagian);
    }

    /**
     * Label satu tingkat, "Induk — Tingkat".
     *
     * Dipakai juga oleh halaman publik event, yang hanya butuh daftar tingkat
     * (tanpa rincian baris) untuk menyebut tugas tiap juri.
     */
    public static function namaTingkat(?string $induk, ?string $tingkat): string
    {
        return trim(($induk ? $induk . ' — ' : '') . $tingkat);
    }

    /**
     * Label satu baris penugasan: nama grup untuk scope 'group', nama scope
     * untuk tiga sisanya.
     *
     * Scope tak dikenal (mis. baris lama dari versi sebelumnya) dikembalikan
     * apa adanya, bukan jadi null — label kosong akan membuat baris tugas
     * lenyap tanpa jejak, dan panitia tak punya cara menebak bahwa ada baris
     * yang tak terbaca.
     */
    public static function namaBaris(?string $scope, ?string $groupName): string
    {
        if ($scope === CompetitionGroup::SCOPE_GROUP) {
            return (string) $groupName;
        }

        return CompetitionGroup::SCOPE_LABELS[$scope] ?? (string) $scope;
    }
}
