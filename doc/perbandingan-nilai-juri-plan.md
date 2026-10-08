# Plan — Perbandingan Nilai Juri

**Titik masuk:** `/eventner/scoring?selectedCategoryId=27`
**Halaman baru:** `/eventner/scoring/perbandingan?selectedCategoryId=27`
**Wireframe:** `doc/perbandingan-nilai-juri-wireframe.html`
**Tanggal:** 2026-10-08

---

## 1. Keputusan bentuk: laman khusus, bukan modal

User membuka dua pilihan ("laman khusus atau modal"). Yang dipilih **laman khusus**, alasannya tiga dan semuanya berasal dari kode yang sudah ada:

1. **Lebarnya.** Permukaannya adalah matriks peserta × juri × kriteria. Modal Bootstrap di layar 1280 px menyisakan ~1140 px setelah padding, cukup untuk 3–4 kolom juri — di tablet panitia meja itu pecah. Halaman penuh memakai lebar yang sama dengan `score-recap`.
2. **Tautan yang bisa dibagikan.** Rekap nilai sudah punya halaman sendiri dengan `queryString` (`selectedCategoryId`, `selectedGroupId`), jadi satu panitia bisa mengirim "lihat perbandingan juri Grup A babak final" ke panitia lain. Modal tidak punya alamat.
3. **Modal di Livewire punya jebakan yang sudah tercatat.** Modal wajib berada di dalam satu root komponen (`livewire-modal-di-luar-root`), dan `wire:confirm` di dalam modal Bootstrap selalu batal (`livewire-confirm-override-pitfall`). Halaman biasa tidak menyentuh dua jebakan itu sama sekali.

Modal kecil tetap dipakai, tapi hanya untuk **satu sel** — klik satu sel di matriks membuka rincian nilai per juri untuk peserta + kriteria itu. Bukan untuk seluruh analisis.

---

## 2. Masalah yang harus dijawab halaman ini

Panitia ingin tahu: **nilai juri-juri ini seimbang tidak? Ada yang terlalu tinggi atau terlalu kecil?** Tiga pertanyaan nyata di baliknya:

| Pertanyaan panitia | Yang harus dihitung |
|---|---|
| "Juri B kok nilainya selalu lebih tinggi?" | Bias tiap juri terhadap rata-rata sel yang **ia isi bersama juri lain** |
| "Ada sel yang jurinya jauh berbeda?" | Rentang nilai per sel (max − min), ditandai kalau lewat ambang |
| "Nilainya sudah lengkap belum?" | Kelengkapan per juri: sel yang jadi tugasnya vs yang terisi |

---

## 3. Jebakan utama — dan kenapa halaman ini tidak boleh menjumlahkan nilai juri

Sudah tertulis di kode, di `Scoring\Index::render()`:

> Total per juri … angka ini **TIDAK lagi setara antar juri** … Yang tetap setara dengan `ChampionCalculator` adalah "Jumlah Semua Juri".

Sebabnya: rubrik dibagi antar juri (`assessment_category_judge`, lihat `AssessmentCategory::bolehDinilaiOleh()`). Juri A memegang kriteria 1–3, juri B memegang kriteria 4–6. **Menjumlahkan total A dan membandingkannya dengan total B membandingkan dua pekerjaan yang berbeda** — dan halaman yang melakukannya akan melaporkan "juri A terlalu rendah" hanya karena juri A kebetulan memegang kriteria berskala kecil.

Konsekuensi untuk rancangan:

- **Satuan analisis = sel**, bukan juri. Sel = (peserta × kriteria). Karena unique index `unique_score_participant_criteria_judge (registration_id, assessment_criteria_id, judge_id)` membuat satu sel berisi **paling banyak satu nilai per juri**, model selnya eksak — tidak ada agregasi yang bisa salah.
- **Pembanding hanya dihitung antar juri yang beririsan.** Dua juri yang tidak memegang satu kriteria bersama **tidak bisa dibandingkan**, dan itu harus dikatakan apa adanya ("Tidak dapat dibandingkan"), bukan ditampilkan sebagai 0.
- **Total mentah tetap ditampilkan**, tapi diberi catatan bahwa angkanya berbeda karena rubriknya terbagi. Menyembunyikannya justru membuat panitia menghitung sendiri lalu salah.
- **Bobot kriteria (`$crit->weight ?? 1`) sengaja TIDAK dipakai** di analisis keseimbangan. Bobot adalah sifat kriteria, bukan sifat juri; memasukkannya membuat juri yang kebetulan memegang kriteria berbobot besar terlihat "lebih tinggi". Bobot tetap dipakai di rekap/peringkat — tempat ia memang berhak.

---

## 4. Normalisasi skala

Nilai satu kriteria bisa beropsi 0–25, kriteria lain 0–100. Tanpa normalisasi, kriteria berskala besar mendominasi seluruh statistik.

```
x = ScoreOptions::value($score->score)          // pintu baca resmi — kolomnya varchar
m = ScoreOptions::maxValue($crit->score_options) // batas atas skala; 0 → cadangan 100
p = x / m                                        // porsi skala (boleh > 1 kalau di luar opsi)
```

Semua statistik di bawah bekerja pada `p`, lalu ditampilkan kembali dalam poin kriteria lewat `ScoreOptions::format()` supaya tetap terbaca panitia.

---

## 5. Rumus & ambang

Ditetapkan sebagai konstanta di service, bukan angka berserak di Blade.

| Konstanta | Nilai | Arti |
|---|---|---|
| `AMBANG_BIAS` | `0.05` | 5% skala — batas "seimbang" |
| `AMBANG_SIGMA` | `0.10` | 10% skala — batas konsistensi |
| `AMBANG_RENTANG_SEL` | `0.20` | 20% skala — sel ditandai kalau rentangnya lewat ini |
| `MIN_SEL` | `5` | Juri dengan < 5 sel pembanding tidak diberi putusan |

**Per juri** (atas sel yang juga diisi ≥1 juri lain):

```
d       = p_juri − rata-rata(p) pada sel itu
bias    = mean(d)        → arah: + berarti lebih tinggi dari rata-rata sel
sigma   = stdev(d)       → seberapa liar, lepas dari arahnya
```

**Kelas juri**, dievaluasi berurutan:

1. jumlah sel pembanding < `MIN_SEL` → **Data belum cukup** (tidak ada angka bias yang ditampilkan)
2. `|bias| ≤ AMBANG_BIAS` dan `sigma ≤ AMBANG_SIGMA` → **Seimbang**
3. `|bias| ≤ AMBANG_BIAS` dan `sigma > AMBANG_SIGMA` → **Tak konsisten** (rata-rata seimbang, tapi sebarannya liar)
4. `bias > AMBANG_BIAS` → **Cenderung tinggi**
5. sisanya → **Cenderung rendah**

`konsisten` juga disimpan sebagai chip terpisah, karena juri bisa "cenderung tinggi" **dan** konsisten (selalu lebih tinggi 10% — itu bias yang bisa dikoreksi dengan sadar), atau "seimbang" tapi liar.

**Per sel:** ditandai kalau `rentang = max(p) − min(p) > AMBANG_RENTANG_SEL` **dan** jumlah juri yang mengisi ≥ 2. Juri yang paling jauh dari rata-rata sel dicatat sebagai `menyimpang`.

**Per kriteria:** `n`, `mean`, `median`, `min`, `max`, `rentang`, `sigma` — dihitung pada `p`, ditampilkan dalam poin.

---

## 6. Pemisahan yang wajib dihormati

Menyontek alasan yang sama dengan `ScoreRecapBuilder`:

- **Per babak.** Nilai penyisihan dan final tinggal di baris kriteria berbeda. Menggabungkan keduanya menghasilkan selisih yang tidak pernah terjadi di lapangan. Filter babak meneruskan `selectedRoundId` ke `roundCriteriaIds()`.
- **Per grup.** Peserta Grup A bukan pembanding Grup B. Filter grup memakai `CompetitionGroup` milik tingkat itu — sama seperti `score-recap`.
- **Per seri.** Rubrik berseri hanya berlaku untuk peserta berseri itu; sel diambil lewat `AssessmentCategory::rubrikUntukPeserta()` **per juri dan per peserta**, pintu yang sama dengan tablet juri dan `ScoreFinalizationService`. Tanpa itu halaman ini akan memasangkan nilai peserta Seri A dengan rubrik Seri B.
- **Babak final** mengabaikan seri sepenuhnya — sudah jadi aturan di `scopeForEntry()`, jadi tidak perlu cabang baru.

---

## 7. Mode simulasi (Sandbox)

`simulateMode` tidak menulis apa pun ke DB. Halaman perbandingan **selalu membaca data tersimpan**, jadi saat sandbox menyala ia menampilkan spanduk: *"Simulasi aktif — angka di halaman ini berasal dari nilai tersimpan, bukan dari input latihan Anda."* Tanpa itu panitia menyangka latihannya sudah masuk.

Halaman ini juga **read-only**: tidak ada tombol simpan, tidak ada finalisasi. Aman dibuka kapan saja, dan tidak terpengaruh kunci `finalize()` — nilai terkunci tetap terbaca.

---

## 8. Berkas yang disentuh

| Berkas | Aksi | Isi |
|---|---|---|
| `app/Services/JudgeScoreComparison.php` | **baru** | Seluruh matematika. Satu sumber, tanpa Livewire — bisa diuji langsung dan nanti dipakai PDF/CSV tanpa menyalin rumus. Alasan yang sama dengan `ScoreRecapBuilder`. |
| `app/Livewire/Eventner/Scoring/Perbandingan.php` | **baru** | Komponen halaman. `#[Layout('layouts.admin')]`, `queryString` = `selectedCategoryId`, `selectedRoundId`, `selectedGroupId`. Tenant-scoping lewat `findOwnCategory()`. |
| `resources/views/livewire/eventner/scoring/perbandingan.blade.php` | **baru** | Tampilan. |
| `routes/eventner.php` | ubah | `Route::get('scoring/perbandingan', …)->name('eventner.scoring.perbandingan');` — **sebelum** baris `scoring`, supaya tidak tertukar. |
| `resources/views/livewire/eventner/scoring/index.blade.php` | ubah | Tombol di header Input Nilai, membawa `selectedCategoryId` + `selectedRoundId` yang sedang dibuka. |
| `tests/Feature/JudgeScoreComparisonTest.php` | **baru** | Lihat §10. |

### Tanda tangan service

```php
public function build(Eventner $eventner, int $categoryId, ?int $roundId = null, ?int $groupId = null): array
```

Mengembalikan:

```php
[
  'juri'     => [ ['judge', 'sel', 'dinilai', 'bias', 'sigma', 'kelas', 'konsisten', 'selisih_ekstrem', 'sumber' => 'grup|final|ungrouped|level'], … ],
  'kriteria' => [ ['criteria', 'max', 'n', 'mean', 'median', 'min', 'max', 'rentang', 'sigma'], … ],
  'sel'      => [ ['peserta', 'kriteria', 'nilai' => [judgeId => float], 'rentang', 'menyimpang', 'flag'], … ],
  'temuan'   => [ ['tingkat' => 'info|perhatian', 'teks' => '…'], … ],
  'ringkas'  => ['juri_dibandingkan', 'juri_tak_dibandingkan', 'sel_total', 'sel_ditandai', 'kriteria_total'],
  'punya_data' => bool,
]
```

### Tombol di halaman Input Nilai

Ditempatkan di baris yang sama dengan **Mode Simulasi**, dengan gaya `btn-sm btn-outline-primary`. Hanya muncul saat `selectedCategoryId` terisi (tanpa tingkat, tidak ada yang bisa dibandingkan). Membawa babak yang sedang dibuka supaya tidak mendarat di analisis gabungan yang berbeda dari angka di layar asal.

---

## 9. Yang sengaja TIDAK dikerjakan

- **Ubah nilai dari halaman ini.** Halaman ini hanya membaca. Mengoreksi nilai tetap lewat Input Nilai — satu pintu tulis, seperti sekarang.
- **Hitung ulang peringkat juara.** Itu tugas `ChampionCalculator` dan `ScoreRecapBuilder`; menyalinnya di sini membuat rumus peringkat punya salinan ketiga.
- **"Nilai juri X salah".** Semua keluaran berbentuk *kecenderungan* + jumlah sel yang mendasarinya, bukan tuduhan. Juri dengan data kurang diberi "Data belum cukup", bukan kelas.
- **Tanda tangan digital / audit trail.** Sudah ada Activity Log; tidak digandakan di sini.
- **Ekspor PDF/CSV.** Service sudah dipisah supaya nanti tinggal dipanggil; untuk versi pertama, layarnya dulu.

---

## 10. Uji yang harus lulus

| Uji | Yang dijaga |
|---|---|
| `test_bias_terdeteksi_saat_dua_juri_menilai_kriteria_yang_sama` | Dua juri, rubrik sama, satu selalu +20% → kelas `Cenderung tinggi`, `sigma` kecil |
| `test_juri_tanpa_kriteria_bersama_tidak_dibandingkan` | Rubrik terbagi tanpa irisan → `kelas` = data belum cukup, **bias null**, dan ada baris "Tidak dapat dibandingkan" di tampilan |
| `test_babak_penyisihan_dan_final_tidak_tercampur` | Nilai final tidak ikut ke statistik penyisihan; dua bagian terpisah |
| `test_sel_dengan_selisih_besar_ditandai` | 10 vs 25 pada skala 25 → `flag` true, `menyimpang` menunjuk juri yang benar |
| `test_kriteria_berskala_berbeda_dinormalkan` | Kriteria 0–10 dan 0–100 dengan penyimpangan setara → `bias` keduanya mendekati sama |
| `test_bobot_kriteria_tidak_mempengaruhi_bias` | weight 1 vs 5, nilai identik → `bias` tidak berubah |
| `test_halaman_menolak_tingkat_milik_eventner_lain` | `selectedCategoryId` milik tenant lain → abort, tidak ada nilai yang bocor |
| `test_halaman_tidak_menulis_apa_pun` | Bandingkan `assessment_scores` + `score_deductions` sebelum & sesudah dibuka → sama persis |
| `test_tombol_perbandingan_ada_di_halaman_input_nilai` | Tautan ada, membawa `selectedCategoryId` |
| `test_data_belum_cukup_bila_sel_pembanding_kurang_dari_lima` | 2 sel → "Data belum cukup", tidak ada angka bias |

---

## 11. Urutan pengerjaan

1. `JudgeScoreComparison` + uji service (murni, tanpa HTTP) — 7 uji pertama.
2. Route + komponen + Blade halaman.
3. Tombol di halaman Input Nilai + 2 uji HTTP terakhir.
4. `node node_modules/vite/bin/vite.js build` bila Blade baru memakai kelas Tailwind yang belum ada di bundle — halaman ini memakai komponen Bootstrap template (`card`, `table`, `badge`) seperti layar Eventner lain, jadi kemungkinan besar tidak perlu.
5. `php artisan test` penuh.
