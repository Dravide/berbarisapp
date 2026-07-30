# Arsitektur Android App — Berbaris Peserta

## 1. Gambaran Besar

**2 Mode:**

| Mode | Akses | Auth | Fitur |
|---|---|---|---|
| **Public** | Buka app langsung | Tanpa login | Lihat event, vote, scoreboard, champions |
| **Private** | Scan QR di ruang peserta | QR token | Upload berkas, cek nilai, tiket digital |

```
┌─────────────────────────────────────────────────────────┐
│                    Android App                          │
│  ┌────────────────────┐   ┌──────────────────────────┐  │
│  │   PUBLIC MODE      │   │   PRIVATE MODE (QR)      │  │
│  │  ────────────────  │   │  ──────────────────────  │  │
│  │  • Home Event List │   │  • Scan QR → Token       │  │
│  │  • Vote + QRIS     │   │  • Dashboard Sekolah     │  │
│  │  • Scoreboard      │   │  • Upload/Edit Data      │  │
│  │  • Champions       │   │  • Progress Nilai        │  │
│  │  • Daftar Peserta  │   │  • Status Berkas         │  │
│  │                    │   │  • Tiket Digital         │  │
│  └────────┬───────────┘   └────────┬─────────────────┘  │
│           │                        │                     │
│           └──────────┬─────────────┘                     │
│                      │                                   │
│              ┌───────▼────────┐                          │
│              │  NETWORK LAYER │                          │
│              │  Retrofit/OkHttp                          │
│              └───────┬────────┘                          │
└──────────────────────┼──────────────────────────────────┘
                       │ HTTPS / JSON
              ┌────────▼────────┐
              │  LARAVEL API    │
              │  (Sanctum Auth) │
              └────────┬────────┘
                       │
              ┌────────▼────────┐
              │  MySQL Database │
              └─────────────────┘
```

---

## 2. API Layer Baru

### 2.1 Public API — No Auth

```
GET    /api/v1/events
  └─ daftar event aktif + filter (kota, tanggal)
  └─ response: [{id, nama_event, poster, venue, tanggal, slug, logo_event, subdomain}]

GET    /api/v1/events/{slug}
  └─ detail event + kategori lomba
  └─ response: {nama_event, venue, tanggal, deskripsi, categories: [...]}

GET    /api/v1/events/{slug}/categories
  └─ daftar kategori lomba (parent + child)

GET    /api/v1/events/{slug}/participants?category_id=X
  └─ daftar peserta per kategori
  └─ response: [{registration_id, nama_sekolah, logo_sekolah, total_votes}]

GET    /api/v1/events/{slug}/scoreboard?category_id=X
  └─ peringkat, bisa public setelah difinalisasi

GET    /api/v1/events/{slug}/champions
  └─ juara tiap kategori

POST   /api/v1/vote/calculate
  └─ body: {event_slug, registration_id, vote_count, voter_name, voter_email, comment}
  └─ response: {amount, qr_url, expiry_time, transaction_id, autogopay_id}

GET    /api/v1/vote/status/{transaction_id}
  └─ polling status pembayaran vote

GET    /api/v1/vote/comments?event_slug=X
  └─ comments widget (floating comments vote)
```

### 2.2 Private API — Auth via QR Token

```
POST   /api/v1/qr/scan
  └─ body: {qr_token}
  └─ response: Sanctum token + data registration
  └─ backend: generate qr_token kolom baru di registrations

── Headers: Authorization: Bearer {sanctum_token} ──

GET    /api/v1/portal/registration
  └─ data pendaftaran + status

GET    /api/v1/portal/participants
  └─ daftar anggota pasukan

POST   /api/v1/portal/upload/logo
POST   /api/v1/portal/upload/participant-photo
POST   /api/v1/portal/upload/surat-tugas
POST   /api/v1/portal/upload/pelatih-foto
POST   /api/v1/portal/upload/danton-foto
POST   /api/v1/portal/upload/payment-proof
  └─ multipart: file → storage, return path

PUT    /api/v1/portal/registration
  └─ update data: nama_pelatih, danton_nama, danton_nisn

POST   /api/v1/portal/confirm
  └─ finalisasi data (is_finalized = true)

GET    /api/v1/portal/scores
  └─ progress nilai: total per kategori, detail per kriteria
  └─ response: {categories: [{nama, skor, maks}, ...], total_skor}

GET    /api/v1/portal/ranking
  └─ posisi ranking di kategorinya

GET    /api/v1/portal/ticket
  └─ tiket digital + QR check-in
```

### 2.3 Model Responses (Contoh JSON)

**Event List:**
```json
{
  "data": [
    {
      "id": 1,
      "nama_event": "Lomba PBB se-Jawa Barat 2026",
      "slug": "lomba-pbb-abc12",
      "poster": "/storage/events/poster.jpg",
      "venue": "GOR Padjajaran",
      "tanggal": "2026-08-15",
      "tanggal_akhir": "2026-08-17",
      "logo_event": "/storage/events/logo.jpg",
      "subdomain": "pbb2026",
      "lokasi": "Bandung"
    }
  ]
}
```

**Scoreboard:**
```json
{
  "data": [
    {"ranking": 1, "nama_sekolah": "SMA 1 Bandung", "total_score": 925.5},
    {"ranking": 2, "nama_sekolah": "MAN 2 Bogor", "total_score": 910.0},
    {"ranking": 3, "nama_sekolah": "SMA 5 Jakarta", "total_score": 895.0}
  ]
}
```

**Portal Scores (private):**
```json
{
  "data": {
    "total_skor": 875.0,
    "maks_skor": 1000.0,
    "categories": [
      {
        "nama": "Kekompakan",
        "skor": 350.0,
        "maks": 400.0,
        "persentase": 87.5,
        "sub_categories": [
          {
            "nama": "Gerakan Dasar",
            "criterias": [
              {"nama": "Keseragaman langkah", "skor": 90, "maks": 100},
              {"nama": "Ketepatan balik", "skor": 85, "maks": 100}
            ]
          }
        ]
      }
    ]
  }
}
```

---

## 3. Arsitektur Android App

```
com.berbaris.app/
│
├── BerbarisApp.kt                 ← Application class
├── MainActivity.kt                ← Single activity
│
├── di/                            ← Dependency Injection
│   ├── AppModule.kt               ← Hilt/koin modules
│   ├── NetworkModule.kt
│   └── RepositoryModule.kt
│
├── data/                          ← Data Layer
│   ├── remote/
│   │   ├── api/
│   │   │   ├── PublicApi.kt       ← Retrofit interface (public)
│   │   │   ├── PortalApi.kt       ← Retrofit interface (private)
│   │   │   └── AuthInterceptor.kt ← inject token header
│   │   ├── dto/                   ← Response DTOs
│   │   │   ├── EventDto.kt
│   │   │   ├── ParticipantDto.kt
│   │   │   ├── ScoreDto.kt
│   │   │   ├── VoteDto.kt
│   │   │   └── ...
│   │   └── api/
│   │       └── ApiClient.kt       ← Retrofit builder
│   │
│   ├── local/
│   │   ├── BerbarisDatabase.kt    ← Room database
│   │   ├── dao/
│   │   │   ├── EventDao.kt
│   │   │   ├── RegistrationDao.kt
│   │   │   └── ...
│   │   ├── entity/
│   │   │   ├── EventEntity.kt     ← Room entity
│   │   │   ├── RegistrationEntity.kt
│   │   │   └── ...
│   │   └── datastore/
│   │       └── TokenStore.kt      ← DataStore untuk token
│   │
│   └── repository/
│       ├── EventRepository.kt
│       ├── VoteRepository.kt
│       ├── PortalRepository.kt
│       └── UploadRepository.kt
│
├── domain/                        ← Domain Layer
│   ├── model/
│   │   ├── Event.kt
│   │   ├── Participant.kt
│   │   ├── Score.kt
│   │   ├── Registration.kt
│   │   └── ...
│   └── usecase/
│       ├── GetEventsUseCase.kt
│       ├── SubmitVoteUseCase.kt
│       ├── ScanQrUseCase.kt
│       ├── UploadFileUseCase.kt
│       └── ...
│
├── ui/                            ← Presentation Layer
│   ├── navigation/
│   │   ├── NavGraph.kt            ← Jetpack Navigation
│   │   └── Routes.kt              ← Sealed class routes
│   │
│   ├── theme/
│   │   ├── Theme.kt
│   │   ├── Color.kt
│   │   └── Type.kt
│   │
│   ├── public/                    ← PUBLIC MODE screens
│   │   ├── home/
│   │   │   ├── HomeScreen.kt
│   │   │   └── HomeViewModel.kt
│   │   ├── event-detail/
│   │   │   ├── EventDetailScreen.kt
│   │   │   └── EventDetailViewModel.kt
│   │   ├── vote/
│   │   │   ├── VoteScreen.kt
│   │   │   ├── VotePaymentScreen.kt
│   │   │   └── VoteViewModel.kt
│   │   ├── scoreboard/
│   │   │   ├── ScoreboardScreen.kt
│   │   │   └── ScoreboardViewModel.kt
│   │   └── champions/
│   │       ├── ChampionsScreen.kt
│   │       └── ChampionsViewModel.kt
│   │
│   ├── qr/                        ← QR Scanner
│   │   ├── QrScanScreen.kt
│   │   └── QrScanViewModel.kt
│   │
│   └── portal/                    ← PRIVATE MODE screens
│       ├── dashboard/
│       │   ├── PortalDashboardScreen.kt
│       │   └── PortalViewModel.kt
│       ├── upload/
│       │   ├── UploadBerkasScreen.kt
│       │   ├── UploadPhotoScreen.kt
│       │   └── UploadViewModel.kt
│       ├── scores/
│       │   ├── ScoreProgressScreen.kt
│       │   └── ScoreViewModel.kt
│       ├── ranking/
│       │   ├── RankingScreen.kt
│       │   └── RankingViewModel.kt
│       └── ticket/
│           ├── TicketScreen.kt
│           └── TicketViewModel.kt
│
├── util/
│   ├── CameraUtils.kt
│   ├── FileCompressor.kt
│   ├── QrDecoder.kt
│   ├── NetworkMonitor.kt
│   └── Extensions.kt
│
└── worker/
    ├── VotePollWorker.kt          ← Polling status pembayaran
    └── SyncWorker.kt              ← Sinkronisasi offline
```

---

## 4. Navigation / Screen Flow

```
[Splash]
   │
   ├── [Home — Event List] ◄────────────────────────────┐
   │       │                                             │
   │       ├── Event Detail                              │
   │       │    ├── Kategori Lomba                       │
   │       │    ├── Daftar Peserta                       │
   │       │    ├── Vote Flow                            │
   │       │    │    ├── Pilih Kategori                   │
   │       │    │    ├── Pilih Peserta                    │
   │       │    │    ├── Isi Data Pemilih                 │
   │       │    │    ├── Jumlah Vote                     │
   │       │    │    ├── Bayar QRIS                      │
   │       │    │    └── Sukses                           │
   │       │    ├── Scoreboard                           │
   │       │    └── Champions                            │
   │       │                                             │
   │       └── [Icon Scan QR] ─────────────────────────┐  │
   │                                                    │  │
   └────────────────────────────────────────────────────┤  │
                                                        │  │
[QR Scanner] ◄──────────────────────────────────────────┘  │
   │                                                       │
   ├── [Portal Dashboard Sekolah]                          │
   │    ├── Status Berkas (booking/confirmed/verif)        │
   │    ├── Upload/Edit Data  ──────────────── kembali ────┘
   │    │    ├── Foto Peserta (kamera langsung)
   │    │    ├── Foto Pelatih
   │    │    ├── Foto Danton
   │    │    ├── Logo Sekolah
   │    │    ├── Surat Tugas (PDF/Image)
   │    │    └── Bukti Bayar
   │    ├── Data Pasukan (edit nama, NISN)
   │    ├── Finalisasi Data
   │    ├── Progress Nilai
   │    │    └── Detail per kriteria
   │    ├── Ranking
   │    └── Tiket Digital
   │
   └── [Logout] → Hapus token → Home
```

---

## 5. QR Flow Detail

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│ Panitia  │  →  │ Cetak QR │  →  │ Tempel   │  →  │ Siswa    │
│ Generate │     │ per      │     │ di ruang  │     │ scan QR  │
│ QR token │     │ sekolah  │     │ peserta   │     │          │
└──────────┘     └──────────┘     └──────────┘     └────┬─────┘
                                                         │
                                                    ┌────▼─────┐
                                                    │ Android  │
                                                    │ parse QR │
                                                    │ {qr_tok} │
                                                    └────┬─────┘
                                                         │
                                                    ┌────▼─────┐
                                                    │ POST     │
                                                    │ /qr/scan │
                                                    │{qr_token}│
                                                    └────┬─────┘
                                                         │
                                              ┌──────────▼──────────┐
                                              │ Backend:            │
                                              │ 1. Cari Registration│
                                              │ 2. Generate token   │
                                              │ 3. Return reg data  │
                                              └──────────┬──────────┘
                                                         │
                                              ┌──────────▼──────────┐
                                              │ Android simpan      │
                                              │ token di DataStore  │
                                              │ → Buka Dashboard    │
                                              └─────────────────────┘
```

**Keamanan:**
- QR token = kolom baru `qr_token` (8 digit) di tabel `registrations`
- QR token berbeda dari `magic_token`
- Setelah scan, backend return **Sanctum Personal Access Token**
- Request selanjutnya pake Bearer token
- Token expire 30 hari / bisa di-revoke

---

## 6. Backend Changes Required

### 6.1 Migration
```php
// New column: registrations table
$table->string('qr_token', 8)->nullable()->unique()->after('magic_token');

// Auto-generate saat pendaftaran dibuat
Registration::creating(function ($model) {
    if (!$model->qr_token) {
        $model->qr_token = strtoupper(Str::random(8));
    }
});
```

### 6.2 New Routes
```php
// routes/api.php
Route::prefix('v1')->group(function () {

    // Public
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{slug}', [EventController::class, 'show']);
    Route::get('/events/{slug}/categories', [EventController::class, 'categories']);
    Route::get('/events/{slug}/participants', [ParticipantController::class, 'index']);
    Route::get('/events/{slug}/scoreboard', [ScoreboardController::class, 'index']);
    Route::get('/events/{slug}/champions', [ChampionController::class, 'index']);
    Route::post('/vote/calculate', [VoteController::class, 'calculate']);
    Route::get('/vote/status/{transaction_id}', [VoteController::class, 'status']);
    Route::get('/vote/comments', [VoteController::class, 'comments']);

    // QR scan → dapat token
    Route::post('/qr/scan', [QrController::class, 'scan']);

    // Portal — pake auth:sanctum
    Route::middleware('auth:sanctum')->prefix('portal')->group(function () {
        Route::get('/registration', [PortalController::class, 'registration']);
        Route::get('/participants', [PortalController::class, 'participants']);
        Route::put('/registration', [PortalController::class, 'update']);
        Route::post('/confirm', [PortalController::class, 'confirm']);

        // Upload
        Route::post('/upload/logo', [UploadController::class, 'logo']);
        Route::post('/upload/participant-photo', [UploadController::class, 'participantPhoto']);
        Route::post('/upload/surat-tugas', [UploadController::class, 'suratTugas']);
        Route::post('/upload/pelatih-foto', [UploadController::class, 'pelatih']);
        Route::post('/upload/danton-foto', [UploadController::class, 'danton']);
        Route::post('/upload/payment-proof', [UploadController::class, 'paymentProof']);

        // Scores
        Route::get('/scores', [PortalController::class, 'scores']);
        Route::get('/ranking', [PortalController::class, 'ranking']);
        Route::get('/ticket', [PortalController::class, 'ticket']);
    });
});
```

### 6.3 QrController::scan
```php
public function scan(Request $request)
{
    $reg = Registration::where('qr_token', $request->qr_token)->firstOrFail();
    
    // Buat sanctum token
    $token = $reg->createToken('qr-android')->plainTextToken;
    
    return response()->json([
        'token' => $token,
        'registration' => new RegistrationResource($reg->load([
            'eventner', 'competitionCategory', 'participants'
        ])),
    ]);
}
```

---

## 7. Tech Stack Android

| Layer | Library | Alasan |
|---|---|---|
| **Bahasa** | Kotlin | Modern, concise, first-class Android |
| **UI** | Jetpack Compose | Deklaratif, reactive, less boilerplate |
| **Navigation** | Navigation Compose | Type-safe routing |
| **DI** | Hilt | Standar Android DI, integrates with ViewModel |
| **Network** | Retrofit + OkHttp | Mature, interceptor untuk auth token |
| **JSON** | Kotlinx Serialization | Native Kotlin, no reflection |
| **Image** | Coil | Ringan, Compose-native, caching |
| **Camera** | CameraX + ML Kit Barcode | QR scanning realtime |
| **Local** | Room + DataStore | Offline cache + token storage |
| **Image picker** | ActivityResult Contracts | Standar Android API |
| **File compression** | Compressor | Kurangi ukuran foto sebelum upload |
| **FCM** | Firebase Cloud Messaging | Push notification |
| **Worker** | WorkManager | Background upload + polling vote |

---

## 8. Module Dependency

```
ui → viewmodel → usecase → repository → api/db
 │                                  │
 └────────── domain model ──────────┘
```

- **UI layer** pake `StateFlow` + `collectAsState()` di Compose
- **ViewModel** pegang `state` (sealed class: Loading/Success/Error)
- **Repository** gabung data dari remote + local cache
- **Offline**: Room cache untuk daftar event (biar tetep bisa lihat tanpa internet)
- **Upload**: WorkManager untuk upload berkas di background

---

## 9. State Management Pattern

```kotlin
// Setiap screen punya sealed class state
sealed class PortalState {
    object Loading : PortalState()
    data class Success(val data: Registration) : PortalState()
    data class Error(val message: String) : PortalState()
    object QrExpired : PortalState()
}

// ViewModel
class PortalViewModel @Inject constructor(
    private val scanQrUseCase: ScanQrUseCase
) : ViewModel() {
    private val _state = MutableStateFlow<PortalState>(PortalState.Loading)
    val state: StateFlow<PortalState> = _state.asStateFlow()

    fun scanQr(token: String) {
        viewModelScope.launch {
            _state.value = PortalState.Loading
            scanQrUseCase(token)
                .onSuccess { _state.value = PortalState.Success(it) }
                .onFailure { _state.value = PortalState.Error(it.message ?: "Gagal") }
        }
    }
}

// Screen
@Composable
fun PortalDashboard(state: PortalState, ...) {
    when (state) {
        is PortalState.Loading → ShimmerLoading()
        is PortalState.Success → DashboardContent(state.data)
        is PortalState.Error → ErrorScreen(state.message, onRetry)
        is PortalState.QrExpired → QrExpiredScreen()
    }
}
```

---

## 10. Fitur Layar Detail

### Public Mode

| Screen | Konten | State |
|---|---|---|
| **Home** | Grid/list event, pull-refresh, shimmer loading | Loading / Empty / Events / Error |
| **Event Detail** | Banner, info event, daftar kategori | Loading / Detail / Error |
| **Daftar Peserta** | Per kategori, card + logo + nama + total vote | Loading / List / Empty / Error |
| **Vote** | Pilih peserta → isi data → bayar QRIS → sukses | Multi-step form, Payment (QR image), Success |
| **Scoreboard** | Ranking per kategori | Loading / List / Empty / Kosong (belum rilis) |
| **Champions** | Juara + trophy per kategori | Loading / Juara / Empty / Error |

### Private Mode (Portal)

| Screen | Konten | State |
|---|---|---|
| **Dashboard** | Banner sekolah, status berkas, ringkasan | Loading / Data / Error |
| **Upload** | CameraX capture + preview + upload progress | Idle / Capturing / Uploading / Success / Error |
| **Progress Nilai** | Expandable list: kategori → sub → kriteria | Loading / Scores / Empty (blm dinilai) |
| **Ranking** | Posisi + perbandingan dengan peserta lain | Loading / Data / No-Rank |
| **Tiket** | Tiket card + QR check-in + countdown | Loading / Active / Expired |

---

## 11. Timeline Implementasi

| Fase | Fitur | Durasi |
|---|---|---|
| **1** | Project setup + API layer + Room | 3 hari |
| **2** | Public: Home + Event Detail + Peserta | 4 hari |
| **3** | Vote flow + QRIS payment + polling | 5 hari |
| **4** | QR scanner + Auth flow + Portal Dashboard | 4 hari |
| **5** | Upload berkas (kamera + kompres) | 4 hari |
| **6** | Progress Nilai + Ranking | 3 hari |
| **7** | Tiket digital + push notif | 3 hari |
| **8** | Polish, testing, release | 4 hari |
| **Total** | | **~30 hari** (1 dev) |
