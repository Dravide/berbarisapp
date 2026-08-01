# API Documentation — Berbaris Mobile App

Base URL: `https://berbaris.com/api/v1`

---

## A. PUBLIC ENDPOINTS (No Auth)

---

### A1. Daftar Event

**Request:**
```
GET /events?search=&lokasi=&page=1
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "nama_event": "Lomba PBB se-Jawa Barat 2026",
      "slug": "lomba-pbb-abc12",
      "subdomain": "pbb2026",
      "deskripsi": "Deskripsi event...",
      "poster": "https://berbaris.com/storage/events/poster.jpg",
      "logo_event": "https://berbaris.com/storage/events/logo.jpg",
      "header_banner": "https://berbaris.com/storage/events/banner.jpg",
      "venue": "GOR Padjajaran",
      "lokasi": "Bandung",
      "tanggal": "2026-08-15",
      "tanggal_akhir": "2026-08-17",
      "vote_active": true,
      "vote_start": "2026-08-01T00:00:00Z",
      "vote_end": "2026-08-17T23:59:59Z",
      "vote_price": 1000,
      "registration_status": "open",
      "link_instagram": "https://instagram.com/...",
      "link_tiktok": "https://tiktok.com/...",
      "link_whatsapp": "https://wa.me/...",
      "link_livestreaming": null,
      "diselenggarakan_oleh": "Dinas Pendidikan Jabar",
      "categories": [
        {
          "id": 1,
          "name": "PBB Putra — Regu Inti",
          "parent_id": null,
          "kuota": 20,
          "registration_fee": "150000.00",
          "total_peserta": 15
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45
  }
}
```

---

### A2. Detail Event

**Request:**
```
GET /events/{slug}
```

**Response (200):** Sama struktur dengan item di atas.

**Response (404):**
```json
{ "message": "Event tidak ditemukan." }
```

---

### A3. Kategori Lomba

**Request:**
```
GET /events/{slug}/categories
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "eventner_id": 1,
      "parent_id": null,
      "name": "PBB Putra",
      "full_name": "PBB Putra",
      "tanggal_pelaksanaan": null,
      "kuota": null,
      "max_registrations_per_school": 2,
      "registration_fee": "0.00",
      "sort_order": 1,
      "registrations_count": 5,
      "parent": null,
      "children": [...]
    },
    {
      "id": 2,
      "parent_id": 1,
      "name": "Regu Inti",
      "full_name": "PBB Putra — Regu Inti",
      "registrations_count": 3,
      "parent": { "id": 1, "name": "PBB Putra" }
    }
  ]
}
```

---

### A4. Daftar Peserta per Kategori

**Request:**
```
GET /events/{slug}/participants?category_id=2&search=
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 5,
      "nama_sekolah": "SMA N 1 Bandung",
      "logo_sekolah": "https://berbaris.com/storage/registrations/logos/logo.jpg",
      "total_votes": 150,
      "status_berkas": "Terverifikasi",
      "kategori": {
        "id": 2,
        "name": "PBB Putra — Regu Inti"
      },
      "jumlah_peserta": 12
    }
  ]
}
```

---

### A4b. Galeri Foto

**Request:**
```
GET /events/{slug}/gallery
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "image": "https://berbaris.com/storage/events/gallery/abc.jpg",
      "caption": "Pembukaan lomba",
      "sort_order": 1
    }
  ]
}
```

---

### A4c. FAQ

**Request:**
```
GET /events/{slug}/faq
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "question": "Kapan technical meeting?",
      "answer": "H-1 sebelum lomba, jam 09.00 WIB."
    }
  ]
}
```

---

### A4d. Sponsor

**Request:**
```
GET /events/{slug}/sponsors
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Bank Jabar",
      "logo": "https://berbaris.com/storage/sponsors/logo.png",
      "link": "https://bankjabar.co.id",
      "type": "sponsor"
    }
  ]
}
```

`type` values: `sponsor`, `medpart`, `partner`, `supporting`.

---

### A4e. Tenant / Bazar

**Request:**
```
GET /events/{slug}/tenants
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Baso Aci Mang Udin",
      "logo": "https://berbaris.com/storage/tenants/logo.png",
      "description": "Bakso aci original Bandung.",
      "type": "food"
    }
  ]
}
```

`type` values: `food`, `beverage`, `bazaar`, `souvenir`, `other`.

---

### A4f. Juknis PDF

**Request:**
```
GET /events/{slug}/juknis
```

**Response (200):** Streaming PDF (application/pdf). Header `Content-Disposition: attachment; filename=Juknis_*.pdf`.

**Response (404):**
```json
{ "message": "Juknis belum tersedia untuk event ini." }
```

---

### A4g. Hasil Pengundian (Nomor Urut Tampil)

**Request:**
```
GET /events/{slug}/drawing-results
```

**Response (200):**
```json
{
  "data": [
    {
      "category_id": 2,
      "name": "PBB Putra — Regu Inti",
      "total_peserta": 12,
      "results": [
        { "urutan": 1, "nama_sekolah": "SMA N 1 Bandung", "label_pasukan": "Regu Inti" },
        { "urutan": 2, "nama_sekolah": "MAN 2 Bogor", "label_pasukan": "Regu Utama" }
      ]
    }
  ]
}
```

---

### A5. Vote — Generate QRIS

**Request:**
```
POST /vote/calculate
Content-Type: application/json

{
  "event_slug": "lomba-pbb-abc12",
  "registration_id": 5,
  "vote_count": 10,
  "voter_name": "Budi Santoso",
  "voter_email": "budi@email.com",
  "comment": "Semangat!" (opsional)
}
```

**Response (200):**
```json
{
  "data": {
    "transaction_id": 123,
    "autogopay_transaction_id": "AGP-xxx-yyy",
    "qr_url": "https://api.autogopay.id/qr/xxx.png",
    "qr_string": "000201010212...",
    "expiry_time": "2026-08-01T00:05:00Z",
    "amount": 10000,
    "votes_earned": 10,
    "vote_multiplier": 1
  }
}
```

**Response (400):**
```json
{ "message": "Fitur Vote sudah ditutup." }
{ "message": "Masa voting sudah berakhir." }
```

**Response (429):**
```json
{ "message": "Terlalu banyak permintaan. Silakan coba lagi nanti." }
```

---

### A6. Cek Status Pembayaran Vote

**Request:**
```
GET /vote/status/{transactionId}
```

**Response (200):**
```json
{
  "data": {
    "status": "PENDING",
    "paid_at": null,
    "votes_earned": 10
  }
}
```

```
Status values: PENDING → PAID / EXPIRED
```

---

### A7. Komentar Vote (Widget)

**Request:**
```
GET /vote/comments?event_slug=lomba-pbb-abc12
```

**Response (200):**
```json
{
  "data": [
    {
      "nama_sekolah": "SMA N 1 Bandung",
      "comment": "Semangat kakak!",
      "time": "2 menit lalu"
    }
  ]
}
```

---

### A8. Scoreboard Public

**Request:**
```
GET /scoreboard/{scoringCode}
GET /scoreboard/{scoringCode}/category/{categoryId}
```

**Response (200):**
```json
{
  "data": {
    "category": "PBB Putra — Regu Inti",
    "rankings": [
      {
        "id": 5,
        "nama_sekolah": "SMA N 1 Bandung",
        "logo_sekolah": "https://...",
        "total_skor": 925.5,
        "total_votes": 150,
        "ranking": 1
      },
      {
        "id": 8,
        "nama_sekolah": "MAN 2 Bogor",
        "total_skor": 910.0,
        "total_votes": 200,
        "ranking": 2
      }
    ]
  }
}
```

---

### A9. Champions / Juara

**Request:**
```
GET /champions/{scoringCode}
```

**Response (200):**
```json
{
  "data": {
    "event": {
      "nama_event": "Lomba PBB se-Jawa Barat 2026",
      "slug": "lomba-pbb-abc12"
    },
    "champion_categories": [
      {
        "id": 1,
        "name": "Juara Umum PBB Putra",
        "winners": [
          {
            "rank": 1,
            "title": "Juara 1",
            "nama_sekolah": "SMA N 1 Bandung",
            "logo_sekolah": "https://..."
          },
          {
            "rank": 2,
            "title": "Juara 2",
            "nama_sekolah": "MAN 2 Bogor",
            "logo_sekolah": "https://..."
          }
        ]
      }
    ]
  }
}
```

---

## B. QR SCAN (Dapat Token)

---

### B1. Scan QR

**Request:**
```
POST /qr/scan
Content-Type: application/json

{
  "qr_token": "A7K9M2X3"
}
```

**Response (200):**
```json
{
  "token": "1|abc123sanctumtoken...",
  "data": {
    "id": 5,
    "eventner_id": 1,
    "nama_sekolah": "SMA N 1 Bandung",
    "npsn": "20234567",
    "label_pasukan": "Regu Inti",
    "nama_pelatih": "Ahmad Sudrajat",
    "no_hp": "081234567890",
    "school_email": "sman1@sch.id",
    "danton_nama": "Rudi Hermawan",
    "danton_nisn": "1234567890",
    "status_berkas": "confirmed",
    "is_finalized": true,
    "urutan_tampil": 5,
    "payment_status": "paid",
    "total_fee": "150000.00",
    "logo_sekolah": "https://berbaris.com/storage/...",
    "foto_pelatih": "https://berbaris.com/storage/...",
    "danton_foto": null,
    "surat_tugas": "https://berbaris.com/storage/...",
    "bukti_pendaftaran": null,
    "payment_proof": "https://berbaris.com/storage/...",
    "event": {
      "id": 1,
      "nama_event": "Lomba PBB se-Jawa Barat 2026",
      "slug": "lomba-pbb-abc12",
      "venue": "GOR Padjajaran",
      "tanggal": "2026-08-15",
      "logo": "https://...",
      "scoring_code": "ABC123"
    },
    "competition_category": {
      "id": 2,
      "name": "PBB Putra — Regu Inti"
    },
    "participants": [
      {
        "id": 10,
        "nama": "Andi",
        "nisn": "123456",
        "foto": "https://..."
      }
    ],
    "payment_bank": {
      "bank_name": "BCA",
      "account_number": "1234567890",
      "account_name": "Panitia Lomba"
    },
    "total_votes": 150
  }
}
```

**Response (404):**
```json
{ "message": "QR tidak valid." }
```

**⚠️ QR store:** Simpan `token` di FlutterSecureStorage. Tiap request portal pake `Authorization: Bearer {token}`.

---

## C. PRIVATE ENDPOINTS (Bearer Token)

**Header setiap request:**
```
Authorization: Bearer 1|abc123sanctumtoken...
```

---

### C1. Data Pendaftaran

**Request:**
```
GET /portal/registration
```

**Response (200):** Sama struktur dengan `data` di QR scan response.

---

### C2. Update Data Pendaftaran

**Request:**
```
PUT /portal/registration
Content-Type: application/json

{
  "nama_pelatih": "Ahmad Sudrajat",
  "danton_nama": "Rudi Hermawan",
  "danton_nisn": "1234567890"
}
```

**Response (200):**
```json
{ "message": "Data berhasil disimpan." }
```

**Response (400):**
```json
{ "message": "Data sudah terverifikasi, tidak bisa diubah." }
```

---

### C3. Finalisasi Data

**Request:**
```
POST /portal/confirm
```

**Response (200):**
```json
{ "message": "Data berhasil difinalisasi." }
```

**Response (400):**
```json
{
  "message": "Konfirmasi bisa dilakukan setelah Technical Meeting.",
  "technical_meeting": "2026-08-14T09:00:00Z"
}
```

---

### C4. Daftar Anggota Pasukan

**Request:**
```
GET /portal/participants
```

**Response (200):**
```json
{
  "data": [
    {
      "id": 10,
      "nama": "Andi",
      "nisn": "123456",
      "foto": "https://..."
    }
  ]
}
```

---

### C5. Upload Berkas (Multipart)

**Request:**
```
POST /portal/upload/logo
POST /portal/upload/participant-photo
POST /portal/upload/surat-tugas
POST /portal/upload/pelatih-foto
POST /portal/upload/danton-foto
POST /portal/upload/payment-proof
Content-Type: multipart/form-data

file: [binary]
```

**Keterangan per endpoint:**

| Endpoint | Tipe File | Max Size | Kolom yang diupdate |
|---|---|---|---|
| `/upload/logo` | image (jpg,png) | 3MB | `logo_sekolah` |
| `/upload/participant-photo` | image (jpg,png) | 3MB | `participants.foto` (per ID) |
| `/upload/surat-tugas` | pdf,jpg,png | 5MB | `surat_tugas` |
| `/upload/pelatih-foto` | image (jpg,png) | 3MB | `foto_pelatih` |
| `/upload/danton-foto` | image (jpg,png) | 3MB | `danton_foto` |
| `/upload/payment-proof` | image (jpg,png) | 3MB | `payment_proof` |

**Participant photo — request body tambahan:**
```
POST /upload/participant-photo
Content-Type: multipart/form-data

file: [binary]
participant_id: 10   (opsional, kalau diisi update foto peserta tertentu)
```

**Response upload (200):**
```json
{
  "message": "Logo sekolah berhasil diupload.",
  "path": "registrations/logos/abc123.jpg",
  "url": "https://berbaris.com/storage/registrations/logos/abc123.jpg"
}
```

**Response upload participant photo (200):**
```json
{
  "message": "Foto peserta berhasil diupload.",
  "path": "registrations/peserta/abc123.jpg"
}
```

---

### C6. Progress Nilai

**Request:**
```
GET /portal/scores
```

**Response (200) — belum dinilai:**
```json
{
  "data": {
    "total_skor": 0,
    "maks_skor": 0,
    "persentase": 0,
    "is_finalized": false,
    "categories": []
  }
}
```

**Response (200) — sudah dinilai:**
```json
{
  "data": {
    "total_skor": 875.0,
    "maks_skor": 1000.0,
    "persentase": 87.5,
    "is_finalized": true,
    "categories": [
      {
        "nama": "Kekompakan",
        "skor": 350.0,
        "maks": 400.0,
        "persentase": 87.5,
        "sub_categories": [
          {
            "nama": "Gerakan Dasar",
            "skor": 185.0,
            "maks": 200.0,
            "criterias": [
              {
                "nama": "Keseragaman langkah",
                "skor": 95,
                "maks": 100,
                "bobot": 1
              },
              {
                "nama": "Ketepatan balik",
                "skor": 90,
                "maks": 100,
                "bobot": 1
              }
            ]
          }
        ]
      }
    ]
  }
}
```

---

### C7. Ranking

**Request:**
```
GET /portal/ranking
```

**Response (200):**
```json
{
  "data": {
    "posisi": 4,
    "total_peserta": 20,
    "total_skor_saya": 875,
    "ranking": [
      {
        "id": 3,
        "nama_sekolah": "SMA N 1 Bandung",
        "total_skor": 950.0,
        "is_me": false
      },
      {
        "id": 7,
        "nama_sekolah": "MAN 2 Bogor",
        "total_skor": 920.0,
        "is_me": false
      },
      {
        "id": 5,
        "nama_sekolah": "SMA N 5 Jakarta",
        "total_skor": 890.0,
        "is_me": false
      },
      {
        "id": 5,
        "nama_sekolah": "SMA N 1 Bandung",
        "total_skor": 875.0,
        "is_me": true
      }
    ]
  }
}
```

**Note:** `is_me` untuk highlight user di list. Hanya return top 10 + user.

---

### C8. Tiket Digital

**Request:**
```
GET /portal/ticket
```

**Response (200):**
```json
{
  "data": {
    "nama_sekolah": "SMA N 1 Bandung",
    "label_pasukan": "Regu Inti",
    "kategori": "PBB Putra — Regu Inti",
    "event": "Lomba PBB se-Jawa Barat 2026",
    "venue": "GOR Padjajaran",
    "tanggal": "2026-08-15",
    "logo_event": "https://...",
    "magic_token": "abc123...",
    "qr_token": "A7K9M2X3",
    "status_berkas": "Terverifikasi"
  }
}
```

---

## D. ERROR RESPONSES (Semua Endpoint)

```json
// 401 — Token tidak valid
{ "message": "Unauthenticated." }

// 404 — Data tidak ditemukan
{ "message": "QR tidak valid." }

// 422 — Validasi gagal
{
  "message": "The vote_count field is required.",
  "errors": {
    "vote_count": ["The vote_count field is required."]
  }
}

// 429 — Rate limit
{ "message": "Terlalu banyak permintaan. Silakan coba lagi nanti." }

// 500 — Server error
{ "message": "Gagal memproses vote: ..." }
```

---

## E. STATUS CODE SUMMARY

| Method | Endpoint | 200 | 400 | 404 | 422 | 429 | 500 |
|---|---|---|---|---|---|---|---|
| GET | /events | ✅ | - | - | - | - | - |
| GET | /events/{slug} | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/categories | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/participants | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/gallery | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/faq | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/sponsors | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/tenants | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/juknis | ✅ | - | ✅ | - | - | - |
| GET | /events/{slug}/drawing-results | ✅ | - | ✅ | - | - | - |
| POST | /vote/calculate | ✅ | ✅ | - | ✅ | ✅ | ✅ |
| GET | /vote/status/{id} | ✅ | - | ✅ | - | - | - |
| GET | /vote/comments | ✅ | - | - | ✅ | - | - |
| GET | /scoreboard/{code} | ✅ | - | ✅ | - | - | - |
| GET | /scoreboard/{code}/cat/{id} | ✅ | - | ✅ | - | - | - |
| GET | /champions/{code} | ✅ | - | ✅ | - | - | - |
| POST | /qr/scan | ✅ | - | ✅ | ✅ | - | - |
| GET | /portal/registration | ✅ | - | - | - | - | - |
| PUT | /portal/registration | ✅ | ✅ | - | - | - | - |
| POST | /portal/confirm | ✅ | ✅ | - | - | - | - |
| GET | /portal/participants | ✅ | - | - | - | - | - |
| POST | /portal/upload/* | ✅ | - | - | ✅ | - | ✅ |
| GET | /portal/scores | ✅ | - | - | - | - | - |
| GET | /portal/ranking | ✅ | - | - | - | - | - |
| GET | /portal/ticket | ✅ | - | - | - | - | -

✅ = Ada response
- = Tidak terjadi
