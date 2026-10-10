# Voucher/Diskon Pendaftaran Eventner

> Status: PLAN — belum diimplementasi. Wireframe: `doc/voucher-register-wireframe.html`.

## Masalah
Pendaftaran eventner (`/register/eventner`) menagih paket SaaS via QRIS dengan harga efektif `price - discount_percent` (diskon global per paket). Tidak ada cara memberi potongan terarah: kode promo untuk kampanye/komunitas tertentu, kuota terbatas, berperiode.

## Ruang lingkup
Voucher berlaku pada **biaya pendaftaran paket SaaS** (bayar-sekali-per-event), BUKAN fee pendaftaran peserta lomba (`registrations.total_fee`). Alur peserta lomba tidak tersentuh.

## Keputusan
| Hal | Keputusan |
|---|---|
| Siapa membuat voucher | Admin platform (bukan eventner — pendaftar belum punya event) |
| Berlaku ke | Paket berbayar (`is_free=false, is_contact=false`); opsional dibatasi 1 paket |
| Tipe | `percent` (dengan cap `max_discount`) / `flat` |
| Kuota | Dihitung dari eventner yang **sudah membayar** (registration_paid_at) — daftar-tak-bayar tak mengonsumsi |
| Snapshot | Nominal potongan dibekukan ke `eventners.voucher_discount` saat daftar — hapus/nonaktif voucher belakangan tidak mematahkan webhook |
| Webhook | `expected = effective_price - voucher_discount` |

## Skema

**Tabel baru `registration_vouchers`**
```
id, code (unique, uppercase), type enum('percent','flat'), value unsigned,
max_discount unsigned nullable        -- cap persen; null = tanpa cap
max_uses unsigned nullable            -- null = tanpa batas
saas_plan_id FK nullable              -- null = semua paket berbayar
starts_at nullable, ends_at nullable, is_active bool, timestamps
```

**Kolom baru di `eventners`**
```
registration_voucher_id FK nullOnDelete
voucher_discount integer default 0    -- snapshot nominal potongan saat daftar
```

## Alur pendaftar
1. Pilih paket berbayar → isi form.
2. Input **Kode Promo** → `applyPromo()` (live, blur): cek aktif → periode → kuota → cocok paket. Valid: chip hijau "Kode `X` diterapkan — hemat Rp Y", harga coret. Tak valid: pesan merah, tanpa potongan.
3. Submit → `$fee = max(0, effective_price - diskon)` → QRIS dengan nominal terpotong → `registration_voucher_id` + `voucher_discount` tersimpan.
4. Webhook settlement: `expected = ($eventner->saasPlan?->effective_price ?? fallback) - $eventner->voucher_discount` — tetap `$paidAmount >= expected`.

## Perubahan file
| Aksi | Path |
|---|---|
| Baru | `database/migrations/…_create_registration_vouchers_table.php` (+ kolom eventners) |
| Baru | `app/Models/RegistrationVoucher.php` (scope `validFor($planId)`, `hitungPemakaian()`, `diskonUntuk(SaasPlan $plan): int`) |
| Ubah | `app/Livewire/Public/EventnerRegister.php` — properti `$kodePromo/$voucherId/$voucherLabel`, `applyPromo()`, fee + simpan voucher di `save()` |
| Ubah | `resources/views/livewire/public/eventner-register.blade.php` — input kode + chip diskon + harga coret |
| Ubah | `app/Http/Controllers/Webhook/AutoGoPayWebhookController.php:145` — expected dikurangi `voucher_discount` |
| Baru | `app/Livewire/Admin/VoucherIndex.php` + blade — CRUD + rekap terpakai/sisa |
| Ubah | `routes/web.php` (grup admin) — `Route::get('vouchers', …)` + menu admin |
| Baru | `tests/Feature/RegistrationVoucherTest.php` |

## Logika diskon (`diskonUntuk`)
```
percent → round(effective_price * value / 100); cap ke max_discount bila ada
flat    → min(value, effective_price)   // tidak pernah negatif
```
Selalu dihitung dari `effective_price` (bukan `price`) — sifat `effective_price` sebagai satu-satunya dasar tagihan dipertahankan.

## Kuota & periode
- Pemakaian = `Eventner::where('registration_voucher_id', id)->whereNotNull('registration_paid_at')->count()`.
- Snapshot tidak dihitung ganda: FK nullOnDelete kehilangan jejak bila baris voucher dihapus — karena itu voucher TIDAK boleh dihapus setelah dipakai, cukup `is_active=false` (catatan di panel admin).
- `starts_at/ends_at` dibandingkan `now()` saat validasi.

## Test (pola Feature, nama Indonesia)
1. `test_kode_promo_valid_menampilkan_diskon` — chip + harga terpotong.
2. `test_kode_promo_tak_kenal_ditolak` — tanpa potongan.
3. `test_kode_promo_kadaluarsa_ditolak` (ends_at lewat).
4. `test_kode_promo_belum_mulai_ditolak` (starts_at depan).
5. `test_kuota_habis_ditolak` — 1 slot, dipakai eventner paid → ditolak; eventner belum bayar tak mengonsumsi.
6. `test_kode_promo_batasi_paket_ditolak_di_paket_lain`.
7. `test_diskon_persen_menghormati_cap`.
8. `test_diskon_flat_tidak_melampaui_harga` — fee ≥ 0.
9. `test_qris_ditagih_dengan_nominal_terpotong` — `save()` → assertSee/property `paymentAmount` = efektif − diskon.
10. `test_webhook_menerima_settlement_nominal_terpotong` — handleEventnerSettlement lulus bila `paidAmount = expected - diskon` (regresi mismatch).
11. `test_voucher_nonaktif_setelah_daftar_tidak_mematahkan_webhook` — snapshot bekerja.
12. `test_admin_membuat_dan_menyunting_voucher` — CRUD + validasi.

## Urutan kerja
1. Migration + model + scope.
2. EventnerRegister (logika + blade).
3. Webhook.
4. Admin CRUD + menu.
5. Test → suite penuh.
6. Build tak perlu (tanpa aset).

## Risiko
- Nominal webhook = 0 bila diskon 100% → AutoGoPay menolak nominal 0; validasi admin tolak `value` yang menggratiskan penuh (percent ≤ 90, flat < price termurah).
- Race kuota (dua submit bersamaan melewati sisa 1) — diterima: volume pendaftaran eventner rendah; kuota dicek ulang saat validasi.
