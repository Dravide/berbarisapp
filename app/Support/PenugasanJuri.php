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
     * Tiap tingkat membawa tiga kunci:
     *
     *   name  label tingkat, "Induk — Tingkat"
     *   induk nama induk saja, untuk chip ringkas
     *   grup  true bila ada baris scope 'group' di tingkat itu — dipakai
     *         memutuskan apakah nama grupnya perlu ikut ditulis
     *   items label tiap baris (nama grup, atau label scope)
     *
     * @return Collection<int, Collection<int, array{name: string, induk: ?string, grup: bool, items: Collection<int, string>}>>
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
     * Satu baris teks tugas untuk halaman publik: "Induk — Tingkat", ditambah
     * nama grup bila juri hanya memegang sebagian grup di tingkat itu.
     *
     * Baris scope 'level' TIDAK menulis "Seluruh Tingkat" — nama tingkatnya
     * sudah menyatakan itu, dan menambahkannya membuat kalimatnya berbunyi
     * "PBB Putri — Regu Inti · Seluruh Tingkat" untuk satu fakta yang sama.
     *
     * @param  Collection<int, array{name: string, grup: bool, items: Collection<int, string>}>  $perTingkat
     */
    public static function ringkas(Collection $perTingkat): string
    {
        return $perTingkat
            ->map(function ($t) {
                if (!$t['grup']) {
                    return $t['name'];
                }

                $grup = $t['items']
                    ->reject(fn ($label) => in_array($label, CompetitionGroup::SCOPE_LABELS, true))
                    ->join(', ');

                return $grup === '' ? $t['name'] : $t['name'] . ' · ' . $grup;
            })
            ->join(' · ');
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
