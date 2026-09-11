# Audit Bug Fitur Eventner

- **Tanggal:** 2026-09-11
- **Cabang:** `main` (commit `2834937`)
- **Sifat:** read-only — tidak ada file aplikasi yang diubah
- **Cakupan:** seluruh fitur role Eventner + halaman publik terkait (pendaftaran, vote, scoreboard, sertifikat, undian, penilaian, format nilai)
- **Status:** 48 temuan. Semua sudah diverifikasi ulang langsung ke source (bukan hanya laporan reviewer).

## Ringkasan

Dua akar masalah dominan:

**Akar 1 — Properti Livewire publik dipakai langsung sebagai scope tenant.**
Properti publik komponen Livewire bisa ditulis klien. Snapshot di-HMAC, tapi payload `$updates` tidak (`vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:466-489`). `#[Locked]` satu-satunya pengaman. Model-typed property relatif aman (`Features/SupportModels/ModelSynth.php` — `set()` melempar, `hydrate()` butuh meta `mdl`), jadi yang bocor adalah properti **scalar**: id, `eventnerId`, `templateId`, `activeTab`, `selectedRegistrationId`.

Pola perbaikan sudah ada di repo sendiri:
- `app/Livewire/Eventner/Drawing/Spin.php:19-20` — `#[Locked]` + komentar "Dikunci server-side — client tidak boleh mutasi ke tenant lain."
- `app/Http/Controllers/Api/V1/PortalController.php:22` — guard `tokenable_type`.
- `app/Livewire/Eventner/Notification/Index.php:45-46` — re-query ter-scope.

**Akar 2 — Implementasi peringkat/total paralel yang saling berbeda.**
Enam implementasi peringkat: `ChampionCalculator`, `ChampionCategory\Index`, `ChampionCategoryController`, `Public\Champions\Index`, `Public\Scoreboard\Index`, `ScoreRecap\Index`, plus `Scoring\Index` dan `ScoringController` (CSV/PDF). Mereka berbeda pada: filter nilai nol, tanda potongan, bobot kriteria, dan aturan peringkat seri.

---

## KRITIS — Akses lintas tenant / tanpa autentikasi

### 1. Hasil undian bisa dihapus tanpa login
- `app/Livewire/Eventner/Drawing/Spin.php:46-48` → `:125-135`
- Route publik: `routes/web.php:27` (`/event/{slug}/drawing`)

```php
if (!$eventner->drawing_code) {
    $this->isAuthenticated = true;
}
```

`drawing_code` default kolom kosong. Tenant yang belum pernah mengatur kode undian → `isAuthenticated` true untuk anonim → anonim bisa memanggil `resetDrawing()`:

```php
public function resetDrawing()
{
    if (!$this->isAuthenticated) return;
    Registration::where('eventner_id', $this->eventnerId)
        ->where('competition_category_id', $this->activeTab)
        ->update(['urutan_tampil' => null]);
```

**Dampak:** `urutan_tampil` di-null-kan. Kolom itu tiebreaker terakhir di semua implementasi peringkat → urutan juara berubah diam-diam.
**Catatan:** `wire:confirm` di `spin.blade.php:134` hanya client-side, bukan guard server.

### 2. `eventnerId` tanpa `#[Locked]` → baca + hapus lintas tenant
- `app/Livewire/Eventner/FormatNilai/Builder.php:29`
- `app/Livewire/Eventner/FormatNilai/Download.php:18`

Satu-satunya scope tenant untuk seluruh query. Dampak: `categories()` `Builder.php:133-144` (baca lintas tenant), `deleteCategory()` `Builder.php:174-193` (hapus lintas tenant), `Download.php:70` (dump PDF lintas tenant).

### 3. `templateId` tanpa `#[Locked]` → hapus/ubah field teks sertifikat tenant lain
- `app/Livewire/Eventner/Certificate/Editor.php:29`
- Dipakai unscoped: `:115`, `:155-157`, `:184-186`, `:201-203`

`loadTemplate()` memang men-scope (`:73-75`), tapi dipanggil **setelah** mutasi:

```php
public function deleteField($id)
{
    CertificateTextField::where('certificate_template_id', $this->templateId)
        ->where('id', $id)
        ->delete();          // :203 — DELETE sudah commit
    ...
    $this->loadTemplate();   // :210 — baru di sini ModelNotFoundException naik
```

Tidak ada transaction. **Dampak:** satu eventner bisa menghapus/mengubah field teks sertifikat eventner lain. ID template bocor berurutan lewat `route('eventner.certificate.editor', $tpl['id'])` di `Templates`.

### 4. Vote lintas event → suara dan uang salah dibukukan
- `app/Livewire/Public/EventVote.php:42-47` (rule), `:154-165` (penyimpanan)

```php
'selectedRegistrationId' => 'required|exists:registrations,id',
...
VoteTransaction::create([
    'eventner_id' => $this->eventner->id,
    'registration_id' => $this->selectedRegistrationId,   // tanpa scope event
```

ID registrasi tampil di DOM: `event-vote.blade.php:461,484,510,558`. Semua rekap agregasi `by registration_id` (`VoteResults/Index.php:59-65`, `EventVote.php:283-293,304-308`) → suara + uang masuk ke peserta event lain. Kembaran API-nya sudah men-scope dengan benar.

### 5. Pendaftaran lintas event
- `app/Livewire/Public/Registration/Create.php:27` (`public $selectedCategories = []` — array publik, client-settable)
- `:144` `CompetitionCategory::whereIn('id', $this->selectedCategories)->get()`
- `:78` `CompetitionCategory::find($catId)`

Tidak pernah di-scope `$this->eventner->id`.

### 6. Hierarki kategori dibajak lintas tenant
- `app/Livewire/Eventner/CompetitionCategory/Index.php:117` `'parentId' => 'nullable|exists:competition_categories,id'` (unscoped)
- Ditulis ke `$data['parent_id']` di `:133` tanpa cek ulang

`parent_id` FK cascade (`2026_07_17_000001:12`) → parent korban yang dihapus ikut menghapus kategori penyerang.
Terkait: `:119` `'selectedJudges.*' => 'exists:judges,id'` juga unscoped, dipakai `sync` `:152` dan `attach` `:169`.

### 7. Peserta lintas tenant
- `app/Livewire/Eventner/Participant/Index.php:91` `'competition_category_id' => 'required|exists:competition_categories,id'` (unscoped)
- Jalur edit `:104-111`, jalur create `:117-126`

---

## KRITIS — Kehilangan data tanpa pemulihan

### 8. Hapus tingkat lomba cascade ke seluruh nilai
- `app/Livewire/Eventner/CompetitionCategory/Index.php:193-204`

```php
if ($cat->isParent() && $cat->children()->exists()) { ...blokir...; return; }
$cat->delete();
```

Guard hanya untuk **parent** yang punya anak. **Child** (tingkat lomba) dihapus tanpa syarat apa pun.
**Rantai cascade:** `registrations.competition_category_id` (`2026_04_18_135425:17`) → `participants.registration_id` (`2026_04_18_135426:16`) → `assessment_scores.*` (`2026_04_30_200001:13-15`) + `score_deductions.*` (`2026_05_13_045929:16-18`).
`Registration` **tidak** pakai SoftDeletes. Dialog konfirmasi hanya berbunyi "Hapus tingkat lomba ini?".

### 9. Hapus pendaftaran menghapus transaksi vote PAID
- `app/Livewire/Eventner/Participant/Index.php:153` `Registration::where('eventner_id', $eventner->id)->findOrFail($id)->delete()`
- `database/migrations/2026_04_19_015242_create_vote_transactions_table.php:17` `onDelete('cascade')`

Uang yang sudah dibayar hilang permanen dari rekap. `Registration` tanpa SoftDeletes.

---

## TINGGI — Peringkat & total tidak konsisten (Akar 2)

### 10. Filter nilai nol tidak konsisten
- `app/Livewire/Eventner/ChampionCategory/Index.php:443-499` — `array_slice($participantScores, 0, $champion->quantity)` **tanpa** filter `total > 0`
- `app/Services/ChampionCalculator.php:121-124` — memfilter `fn ($ps) => $ps['total'] > 0`, dengan komentar eksplisit "Peserta tanpa nilai (skor 0) bukan juara"
- `app/Livewire/Public/Champions/Index.php:159-165` — memfilter juga

**Dampak:** peserta tanpa nilai tampil sebagai "Juara N" di halaman Kategori Juara.

### 11. Tanda potongan berbeda antar implementasi
- `ChampionCategory/Index.php:469` — `$totalDeduction = $deductions->sum('amount')` (tanda mengikuti apa pun yang diketik operator)
- `ChampionCategoryController.php:220-222` — `$deductions->sum(fn($d) => abs((float) $d->amount))`
- `ScoreRecap/Index.php:111-120` — normalisasi (`if ($amt > 0) $amt = -$amt;`)
- `Scoring/Index.php:539-541` — `abs()`

`ChampionCategory\Index` satu-satunya yang tidak menormalkan. Tanda yang tersimpan adalah apa pun yang diketik di FormatNilai Builder (`deduction_options` JSON, dikonsumsi verbatim di `scoring/index.blade.php:421`).

### 12. Bobot kriteria hilang di CSV/PDF dan panel operator
- `app/Http/Controllers/Eventner/ScoringController.php:82` (CSV) — `sum((int) $score->score)`
- `app/Http/Controllers/Eventner/ScoringController.php:246` (PDF) — sama
- `app/Livewire/Eventner/Scoring/Index.php:525` — `$judgeScores->sum(fn($s) => (int) $s->score)`
- `resources/views/livewire/eventner/scoring/index.blade.php:218-231` (`$grandTotal += (int) $val`), `:487` (`$judgeTotals->sum('total') - $totalDeductions`)
- Cabang simulasi `Scoring/Index.php:512` juga tanpa bobot

Bobot nyata dan bukan default: `assessment_criterias` id 1 dan 8 punya `weight = 2.00`. Bandingkan yang **sudah** mengalikan: `ScoreRecap/Index.php:107`, `Public/Scoreboard/Index.php:149`. Kolom "Rank" di CSV (`:200`) hanya `$index + 1`.

### 13. ScoreDeduction diabaikan di scoreboard publik
- `app/Livewire/Public/Scoreboard/Index.php:136-151` — hanya `sum(score × weight)`, tidak ada query `ScoreDeduction`
- `app/Livewire/Eventner/ScoreRecap/Index.php:111-140` — mengurangi potongan, urut `finalScore` (`:157`)

Papan skor publik dan rekap panitia memberi peringkat berbeda.

### 14. PDF sertifikat memberi juara ke peserta bernilai nol
- `app/Http/Controllers/Eventner/CertificateController.php:144-172` — slice pool tanpa filter `total > 0`

Mencetak "Juara N" sementara `ChampionCalculator::winners()` dan route token bilang "PESERTA". Preview di `Editor.php:367` memakai calculator (benar).

### 15. Kolom pendapatan vote salah hitung
- `resources/views/livewire/eventner/vote-results/index.blade.php:132` — hardcode `total_votes * 1000`
- `resources/views/eventner/vote-results/pdf_recap.blade.php:150` — `total_votes * pricePerVote`
- Kartu ringkasan memakai `SUM(amount)`: `pdf_recap.blade.php:110-113`, `VoteResultsController.php:21,83` (`$pricePerVote = $eventner->vote_price ?: 1000`)

`votes_earned` **sudah** termasuk multiplier booster (`EventVote.php:138,160` — `$totalVotes = $this->voteCount * $multiplier`). Jadi dua kolom di halaman yang sama tidak cocok. Badge "1 Vote = Rp 1.000" di `index.blade.php:62`.

### 16. Aturan peringkat seri berbeda
- `resources/views/livewire/eventner/score-recap/index.blade.php:77-85` — ordinal per indeks array
- `app/Livewire/Public/Scoreboard/Index.php:166-171` — competition ranking (seri = peringkat sama)

### 17. Kolom rekap tidak saling menjumlah
- `app/Livewire/Eventner/ScoreRecap/Index.php:140` — `$catTotal + $catDeduction` hanya untuk kategori terfilter level
- `:143` — `$totalDeduction = array_sum($deductionByCat)` menjumlah semua (map di `:90-96` **tidak** terfilter level)
- CSV `ScoringController.php:199-200` mewarisi ketidakkonsistenan sama

### 18. Uang macet setelah QR kedaluwarsa
- `app/Http/Controllers/Webhook/AutoGoPayWebhookController.php:72-80` — mismatch amount early-`return`, status tidak diubah
- Semua klaim: `where('status','PENDING')` (`:82-84`, `:179-181`)
- Reconciler berhenti setelah 6 jam: `app/Console/Commands/SyncPendingTransactions.php:24`, `app/Jobs/SyncPendingPayments.php:48`
- **Tidak ada jalur `EXPIRED → PAID`.**

Pembayaran yang masuk setelah kedaluwarsa tidak pernah dikreditkan.

---

## SEDANG

### 19. Gate fitur berbayar bocor
`format_nilai` (`config/eventner_features.php:45-48`) hanya dijaga `Builder` (`Builder.php:25-27,58` via `FeatureGatedComponent::bootFeatureGate`). `Download` dan seluruh aksi `FormatNilaiController` tanpa cek `canAccessFeature`. Bandingkan `CertificateController.php:355` yang sudah benar.

### 20. 500 di halaman penilaian
`app/Livewire/Eventner/Scoring/Index.php:54-56` set `view = 'participants'` untuk `selectedCategoryId` apa pun yang truthy. Lookup di `:463-464` ter-scope eventner sehingga id asing → null, lalu `scoring/index.blade.php:87` render `{{ $selectedCategory->full_name }}` tanpa guard null. Terjangkau lewat `/eventner/scoring?selectedCategoryId=999`. `selectCategory()` `:59-63` juga tidak validasi kepemilikan.

### 21. Nilai terkunci bisa ditimpa diam-diam
`Scoring/Index.php:177` dan `:209` mempercayai `isFinalized` in-memory yang dimuat sekali di `:167`. `resetScores()` `:362-378` **tidak** cek finalized sama sekali. `updateOrCreate` di `:191` tidak pernah menulis `is_finalized`. Tab lama bisa menimpa atau menghapus nilai yang sudah dikunci tanpa jejak.

### 22. Potongan hilang saat finalisasi
`Scoring/Index.php:257` — `finalizeScores()` hanya memanggil `saveScores()`, tidak `saveDeductions()` (tombol terpisah di `:419`, blade `:460`). Tapi blade `:487` menghitung "NILAI AKHIR" dari `$totalDeductions` live (`:437-442`) → operator melihat angka yang lalu hilang.

### 23. Rekap kosong saat pertama dibuka
`ScoreRecap/Index.php:33-38` auto-pilih `$this->eventner->competitionCategories->first()` — kategori **parent** — padahal registrasi hanya menempel ke child. Select di `score-recap/index.blade.php:40-44` hanya menampilkan child → nilai model dan yang tampil tidak sinkron.

### 24. Kategori juara lintas lomba
`CertificateController.php:53` `CompetitionCategory::findOrFail($competitionCategoryId)` tanpa cek `eventner_id` dan tanpa guard `isVisibleFor`. URL rakitan bisa memasangkan rubrik juara lomba A dengan peserta lomba B (skor tak cocok jatuh ke `other_total` di `:112-113`). Inkonsisten dengan guard route token di `:329`.

### 25. Sertifikat bisa salah pasukan
`CertificateController.php:333-345` (dalam `downloadCertificateByToken` `:318`) mencari registrasi target lewat `npsn`/`nama_sekolah`, bukan lewat token. Sekolah dengan dua pendaftaran di kategori sama dapat pasukan salah; dua sekolah dengan NPSN salah ketik sama bisa saling render. NPSN `required|string|max:20` tanpa uniqueness (`Create.php:111,137`).

### 26. Undian mentok di kuota
`Drawing/Spin.php:99-105` — `$totalInCategory = $category->kuota ?? count`, lalu `array_diff(range(1, max(1, $totalInCategory)), $usedNumbers)`. `remainingSlots()` (`CompetitionCategory.php:51-56`) hanya dipanggil `Public/Registration/Create.php:171`, **tidak pernah** di jalur admin. Kuota 5 dengan 8 pendaftar → setelah 5 undian `spin()` selalu early-return, 3 peserta sisa tidak akan pernah dapat nomor.

### 27. Reset undian tanpa guard nilai
`Drawing/Index.php:108-115` — reset tanpa syarat sama seperti `Spin.php`. Padahal `Participant/Index.php:226-233` (`swapPasukan`) justru memblokir perubahan begitu nilai sudah ada.

### 28. Ubah kategori tidak membersihkan `urutan_tampil`
`Participant/Index.php:102-111` mengubah `competition_category_id` tanpa mengosongkan `urutan_tampil` dan tanpa cek `assessment_scores` yang sudah ada. Select tetap aktif saat edit (`participant/index.blade.php:282`). Tidak ada unique constraint di `urutan_tampil` (keunikan hanya dijaga saat penugasan di `Drawing/Index.php:77-86`).

### 29. `groupByLevel` membuang juara multi-level
`ChampionCategoryController.php:142-168` memakai `->first()` pada level subkategori tiap juara → juara yang rubriknya mencakup dua level hanya masuk satu seksi di "Unduh Semua PDF".

### 30. Judul label terpecah
`Builder.php:589` memisah pada `[' – ', ' - ', '–', ';']`, sementara `builder.blade.php:607` memisah pada literal entitas HTML `'&ndash;'`. Preset (`fillLabelPreset`, `Builder.php:567-575`, `'0 – 25'`) tampil sebagai satu badge di preview tapi tersimpan sebagai dua nilai terpisah.

### 31. Rubrik global hilang di lembar cetak
`FormatNilaiController.php:193` dan `:243` memfilter `where('competition_category_id', $levelId)` sehingga NULL (rubrik global) dikecualikan. Bandingkan `Scoring/Index.php:216-221` yang sengaja menyertakan (`orWhereNull('competition_category_id')`).

### 32. Hapus kategori menyisakan orphan
`Builder.php:193` dan `:390` — `deleteCategory()` hanya menjaga `AssessmentScore`/`ScoreDeduction`, tidak `champion_assessment` yang FK-nya cascade (`2026_06_19_000000_repivot_champion_assessment_to_subcategory.php`). `deduction_categories` jadi orphan (`nullOnDelete`, `2026_06_18_110000`) lalu hilang dari `Scoring/Index.php:390-398` (`whereHas('assessmentCategory')`).

### 33. `activeTab` tanpa cek kepemilikan
`Builder.php:164` dan `Import.php:186,198` menulis `activeTab` langsung sebagai `competition_category_id` tanpa verifikasi kepemilikan.

---

## RENDAH

| # | Lokasi | Masalah |
|---|---|---|
| 34 | `app/Traits/HasFeatureGates.php:50-51` | `$this->saasPlan->features` — `saasPlan` bisa null karena `eventners.saas_plan_id` pakai `nullOnDelete` (`2026_09_09_000001:33`) |
| 35 | `app/Http/Controllers/Api/V1/QrController.php:42` | `createToken('mobile-app')` tiap scan tanpa revoke — token menumpuk; hanya limiter 10/menit per IP (`:16-19`) |
| 36 | `app/Http/Controllers/Api/V1/UploadController.php:12-17` | `getRegistration()` tidak punya guard `tokenable_type === Registration::class` (bandingkan `PortalController.php:22`) |
| 37 | `app/Livewire/Eventner/Settings/Billing/Upgrade.php:56-92` | `generatePayment()` menimpa `autogopay_transaction_id` tanpa void QR lama; `handleEventnerSettlement` (`AutoGoPayWebhookController.php:127-144`) cocok hanya lewat transaction id |
| 38 | `routes/console.php:8-9` | `payment:sync-pending` (everyMinute) dan `SyncPendingPayments` (everyFiveMinutes) tumpang tindih — klaim atomik jadi tidak dobel-hitung, hanya mubazir |
| 39 | `app/Livewire/Eventner/VoteComment/Index.php:131-134` | filter `votes_earned >= TIERS[$tier]` sementara `tierOf()` `:85-92` memakai band teratas; CSV `VoteCommentController.php:52-54` mewarisi predikat sama |
| 40 | `app/Livewire/Eventner/Certificate/Templates.php:147-153` | hapus `file_path` lama di `:148` sebelum `store()`/`update()`; route token selalu ambil template aktif terlama (`CertificateController.php:359-363`) |
| 41 | `app/Support/FormatNilaiImport.php:171` | `$rowNo = $rowIndex + 1` meleset satu — `uploadExcel()` sudah `array_shift()` header (`Import.php:125-129`) |
| 42 | `app/Livewire/Eventner/FormatNilai/Builder.php:856-878` | `reorderCategories()` pluck **semua** kategori eventner padahal view hanya render `activeTab` (`:139-141`) → urutan tingkat saling menyilang |
| 43 | `resources/views/livewire/eventner/format-nilai/builder.blade.php:73` | `:key="'import-'.$activeTab"` menghancurkan child Import (preview hilang) saat tab diganti (`wire:model.live` di `:28`) |
| 44 | `resources/views/livewire/eventner/certificate/editor.blade.php:187` | font-size `pt` absolut di dalam canvas yang di-scale CSS (`max-width:100%; max-height:85vh` di `:103-105`) |
| 44b | `app/Livewire/Eventner/Certificate/Editor.php:118` | `$qrPct = font_size / template.height * 100` + `width:{qrPct}%` (`editor.blade.php:147`) vs PDF `width:{font_size}mm` (`pdf.blade.php:52-53`) → 1,41× lebih besar di A4 landscape |
| 45 | `app/Livewire/Eventner/Certificate/Editor.php:360` | `CompetitionCategory::with('parent')->find(...)` unscoped (`:357` sudah scope) — bocor info saja |
| 46 | `app/Livewire/Public/EventVote.php:109-121` | `submitVote()` hanya cek `vote_active`; cek `vote_start`/`vote_end` ada di `mount()` `:68-73` yang menulis `$view` publik (client-settable) |
| 47 | `resources/views/livewire/public/event-vote.blade.php:150` | cetak `{{ $voteCount }}` sementara `EventVote.php:160` menyimpan jumlah yang sudah dikali booster; `checkPaymentStatus()` `:219-225` lapor sukses walau update atomiknya kena 0 baris |
| 48 | `app/Livewire/Public/EventScoring.php` | dead code (tidak ada route). Kalau dihidupkan: `updateOrCreate` `:136-145` tidak menyertakan `judge_id` padahal unique index `(registration_id, assessment_criteria_id, judge_id)` (`2026_05_01_134206_…`) → juri saling menimpa |

Dead code lain: `Builder::previewCopy`, `executeCopy`, `openCopyModal`, `closeCopyModal`, `$showCopyModal`, `$copySourceId`, `$copyPreviewData`.

---

## Sudah dicek — bersih

- **API v1:** `VoteController`, `TicketController`, `EventController`
- **Pembayaran:** verifikasi HMAC-SHA256 (`X-Signature`) + verifikasi amount + klaim atomik `where('status','PENDING')->update(...)`; `Ticket::claimPaid()`
- **Publik:** `Public/TicketPdfController`, `Public/Checkin/Scan`, `Public/MagicLink/Registration` (`switchRegistration` `:73` resolve lewat `$this->siblingRegistrations->firstWhere('id', $regId)` — sibling-scoped)
- **Eventner:** `Sponsor\Index`, `Tenant\Index`, `Settings/BankAccount`, `Settings/Signature` (pakai `protected $eventnerId` di `boot()` — bukan client-writable), `Gallery\Index`, `Faq\Index`, `VoteBooster\Index`, `Ticket\Index`, `Certificate\Templates`, `Livestream\Manage`, `ActivityLog\Index`, `EventQr`
- **Controller:** `DrawingController`, `RundownController`, `VoteCommentController`, `FinanceDashboardController`, `ParticipantController::downloadPdf/downloadInvoice`, `ScoringController::downloadParticipantPdf`, `FormatNilaiController::downloadPdfByChild/downloadPdfByJudge/copyExecute`, `ChampionCategoryController` (kecuali temuan peringkat/grouping)
- **Vote:** semua counter filter `status = 'PAID'`; `markAsPaid` (`VoteTransaction/Index.php:186-205`) menolak non-PENDING; semua route vote di bawah `role:Eventner`; `VoteResultsController` PDF ter-scope
- **Sertifikat:** output PDF ter-escape `{{ }}` di seluruh view, tidak ada `{!!`; lookup token `firstOrFail()` eksak; `ModelSynth::hydrate` memblokir penukaran property ke baris sembarang; `magic_token` bukan properti publik yang writable (`MagicLink/Registration.php:16`); `Templates` delete ter-scope `eventner_id`
- **Penilaian:** `Scoring/Index.php:93` dan `:463` ter-scope eventner (`selectParticipant` `:89-95` berkomentar eksplisit "cegah IDOR ke registrasi tenant lain"); `finalizeScores`/`loadDeductions` fail-closed pada kategori null; `ScoringController.php:45` `CompetitionCategory::find()` hanya untuk teks judul CSV
- **Rule `exists:` unscoped yang sudah termitigasi:** `Drawing/Index.php:68` (re-query scoped `:72-74`), `Notification/Index.php:27` (`:45-46`), `Rundown/Index.php:140` (validasi `$this->categories` `:151-155`), `FormatNilaiController.php:335` (`:343`), `UploadController.php:28` (`:35`), `Judge/Index.php:122` (pivot saja)

---

## Catatan metodenya

- Laporan disusun dari 5 reviewer paralel + audit manual lapisan di luar jangkauan mereka (API v1, webhook, AutoGoPay, sync job/command, settings/billing, sponsor/tenant, view PDF, migrasi, middleware, routes, jadwal console).
- Reviewer ke-6 (dashboard/keuangan/settings) **gagal** dengan error kuota API dan tidak menghasilkan laporan. Lapisan itu hanya tercakup audit manual: temuan #34, #37, dan daftar "bersih" di atas.
- Semua temuan di dokumen ini sudah diverifikasi ulang langsung ke source, termasuk membaca `vendor/livewire/livewire/src/Features/SupportModels/ModelSynth.php` dan `HandleComponents.php` untuk memastikan batas kepercayaan properti Livewire.
- FK cascade diverifikasi di file migrasi masing-masing, bukan dari asumsi.

## Saran prioritas

1. **#1–#9** — akses lintas tenant dan kehilangan data. Pola perbaikan seragam: tambah `#[Locked]` dan/atau re-query ter-scope.
2. **#10–#18** — menyangkut uang dan penentuan juara. Perlu satu sumber kebenaran (satukan ke `ChampionCalculator`).
3. **#19–#33** — bug fungsional dan UX.
4. **#34–#48** — perbaikan bertahap.
