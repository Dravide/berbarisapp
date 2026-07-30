# Langkah-Langkah Development — Berbaris Flutter App

## Fase 0: Prasyarat

```
Sebelum mulai coding:
✅ Laravel app (backend) — sudah jadi
✅ Database MySQL — sudah jalan
✅ Flutter SDK 3.x — install
✅ Android Studio / VS Code — install
✅ Emulator / device fisik — siap
✅ Git repo — clone project
```

---

## Fase 1: Backend API + Setup Project (3 hari)

### Hari 1: API Routes Laravel

**Buat file `routes/api.php`:**

```
1. Buat controller: EventController, VoteController, QrController,
   PortalController, UploadController, ScoreboardController, ChampionController
2. Definisikan semua endpoint public + private (lihat docs/api-endpoints.md)
3. Testing pake Postman / curl
```

**Yang dikerjain:**
```bash
php artisan make:controller Api/V1/EventController --resource
php artisan make:controller Api/V1/VoteController
php artisan make:controller Api/V1/QrController
php artisan make:controller Api/V1/PortalController
php artisan make:controller Api/V1/UploadController
php artisan make:controller Api/V1/ScoreboardController
php artisan make:controller Api/V1/ChampionController
```

**Migration baru:**
```bash
php artisan make:migration add_qr_token_to_registrations_table
```
```php
// qr_token 8 digit, unik, auto-generate
$table->string('qr_token', 8)->nullable()->unique()->after('magic_token');
```

**Model Registration — update boot():**
```php
if (!$model->qr_token) {
    $model->qr_token = strtoupper(Str::random(8));
}
```

**Resource:**
```bash
php artisan make:resource Api/V1/EventResource
php artisan make:resource Api/V1/RegistrationResource
php artisan make:resource Api/V1/ScoreResource
```

### Hari 2: Setup Flutter Project

```bash
flutter create --org com.berbaris --project-name berbaris_app .
```

**Struktur folder:**
```bash
mkdir -p lib/{core/{constants,theme,network,storage,utils,widgets},data/{models,repositories,datasources/{remote,local}},providers/{public,portal},ui/{router,public/{home,event-detail,vote,scoreboard,champions},qr,portal/{dashboard,upload,scores,ranking,ticket}}}
```

**pubspec.yaml — dependencies:**
```yaml
dependencies:
  flutter:
    sdk: flutter
  flutter_riverpod: ^2.5.0
  go_router: ^14.0.0
  dio: ^5.4.0
  mobile_scanner: ^5.0.0
  qr_flutter: ^4.1.0
  image_picker: ^1.0.7
  file_picker: ^6.1.1
  flutter_image_compress: ^2.1.0
  flutter_secure_storage: ^9.0.0
  sqflite: ^2.3.0
  cached_network_image: ^3.3.0
  shimmer: ^3.0.0
  firebase_core: ^2.27.0
  firebase_messaging: ^14.7.19
  flutter_local_notifications: ^17.0.0
  google_fonts: ^6.1.0
  intl: ^0.19.0
  lottie: ^3.0.0
```

### Hari 3: Core Layer

**Yang dikerjain:**
1. `lib/core/theme/app_theme.dart` — ThemeData, colors, fonts
2. `lib/core/constants/api_constants.dart` — base URL, endpoints
3. `lib/core/network/dio_client.dart` — Dio instance + auth interceptor
4. `lib/core/storage/local_storage.dart` — token + cache
5. `lib/core/widgets/` — shimmer, error, empty state, scaffold
6. `lib/ui/router/app_router.dart` — GoRouter + route list
7. `lib/main.dart` — ProviderScope + MaterialApp.router

**Hasil:**
```
flutter run
→ Layar putih dengan router siap
→ Token bisa disimpan/dibaca
→ Dio bisa request API
```

---

## Fase 2: Public Module — Event List + Detail (4 hari)

### Hari 4-5: Data Layer + Models

**Yang dikerjain:**
1. `lib/data/models/event_model.dart` — fromJson, toJson
2. `lib/data/models/category_model.dart`
3. `lib/data/models/participant_model.dart`
4. `lib/data/datasources/remote/public_api.dart` — HTTP calls
5. `lib/data/repositories/event_repository.dart`

### Hari 6-7: UI Screens

**Yang dikerjain:**
1. `HomeScreen` — grid event cards, pull refresh, shimmer loading
   - State: loading → shimmer / data → grid / error → retry / empty → ilustrasi
2. `EventDetailScreen` — banner, info event, kategori list
3. `ParticipantListScreen` — per kategori, search filter
4. `ScoreboardScreen` — ranking per kategori (kalo udah rilis)
5. `ChampionsScreen` — juara + trophy (kalo udah rilis)

**Hasil:**
```
Buka app → lihat daftar event
Tap event → lihat detail + kategori
Tap kategori → lihat peserta
Tap scoreboard → lihat ranking
```

---

## Fase 3: Vote Flow + QRIS Payment (5 hari)

### Hari 8-9: Vote API + Provider

**Yang dikerjain:**
1. `lib/data/models/vote_model.dart`
2. VoteRepository + VoteProvider
3. POST `/api/v1/vote/calculate` — generate QRIS
4. GET `/api/v1/vote/status/{id}` — polling

### Hari 10-12: UI Vote

**Yang dikerjain:**
1. `VoteScreen` — multi-step form:
   - Step 1: Pilih kategori lomba
   - Step 2: Pilih peserta (card + nama + total vote)
   - Step 3: Form pemilih (nama, email, comment opsional)
   - Step 4: Pilih jumlah vote (+/-)
   - Step 5: Bayar — QRIS image + countdown + polling status
   - Step 6: Sukses — animation + total votes earned

2. `VotePaymentScreen`:
   - Tampilkan QR image dari AutoGoPay
   - Countdown timer (expiry)
   - Polling tiap 5 detik cek status
   - Sukses → animasi + redirect
   - Gagal/expired → error + retry

**State:**
```dart
sealed class VoteState {
  object SelectingCategory : VoteState
  object SelectingParticipant : VoteState
  object FillingForm : VoteState
  data class Payment(val qrUrl: String, val expiry: DateTime, val amount: int) : VoteState
  object Success : VoteState
  data class Error(val message: String) : VoteState
}
```

**Hasil:**
```
Event detail → tap Vote
→ Pilih kategori → Pilih peserta
→ Isi nama email → Pilih jumlah vote
→ QRIS muncul → scan QR → tunggu bayar
→ Sukses!
```

---

## Fase 4: QR Scanner + Auth Portal (4 hari)

### Hari 13: Backend QR Controller

**Yang dikerjain:**
1. `QrController@scan` — validasi qr_token, return Sanctum token + data registration
2. `RegistrationResource` — format response

### Hari 14-16: QR Scanner Flutter

**Yang dikerjain:**
1. `QrScanScreen`:
   - Camera preview full screen
   - `mobile_scanner` deteksi QR
   - Overlay frame scan
   - Loading state pas parsing
   - Error state: "QR tidak valid"
   
2. `AuthProvider` — manage token state:
   ```
   Idle → Scan → Loading → Success (token saved) → Navigate portal
                         → Error → Scan lagi
   ```

3. `PortalDashboardScreen`:
   - Header: nama event, nama sekolah, logo
   - Status berkas: booking/confirmed/Terverifikasi (dengan color coding)
   - Card ringkasan: jumlah peserta, status pembayaran
   - Quick actions: Upload / Nilai / Ranking / Tiket
   - Tombol: Logout (hapus token → back to home)

**State portal:**
```dart
sealed class PortalState {
  object Loading : PortalState
  object NoSession : PortalState  // belum scan QR, perlu scan
  data class Active(val data: Registration) : PortalState
  data class Error(val message: String) : PortalState
}
```

**Hasil:**
```
Home → tap ikon QR → buka kamera
Scan QR → loading → masuk portal dashboard
Lihat status, tombol menu
```

---

## Fase 5: Upload Berkas — Camera + File (4 hari)

### Hari 17-18: Backend Upload Controller

**Yang dikerjain:**
1. `UploadController` — 6 method upload:
   - `logo()` → storage/registrations/logos
   - `participantPhoto()` → storage/registrations/peserta
   - `suratTugas()` → storage/registrations/surat
   - `pelatih()` → storage/registrations/pelatih
   - `danton()` → storage/registrations/danton
   - `paymentProof()` → storage/registrations/payment
2. Validasi: image max 3MB, PDF max 5MB
3. Return path + URL

### Hari 19-20: Upload Screen

**Yang dikerjain:**
1. `UploadScreen` — list semua yang perlu diupload:
   ```
   📋 Daftar Berkas
   ┌─────────────────────────────────┐
   │ 📸 Logo Sekolah        ✅ Ada   │
   │ 📄 Surat Tugas         ❌ Kosong│
   │ 👤 Foto Pelatih        ✅ Ada   │
   │ 👤 Foto Danton         ❌ Kosong│
   │ 👥 Foto Peserta (12)  3/12      │
   │ 🧾 Bukti Bayar         ❌ Kosong│
   └─────────────────────────────────┘
   ```
   - Tap item → show bottom sheet: Camera / Galeri
   - Foto langsung dari kamera → kompres → upload
   - Progress indicator pas upload
   - Snackbar sukses/gagal

2. `PhotoPickerTile` — reusable widget:
   - Ikon status (hijau = sudah, abu = belum)
   - Preview foto kalo sudah ada
   - Tap untuk ganti foto

3. Upload flow peserta:
   ```
   Tap "Foto Peserta"
   → Kamera buka (tanpa konfirmasi gallery)
   → Foto peserta 1 → simpan → upload
   → Langsung ke peserta 2 → kamera lagi
   → ... sampai semua
   ```

**Hasil:**
```
Portal → tap Upload
→ Tap item → camera/galeri → ambil foto
→ Loading upload → sukses → ikon berubah hijau
```

---

## Fase 6: Progress Nilai + Ranking + Tiket (3 hari)

### Hari 21: Backend Score + Ticket

**Yang dikerjain:**
1. `PortalController@scores` — query AssessmentScore
   - Join dengan AssessmentCriteria, SubCategory, Category
   - Hitung total per kategori
   - Return: kategori + sub + kriteria + skor + maks

2. `PortalController@ranking` — hitung posisi
   ```sql
   SELECT registration_id, SUM(score) as total
   FROM assessment_scores
   WHERE is_finalized = true
     AND registration_id IN (
       SELECT id FROM registrations
       WHERE competition_category_id = ?
     )
   GROUP BY registration_id
   ORDER BY total DESC
   ```
   - Bandingkan dengan registration_id user → dapat ranking

3. `PortalController@ticket` — data tiket + generate QR

### Hari 22: Score + Ranking Screen

**Yang dikerjain:**
1. `ScoreScreen`:
   ```
   ┌─────────────────────────────────┐
   │      Total Skor                 │
   │       875 / 1000                │
   │     ████████████░░ 87.5%        │
   ├─────────────────────────────────┤
   │ ▶ Kekompakan (350/400)   87.5%  │
   │   ▸ Gerakan Dasar               │
   │     Keseragaman langkah  95/100 │
   │     Ketepatan balik      85/100 │
   │   ▸ Formasi                     │
   │     Kerapihan barisan    90/100 │
   │     Perpindahan           80/100 │
   │ ▶ Keluwesan (280/300)    93.3%  │
   └─────────────────────────────────┘
   ```
   - ExpansionTile per kategori
   - Progress bar visual
   - Kalo belum dinilai semua: tampilkan "Menunggu penilaian juri..."

2. `RankingScreen`:
   ```
   Peringkat Kamu: #4 dari 20 peserta
   
   ┌─────────────────────────────────┐
   │ 🥇 SMA N 1 Bandung      950     │
   │ 🥈 MAN 2 Bogor          920     │
   │ 🥉 SMK 3 Jakarta        890     │
   │ 👉 SMA N 5 Bandung      875 ← kamu │
   │    SMA N 2 Jakarta      860     │
   └─────────────────────────────────┘
   ```
   - Highlight baris user dengan warna beda

### Hari 23: Ticket Screen

**Yang dikerjain:**
1. `TicketScreen`:
   ```
   ┌──────────────────────────────────┐
   │       TIKET PESERTA              │
   │                                  │
   │   ┌──────────────────────┐       │
   │   │     ██████████████    │       │
   │   │     ██ QR CODE ██    │       │
   │   │     ██████████████    │       │
   │   └──────────────────────┘       │
   │                                  │
   │   SMA N 1 Bandung                │
   │   Lomba PBB Putra                │
   │   15 Agustus 2026                │
   │   Venue: GOR Padjajaran          │
   └──────────────────────────────────┘
   ```
   - Generate QR dari magic_token / ticket token
   - Countdown kalo event belum mulai
   - Status: active / expired

---

## Fase 7: Push Notification + Polish + Release (3 hari)

### Hari 24: Firebase + FCM

**Yang dikerjain:**
1. Setup Firebase Console — download google-services.json, GoogleService-Info.plist
2. `firebase_core.initializeApp()` di main.dart
3. `FirebaseMessaging` — dapatkan FCM token
4. Kirim FCM token ke backend (simpan di tabel registrations / devices)
5. Handle notif: popup / navigasi ke screen tertentu

**Notifikasi yang dikirim backend (via panitia):**
- "Berkas kamu sudah diverifikasi ✅"
- "Pembayaran sudah dikonfirmasi"
- "Nilai sudah dirilis, cek sekarang!"
- "Pengumuman juara 🏆"

### Hari 25: Deep Link + Polish

**Yang dikerjain:**
1. Deep link — QR scan langsung buka app
   - Android: intent filter `https://berbaris.com/qr/*`
   - iOS: universal link
   - Flutter: GoRouter handle redirect ke `/scan`

2. Polish UI:
   - Animasi transisi
   - Shimmer loading untuk semua screen
   - Pull refresh semua list
   - Infinite scroll untuk event list (kalo banyak)
   - Offline indicator (koneksi putus)
   - Dark mode? (opsional)

3. Error handling lengkap:
   - Network error → "Tidak ada koneksi internet"
   - 401 → token expired → redirect scan QR
   - 404 → "Data tidak ditemukan"
   - 500 → "Terjadi kesalahan server"

### Hari 26: Testing

**Yang dikerjain:**
1. Unit test: model fromJson/toJson
2. Widget test: semua screen
3. Integration test: flow vote, flow QR scan
4. Manual test di 3 device berbeda
5. Test upload foto besar (5MB)
6. Test QR scan dalam kondisi cahaya redup

### Hari 27: Build + Release

**Yang dikerjain:**
```bash
# Android
flutter build apk --release
flutter build appbundle --release

# iOS
flutter build ios --release
```

1. **Android:**
   - Generate keystore (jika belum)
   - Update `android/app/build.gradle` — version, signing config
   - Build APK untuk distribusi manual
   - Build AAB untuk Play Store

2. **Play Console:**
   - Buat listing
   - Upload AAB
   - Signed APK
   - Submit review (3-7 hari)

3. **Distribusi offline (event):**
   - APK bisa di-sideload
   - QR langsung buka link download

---

## Ringkasan Timeline

| Fase | Hari ke | Fitur |
|---|---|---|
| **1. Setup** | 1-3 | Backend API + Flutter project + core |
| **2. Public** | 4-7 | Home, event detail, peserta, scoreboard |
| **3. Vote** | 8-12 | Vote form, QRIS payment, polling |
| **4. QR + Auth** | 13-16 | QR scanner, token, portal dashboard |
| **5. Upload** | 17-20 | Camera, file picker, multipart upload |
| **6. Nilai** | 21-23 | Score per kategori, ranking, tiket |
| **7. Release** | 24-27 | FCM, deep link, testing, Play Store |

**Total: ~27 hari kerja (1 dev fulltime)**
