# PROPOSAL APLIKASI — BARIS APP

## Platform Manajemen Event & Kompetisi Terpadu

---

## 1. Ringkasan Eksekutif

**BARIS APP** adalah platform SaaS berbasis web untuk mengelola event dan kompetisi — khususnya lomba baris-berbaris (PBB), paskibra, dan kegiatan sekolah/ekstrakurikuler. Platform mencakup manajemen pendaftaran, penilaian juri, voting penonton, tiket event, livestream overlay, drawing/undian, hingga pengumuman juara — semua dalam satu sistem terintegrasi.

Dibangun dengan **Laravel 13** (Livewire 3) + **MySQL** + **Tailwind CSS**, dengan pembayaran via **AutoGoPay (QRIS)**.

---

## 2. Arsitektur Teknis

| Komponen | Spesifikasi |
|---|---|
| **Backend** | PHP 8.3, Laravel 13 |
| **Frontend** | Livewire 3, Tailwind CSS 4 (Vite), Alpine.js |
| **Database** | MySQL dengan 35+ tabel |
| **Autentikasi** | Custom auth (role-based: Admin / Eventner / Peserta) |
| **Payment Gateway** | AutoGoPay (QRIS dinamis) |
| **Email Service** | Maily.id API |
| **PDF Generator** | barryvdh/laravel-dompdf |
| **QR Code** | chillerlan/php-qrcode |
| **Activity Log** | spatie/laravel-activitylog |
| **Development** | Laragon (Windows), Vite HMR |

---

## 3. Struktur Role & Hak Akses

### 3.1. Admin
- Dashboard manajemen utama
- Kelola eventner (create/approve/reject)
- Atur setting situs (tema, font, logo, SEO)
- Atur landing page (hero, fitur, section visibility)
- Biaya pendaftaran eventner

### 3.2. Eventner (Penyelenggara Event)
- Buat & kelola profil event (nama, tanggal, lokasi, venue)
- Atur kategori lomba (hierarkis: tingkat → sub-kategori)
- Atur sistem penilaian & kriteria
- Kelola pendaftaran peserta (approve/reject)
- Kelola juri
- Atur voting (aktif/nonaktif, harga vote, jadwal)
- Atur tiket (harga, stok, jadwal)
- Atur drawing/undian
- Konfigurasi livestream overlay
- Kelola sponsor & tenant
- Lihat rekap nilai & champion

### 3.3. Peserta (Sekolah/Tim)
- Daftar event via magic link
- Isi data tim (pelatih, danton, peserta)
- Upload berkas (logo, surat tugas, foto)
- Cek status pendaftaran

---

## 4. Fitur-Fitur Utama

### 4.1. Manajemen Pendaftaran Event (Multi-Step)
- **Step 1**: Pilih kategori lomba dengan kuota & limit per sekolah
- **Step 2**: Data sekolah (NPSN, nama, pelatih, kontak)
- **Step 3**: Konfirmasi & submit
- Sistem **magic link** → peserta akses via email tanpa password
- Status: `booking` → `confirmed` → `Terverifikasi` / `Ditolak`

### 4.2. Sistem Penilaian Juri (Scoring)
- **Format Nilai**: Buat kategori penilaian, sub-kategori, dan kriteria dengan bobot
- **Input Nilai**: Juri menilai peserta per kategori
- **Rekap Nilai**: Agregasi otomatis, tampilan per kategori
- **Scoreboard Publik**: Tampilan real-time via `/{scoringCode}`

### 4.3. Voting Penonton
- Vote berbayar via QRIS (AutoGoPay)
- Harga vote eventner bisa diatur sendiri
- Vote booster: paket vote dengan bonus
- Hasil voting live + PDF recap
- Transaksi tervalidasi via webhook

### 4.4. Tiket Event
- Penjualan tiket dengan harga, kuota, jadwal
- QRIS untuk pembayaran
- QR Code tiket untuk check-in
- Konfirmasi email otomatis

### 4.5. Drawing / Undian
- Halaman spin untuk pengundian peserta
- Tampilan publik untuk hasil undian

### 4.6. Livestream Overlay
- Layar untuk siaran langsung (OBS)
- Mode: full view, vote, kegiatan, greenscreen
- Theme customization per event
- Marquee leaderboard, komentar vote
- Top voter card

### 4.7. Champion / Juara
- Kategori juara (juara 1, 2, 3, harapan, dll)
- Rank titles per kategori
- Tiebreak logic
- Pengumuman juara publik (halaman champion)
- PDF ranking

### 4.8. Pendaftaran Eventner (Self-Registration)
- Daftar mandiri sebagai eventner
- **Paket Gratis**: trial 3 hari, fitur premium terkunci
- **Paket Berbayar**: bayar sekali via QRIS, akses penuh
- Approval admin (otomatis untuk paid + bayar sukses)

### 4.9. Subdomain Halaman Publik
- Setiap eventner bisa punya subdomain sendiri
- URL: `nama-event.berbaris.app`
- Dashboard tetap di domain utama
- Fallback ke `/event/{slug}` jika subdomain tidak diisi

### 4.10. Feature Gating System
- Kontrol akses fitur berdasarkan plan
- Fitur premium: Tiket, Vote, Drawing, Champion, Livestream, Sponsor, Tenant, Format Nilai
- Trial countdown + banner dashboard

### 4.11. Lainnya
- Activity log (riwayat perubahan data)
- FAQ per event
- Galeri foto event
- Sponsor & tenant management
- Check-in tiket via token
- Cetak PDF rekap nilai & voting

---

## 5. Struktur Database (35+ Tabel)

### Core
- `users` — Admin, Eventner, Peserta (role enum)
- `eventners` — Profil event (37+ kolom, termasuk theme_config JSON)

### Pendaftaran & Peserta
- `registrations` — Booking pendaftaran sekolah
- `participants` — Data peserta per registrasi
- `competition_categories` — Kategori lomba (parent/child hierarchy)

### Penilaian
- `assessment_categories`
- `assessment_sub_categories`
- `assessment_criterias` — Kriteria dengan `score_options` JSON
- `assessment_scores` — Nilai per juri per peserta
- `deduction_categories` / `deduction_criterias` / `score_deductions`

### Champion
- `champion_categories` / `champion_rank_titles`
- `champion_assessment` / `champion_tiebreak`

### Voting
- `vote_transactions` — Riwayat vote (PAID/PENDING/EXPIRED)
- `vote_boosters` — Paket vote booster

### Tiket
- `tickets` — Pembelian tiket dengan QR code path

### Event Content
- `event_faqs` / `event_galleries`
- `sponsors` / `tenants`
- `judges`
- `overlay_settings` — Konfigurasi overlay JSON
- `settings` — Key-value store untuk pengaturan global

### Lainnya
- `activity_log` — Spatie activity log
- `sessions` — Database sessions

---

## 6. Alur Bisnis

```
  PESERTA (Sekolah)               EVENTNER                   ADMIN
       │                             │                         │
       │   Daftar Event              │                         │
       ├─── Magic Link ───────────►  │                         │
       │   Upload Berkas             │                         │
       │   Isi Data Tim              │                         │
       │                             ├── Manajemen Event       │
       │   Verifikasi ◄──────────────┤── Atur Kategori         │
       │                             │── Atur Juri             │
       │   Hasil Lomba               │── Atur Penilaian        │
       │   Scoreboard ◄──────────────┤── Atur Voting           │
       │   Pengumuman Juara          │── Atur Tiket            │
       │                             │── Drawing               │
       │                             │── Livestream Overlay    │
       │                             │                         │
       │   Vote ◄──────────────────► │ (QRIS)                  │
       │   Beli Tiket ◄────────────► │ (QRIS)                  │
       │                             │                         ├── Kelola Eventner
       │                             │                         ├── Approve Registrasi
       │                             │                         ├── Setting Situs
       │                             │                         └── Landing Page
       │                             │
       │       Daftar Eventner ───────────────────────────────► │
       │         (Free/Paid)         │         Approve ◄─────── │
```

---

## 7. Monetisasi

| Sumber | Mekanisme |
|---|---|
| **Pendaftaran Eventner (Paid)** | Bayar sekali via QRIS — Rp 50.000 (default, bisa diubah admin) |
| **Voting Penonton** | Harga per vote ditentukan eventner + biaya platform |
| **Tiket Event** | Harga tiket ditentukan eventner, platform potong fee |
| **Paket Gratis** | Trial 3 hari, konversi ke paid setelah trial |

---

## 8. Tech Stack & Dependencies

### PHP Packages (composer.json)
- `laravel/framework ^13.0`
- `livewire/livewire`
- `barryvdh/laravel-dompdf` — PDF generation
- `chillerlan/php-qrcode` — QR code
- `spatie/laravel-activitylog` — Activity logging
- `laravel/boost` — Laravel dev tools
- `laravel/pail` — Log viewer
- `laravel/tinker` — REPL

### Frontend
- Tailwind CSS 4 (via Vite)
- Alpine.js (via Livewire)
- Tabler Icons (via @tabler/icons-webfont)
- Google Fonts (Inter, Poppins, Plus Jakarta Sans, dll)

### Infrastruktur
- **Server**: Apache/Nginx via Laragon (local), Nginx (production)
- **PHP**: 8.3+
- **Database**: MySQL
- **Redis/Memcached**: Tidak digunakan
- **Queue**: Database driver

---

## 9. Alur Pembayaran (QRIS)

```
  User Klik Bayar
       │
       ▼
  AutoGoPay.generateQris(amount)
       │
       ▼
  Tampilkan QR di halaman
       │
       ├── User Scan & Bayar
       │
       ▼
  Webhook AutoGoPay → transaction.received
       │
       ├── settlement → Update status PAID
       │                   └── Eventner auto-approved
       │                   └── Kirim email notifikasi
       │
       └── expire → Update status EXPIRED
```

---

## 10. Routing Architecture

| Domain | Routes | Middleware |
|---|---|---|
| `berbaris.app/` | Landing, Login, Register Eventner | guest |
| `berbaris.app/event/{slug}/*` | 10 route publik (detail, peserta, vote, tiket, dll) | web |
| `berbaris.app/dashboard` | Dashboard router (redirect berdasarkan role) | auth |
| `berbaris.app/admin/*` | Panel admin | auth + role:Admin |
| `berbaris.app/eventner/*` | Panel eventner (36+ route) | auth + role:Eventner |
| `*.berbaris.app/` | Subdomain halaman publik event | web + subdomain |

---

## 11. Status Pengembangan

| Fitur | Status |
|---|---|
| Landing page + theme customization | ✅ Selesai |
| Manajemen eventner (CRUD + approval) | ✅ Selesai |
| Pendaftaran sekolah (2-step) | ✅ Selesai |
| Sistem penilaian & scoring | ✅ Selesai |
| Champion & juara | ✅ Selesai |
| Drawing / undian | ✅ Selesai |
| Voting penonton (QRIS) | ✅ Selesai |
| Vote booster | ✅ Selesai |
| Tiket event (QRIS) | ✅ Selesai |
| Check-in tiket | ✅ Selesai |
| Livestream overlay | ✅ Selesai |
| Scoreboard publik | ✅ Selesai |
| Subdomain publik | ✅ Selesai |
| Self-registration eventner (free/paid) | ✅ Selesai |
| Feature gating + trial system | ✅ Selesai |
| Integrasi email (Maily.id) | ✅ Selesai |
| Sponsor & tenant | ✅ Selesai |
| FAQ & galeri event | ✅ Selesai |
| Multi-kategori booking | ✅ Selesai |
| Activity log | ✅ Selesai |
| **Pending: Payment gateway untuk pendaftaran paid** | 🔄 Selesai (AutoGoPay) |
| **Pending: Dashboard analytics lanjutan** | 📋 Rencana |

---

## 12. Keunggulan Kompetitif

1. **All-in-one**: Pendaftaran, penilaian, voting, tiket, overlay — satu platform
2. **Tanpa login untuk peserta**: Magic link via email
3. **Pembayaran QRIS**: Real-time, tanpa perlu rekening bersama
4. **Livestream overlay**: Cocok untuk siaran langsung lomba
5. **Subdomain kustom**: Setiap event punya identitas sendiri
6. **Pricing fleksibel**: Free trial + paid one-time
7. **Hierarki kategori**: Tingkat → sub-kategori, sesuai struktur lomba PBB
8. **Mobile-friendly**: Tailwind responsive design

---

## 13. Target Pengguna

| Segmen | Use Case |
|---|---|
| **Sekolah / OSIS** | Lomba PBB, paskibra, upacara |
| **Pramuka** | Lomba keterampilan baris-berbaris |
| **Universitas** | UKM, dies natalis, kompetisi |
| **Pemerintah Daerah** | Event kabupaten/kota, FORBASI |
| **Event Organizer** | Kompetisi umum |

---

## 14. Pengembangan Selanjutnya

- Dashboard analytics dengan grafik
- Export data peserta ke Excel
- Multi-language (EN)
- API untuk integrasi pihak ketiga
- Notifikasi WhatsApp
- Automated scoring tiebreak
- Template kategori lomba preset
- Manajemen kehadiran/scanner offline
- Public event directory / marketplace
