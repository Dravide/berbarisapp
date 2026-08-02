<?php

namespace Database\Seeders;

use App\Models\AssessmentCategory;
use App\Models\AssessmentCriteria;
use App\Models\AssessmentSubCategory;
use App\Models\CompetitionCategory;
use App\Models\EventFaq;
use App\Models\EventGallery;
use App\Models\Eventner;
use App\Models\Judge;
use App\Models\Participant;
use App\Models\Registration;
use App\Models\Sponsor;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EventnerSeeder extends Seeder
{
    public function run(): void
    {
        // ── User Eventner ──
        $user = User::firstOrCreate(
            ['email' => 'eventner@baris.app'],
            [
                'name' => 'Panitia Demo',
                'username' => 'eventner',
                'password' => Hash::make('password'),
                'role' => 'Eventner',
                'is_active' => true,
            ]
        );

        // ── Event ──
        $eventner = Eventner::firstOrCreate(
            ['slug' => 'lomba-pbb-2026'],
            [
                'user_id' => $user->id,
                'status' => 'approved',
                'plan' => 'paid',
                'trial_ends_at' => null,
                'subdomain' => 'pbb2026',
                'nama_event' => 'Lomba PBB se-Jawa Barat 2026',
                'diselenggarakan_oleh' => 'Dinas Pendidikan Jawa Barat',
                'lokasi' => 'Bandung',
                'venue' => 'GOR Padjajaran',
                'tanggal' => now()->addWeeks(3)->toDateString(),
                'tanggal_akhir' => now()->addWeeks(3)->addDays(2)->toDateString(),
                'tanggal_pendaftaran' => now()->subWeek()->toDateString(),
                'technical_meeting' => now()->addWeeks(2)->toDateString(),
                'tingkat_perlombaan' => 'Provinsi',
                'deskripsi' => 'Lomba PBB antar sekolah se-Jawa Barat. Menampilkan kedisiplinan, kekompakan, dan ketangkasan baris-berbaris.',
                'scoring_code' => 'PBB2026',
                'drawing_code' => 'UNDIAN2026',
                'registration_status' => 'open',
                'vote_active' => true,
                'vote_price' => 1000,
                'vote_start' => now()->subDay(),
                'vote_end' => now()->addWeeks(3),
                'link_instagram' => 'https://instagram.com/lombapbb',
                'link_tiktok' => 'https://tiktok.com/@lombapbb',
            ]
        );

        // ── Kategori Lomba (Parent + Child) ──
        $categories = [
            'PBB Putra' => ['Regu Inti', 'Regu Cadangan'],
            'PBB Putri' => ['Regu Inti', 'Regu Cadangan'],
            'PBB Campuran' => ['Regu Utama'],
        ];

        $childIds = [];
        foreach ($categories as $parentName => $children) {
            $parent = CompetitionCategory::firstOrCreate(
                ['eventner_id' => $eventner->id, 'name' => $parentName, 'parent_id' => null],
                ['eventner_id' => $eventner->id, 'name' => $parentName, 'parent_id' => null, 'sort_order' => count($childIds)]
            );
            foreach ($children as $i => $childName) {
                $child = CompetitionCategory::firstOrCreate(
                    ['eventner_id' => $eventner->id, 'name' => $childName, 'parent_id' => $parent->id],
                    [
                        'eventner_id' => $eventner->id,
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'kuota' => 20,
                        'max_registrations_per_school' => 2,
                        'registration_fee' => 150000,
                        'sort_order' => $i,
                    ]
                );
                $childIds[] = $child->id;
            }
        }

        // ── Juri ──
        $judges = [];
        foreach (['Ahmad Sudrajat', 'Budi Santoso', 'Citra Dewi'] as $i => $jName) {
            $judges[] = Judge::firstOrCreate(
                ['eventner_id' => $eventner->id, 'name' => $jName],
                ['eventner_id' => $eventner->id, 'name' => $jName, 'phone_number' => '08' . rand(1000000000, 9999999999)]
            );
        }

        // ── Rubrik Penilaian (Kategori Penilaian + Sub + Kriteria) ──
        $rubrik = [
            'Kekompakan' => [
                'Gerakan Dasar' => [
                    ['Keseragaman langkah', [['label' => 'Sangat Baik', 'score' => 100], ['label' => 'Baik', 'score' => 80], ['label' => 'Cukup', 'score' => 60]], 2],
                    ['Ketepatan balik', [['label' => 'Sangat Baik', 'score' => 100], ['label' => 'Baik', 'score' => 80]], 1],
                ],
                'Kedisiplinan' => [
                    ['Kebersihan sikap', [['label' => 'Baik', 'score' => 100], ['label' => 'Kurang', 'score' => 50]], 1],
                ],
            ],
            'Ketangkasan' => [
                'Kemampuan Baris' => [
                    ['Kecepatan aba-aba', [['label' => 'Sangat Baik', 'score' => 100], ['label' => 'Baik', 'score' => 75]], 1],
                ],
            ],
        ];

        $assessmentCategories = [];
        foreach ($rubrik as $catName => $subs) {
            $ac = AssessmentCategory::firstOrCreate(
                ['eventner_id' => $eventner->id, 'name' => $catName, 'competition_category_id' => $childIds[0]],
                ['eventner_id' => $eventner->id, 'name' => $catName, 'competition_category_id' => $childIds[0], 'sort_order' => count($assessmentCategories)]
            );
            $assessmentCategories[] = $ac;

            foreach ($subs as $subName => $crits) {
                $sub = AssessmentSubCategory::firstOrCreate(
                    ['assessment_category_id' => $ac->id, 'name' => $subName],
                    ['assessment_category_id' => $ac->id, 'name' => $subName, 'sort_order' => 0]
                );
                foreach ($crits as $k => [$critName, $options, $weight]) {
                    AssessmentCriteria::firstOrCreate(
                        ['assessment_sub_category_id' => $sub->id, 'name' => $critName],
                        ['assessment_sub_category_id' => $sub->id, 'name' => $critName, 'score_options' => $options, 'weight' => $weight, 'sort_order' => $k]
                    );
                }
            }
        }

        // Hubungkan juri ke kategori penilaian
        if ($assessmentCategories && $judges) {
            foreach ($assessmentCategories as $ac) {
                $ac->judges()->syncWithoutDetaching([$judges[0]->id, $judges[1]->id]);
            }
        }

        // ── Pendaftaran (Registrations + Participants) ──
        $schools = [
            'SMA Negeri 1 Bandung' => ['npsn' => '20210001', 'regu' => 'Regu Inti'],
            'SMA Negeri 2 Bandung' => ['npsn' => '20210002', 'regu' => 'Regu Inti'],
            'SMA Negeri 5 Bandung' => ['npsn' => '20210005', 'regu' => 'Regu Inti'],
            'SMA Pasundan 1' => ['npsn' => '20220001', 'regu' => 'Regu Cadangan'],
            'SMA Alfa Centauri' => ['npsn' => '20220002', 'regu' => 'Regu Inti'],
            'SMA Plus Muthahhari' => ['npsn' => '20220003', 'regu' => 'Regu Utama'],
        ];

        $childByRegu = [];
        foreach ($childIds as $cid) {
            $cc = CompetitionCategory::find($cid);
            $childByRegu[$cc->name] = $cid;
        }

        foreach ($schools as $schoolName => $data) {
            $childId = $childByRegu[$data['regu']] ?? $childIds[0];

            $reg = Registration::firstOrCreate(
                ['eventner_id' => $eventner->id, 'nama_sekolah' => $schoolName, 'npsn' => $data['npsn']],
                [
                    'eventner_id' => $eventner->id,
                    'competition_category_id' => $childId,
                    'nama_sekolah' => $schoolName,
                    'npsn' => $data['npsn'],
                    'nama_pelatih' => 'Pelatih ' . $schoolName,
                    'no_hp' => '08' . rand(1000000000, 9999999999),
                    'school_email' => 'info@' . Str::slug($schoolName) . '.sch.id',
                    'status_berkas' => 'Terverifikasi',
                    'is_finalized' => true,
                    'payment_status' => 'paid',
                    'total_fee' => 150000,
                    'payment_verified_at' => now()->subDays(2),
                ]
            );

            // Peserta anggota regu
            if ($reg->participants()->count() === 0) {
                foreach (['Andi', 'Budi', 'Cici', 'Dedi', 'Eka', 'Fajar'] as $i => $nama) {
                    Participant::create([
                        'registration_id' => $reg->id,
                        'nama' => $nama . ' ' . $schoolName,
                        'nisn' => (string) (1000000000 + $reg->id * 10 + $i),
                    ]);
                }
            }
        }

        // ── Sponsor, Tenant, Galeri, FAQ ──
        Sponsor::firstOrCreate(
            ['eventner_id' => $eventner->id, 'name' => 'Bank Jabar'],
            ['eventner_id' => $eventner->id, 'name' => 'Bank Jabar', 'type' => 'sponsor', 'link' => 'https://bankjabar.co.id', 'is_active' => true, 'sort_order' => 1]
        );
        Sponsor::firstOrCreate(
            ['eventner_id' => $eventner->id, 'name' => 'Kopi Kenangan'],
            ['eventner_id' => $eventner->id, 'name' => 'Kopi Kenangan', 'type' => 'medpart', 'is_active' => true, 'sort_order' => 2]
        );

        Tenant::firstOrCreate(
            ['eventner_id' => $eventner->id, 'name' => 'Baso Aci Mantap'],
            ['eventner_id' => $eventner->id, 'name' => 'Baso Aci Mantap', 'type' => 'food', 'description' => 'Baso aci original Bandung.', 'is_active' => true, 'sort_order' => 1]
        );
        Tenant::firstOrCreate(
            ['eventner_id' => $eventner->id, 'name' => 'Kios Kaos PBB'],
            ['eventner_id' => $eventner->id, 'name' => 'Kios Kaos PBB', 'type' => 'souvenir', 'description' => 'Kaos & merchandise lomba.', 'is_active' => true, 'sort_order' => 2]
        );

        EventGallery::firstOrCreate(
            ['eventner_id' => $eventner->id, 'image' => 'events/gallery/demo-1.jpg', 'caption' => 'Pembukaan lomba'],
            ['eventner_id' => $eventner->id, 'image' => 'events/gallery/demo-1.jpg', 'caption' => 'Pembukaan lomba', 'sort_order' => 1]
        );
        EventGallery::firstOrCreate(
            ['eventner_id' => $eventner->id, 'image' => 'events/gallery/demo-2.jpg', 'caption' => 'Sesi baris-berbaris'],
            ['eventner_id' => $eventner->id, 'image' => 'events/gallery/demo-2.jpg', 'caption' => 'Sesi baris-berbaris', 'sort_order' => 2]
        );

        EventFaq::firstOrCreate(
            ['eventner_id' => $eventner->id, 'question' => 'Kapan technical meeting?'],
            ['eventner_id' => $eventner->id, 'question' => 'Kapan technical meeting?', 'answer' => 'H-1 sebelum lomba pukul 09.00 WIB di GOR Padjajaran.', 'sort_order' => 1]
        );
        EventFaq::firstOrCreate(
            ['eventner_id' => $eventner->id, 'question' => 'Berapa biaya pendaftaran?'],
            ['eventner_id' => $eventner->id, 'question' => 'Berapa biaya pendaftaran?', 'answer' => 'Rp 150.000 per regu.', 'sort_order' => 2]
        );

        $this->command->info("Eventner demo '{$eventner->nama_event}' siap.");
        $this->command->info("Login: eventner@baris.app / password");
    }
}
