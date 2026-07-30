# Arsitektur Aplikasi — Berbaris Peserta (Flutter)

## 1. Gambaran Besar

**2 Mode:**

| Mode | Akses | Auth | Fitur |
|---|---|---|---|
| **Public** | Buka app langsung | Tanpa login | Lihat event, vote, scoreboard, champions |
| **Private** | Scan QR di ruang peserta | QR token | Upload berkas, cek nilai, tiket digital |

```
┌─────────────────────────────────────────────────────────┐
│                   Flutter App (1 codebase)              │
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
│              │  Dio / HTTP    │                          │
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

## 2. API Layer — Sama, Nggak Berubah

API endpoint **sama persis** kayak desain sebelumnya. Baca `android-architecture.md` bagian API.

| Endpoint | Method | Auth |
|---|---|---|
| `GET /api/v1/events` | Public | - |
| `GET /api/v1/events/{slug}` | Public | - |
| `GET /api/v1/events/{slug}/participants?category_id=X` | Public | - |
| `GET /api/v1/events/{slug}/scoreboard` | Public | - |
| `GET /api/v1/events/{slug}/champions` | Public | - |
| `POST /api/v1/vote/calculate` | Public | - |
| `GET /api/v1/vote/status/{id}` | Public | - |
| `POST /api/v1/qr/scan` | Public (QR) | Return token |
| `GET /api/v1/portal/...` | Private | Bearer token |
| `POST /api/v1/portal/upload/...` | Private | Bearer token + multipart |

---

## 3. Arsitektur Flutter

```
berbaris_app/
│
├── lib/
│   ├── main.dart                        ← Entry point + MaterialApp
│   ├── app.dart                         ← App widget + router + theme
│   │
│   ├── core/                            ← Shared utilities
│   │   ├── constants/
│   │   │   ├── api_constants.dart       ← Base URL, endpoints
│   │   │   └── app_constants.dart       ← App name, version
│   │   ├── theme/
│   │   │   ├── app_theme.dart           ← ThemeData, colors, fonts
│   │   │   └── app_colors.dart
│   │   ├── network/
│   │   │   ├── dio_client.dart          ← Dio instance + interceptors
│   │   │   ├── auth_interceptor.dart    ← Inject Bearer token
│   │   │   └── api_response.dart        ← Wrapper response
│   │   ├── storage/
│   │   │   └── local_storage.dart       ← SharedPreferences / flutter_secure_storage
│   │   ├── utils/
│   │   │   ├── image_utils.dart         ← Kompres, crop
│   │   │   └── validators.dart
│   │   └── widgets/
│   │       ├── loading_shimmer.dart
│   │       ├── error_screen.dart
│   │       ├── empty_state.dart
│   │       └── app_scaffold.dart
│   │
│   ├── data/                            ← Data Layer
│   │   ├── models/                      ← Data classes (fromJson/toJson)
│   │   │   ├── event_model.dart
│   │   │   ├── category_model.dart
│   │   │   ├── participant_model.dart
│   │   │   ├── registration_model.dart
│   │   │   ├── score_model.dart
│   │   │   ├── vote_model.dart
│   │   │   ├── ranking_model.dart
│   │   │   ├── champion_model.dart
│   │   │   └── ticket_model.dart
│   │   ├── repositories/               ← Business logic & data source
│   │   │   ├── event_repository.dart
│   │   │   ├── vote_repository.dart
│   │   │   ├── qr_repository.dart
│   │   │   ├── portal_repository.dart
│   │   │   └── upload_repository.dart
│   │   └── datasources/
│   │       ├── remote/
│   │       │   ├── public_api.dart      ← Public HTTP calls
│   │       │   ├── portal_api.dart      ← Private HTTP calls (auth)
│   │       │   └── upload_api.dart      ← Multipart upload calls
│   │       └── local/
│   │           └── app_database.dart    ← SQLite (sqflite/drift)
│   │
│   ├── providers/                       ← State management (Riverpod)
│   │   ├── public/
│   │   │   ├── event_provider.dart
│   │   │   ├── vote_provider.dart
│   │   │   └── scoreboard_provider.dart
│   │   └── portal/
│   │       ├── auth_provider.dart       ← QR scan → token state
│   │       ├── portal_provider.dart
│   │       └── upload_provider.dart
│   │
│   └── ui/                              ← Presentation Layer
│       ├── router/
│       │   ├── app_router.dart          ← GoRouter config
│       │   └── routes.dart              ← Route definitions
│       │
│       ├── public/                      ← PUBLIC MODE screens
│       │   ├── home/
│       │   │   ├── home_screen.dart
│       │   │   └── widgets/
│       │   │       ├── event_card.dart
│       │   │       └── search_bar.dart
│       │   ├── event-detail/
│       │   │   ├── event_detail_screen.dart
│       │   │   └── widgets/
│       │   │       ├── event_info.dart
│       │   │       └── category_list.dart
│       │   ├── vote/
│       │   │   ├── vote_screen.dart
│       │   │   ├── vote_payment_screen.dart
│       │   │   └── widgets/
│       │   │       ├── vote_form.dart
│       │   │       └── qris_display.dart
│       │   ├── scoreboard/
│       │   │   ├── scoreboard_screen.dart
│       │   │   └── widgets/
│       │   │       └── ranking_list.dart
│       │   └── champions/
│       │       ├── champions_screen.dart
│       │       └── winners_grid.dart
│       │
│       ├── qr/                          ← QR Scanner
│       │   ├── qr_scan_screen.dart
│       │   └── widgets/
│       │       └── scan_overlay.dart
│       │
│       └── portal/                      ← PRIVATE MODE screens
│           ├── dashboard/
│           │   ├── portal_screen.dart
│           │   └── widgets/
│           │       ├── status_card.dart
│           │       └── quick_actions.dart
│           ├── upload/
│           │   ├── upload_screen.dart
│           │   └── widgets/
│           │       ├── photo_picker_tile.dart
│           │       └── file_picker_tile.dart
│           ├── scores/
│           │   ├── score_screen.dart
│           │   └── widgets/
│           │       ├── progress_chart.dart
│           │       └── criteria_detail.dart
│           ├── ranking/
│           │   ├── ranking_screen.dart
│           │   └── widgets/
│           │       └── rank_badge.dart
│           └── ticket/
│               ├── ticket_screen.dart
│               └── widgets/
│                   └── ticket_card.dart
│
├── assets/
│   ├── images/
│   │   ├── logo.png
│   │   ├── empty_state.svg
│   │   └── scan_frame.png
│   └── fonts/
│       └── ...
│
├── test/
│   ├── unit/
│   ├── widget/
│   └── integration/
│
├── android/                          ← Android native config
├── ios/                              ← iOS native config
├── pubspec.yaml
└── README.md
```

---

## 4. State Management — Riverpod

```dart
// Setiap screen pake AsyncValue built-in Riverpod

// ─── Provider ───
final eventListProvider = FutureProvider.autoDispose((ref) {
  return ref.read(eventRepositoryProvider).getEvents();
});

final voteStateProvider = StateNotifierProvider<VoteNotifier, VoteState>((ref) {
  return VoteNotifier(ref.read(voteRepositoryProvider));
});

// ─── Screen ───
class HomeScreen extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final eventsAsync = ref.watch(eventListProvider);

    return eventsAsync.when(
      loading: () => ShimmerList(),
      error: (err, stack) => ErrorScreen(err.toString()),
      data: (events) => EventList(events),
    );
  }
}
```

**Kenapa Riverpod?**
- Compile-safe, no runtime errors
- `AsyncValue.when()` — loading/error/data built-in
- Auto-dispose — cleanup otomatis
- Testable — override provider

---

## 5. State Pattern Tiap Screen

```
┌─────────────┐
│ App Startup │
└──────┬──────┘
       │
┌──────▼──────┐     ┌──────────┐
│  Loading    │ ──→ │ Shimmer  │
└──────┬──────┘     │ skeleton │
       │            └──────────┘
  ┌────┴────┐
  │         │
  ▼         ▼
┌──────┐ ┌───────┐
│ Data  │ │ Error │
└──┬───┘ └───┬───┘
   │         │
   │    ┌────▼────┐
   │    │ Retry?  │
   │    └────┬────┘
   │         │
   └────┬────┘
        ▼
  ┌──────────┐
  │ Refetch  │
  └──────────┘
```

**State tiap screen wajib handle:**
- **Loading** — shimmer, skeleton
- **Data** — konten
- **Empty** — ilustrasi + "Belum ada data"
- **Error** — pesan error + tombol retry
- **Kosong (belum rilis)** — scoreboard/champions belum publikasi

---

## 6. Navigation — GoRouter

```dart
final appRouter = GoRouter(
  initialLocation: '/',
  routes: [
    GoRoute(path: '/', builder: (_, __) => HomeScreen()),
    GoRoute(path: '/event/:slug', builder: (_, state) =>
      EventDetailScreen(slug: state.pathParameters['slug']!)),
    GoRoute(path: '/event/:slug/vote', builder: (_, state) =>
      VoteScreen(slug: state.pathParameters['slug']!)),
    GoRoute(path: '/event/:slug/vote/payment', builder: (_, state) =>
      VotePaymentScreen(slug: state.pathParameters['slug']!)),
    GoRoute(path: '/event/:slug/scoreboard', builder: (_, state) =>
      ScoreboardScreen(slug: state.pathParameters['slug']!)),
    GoRoute(path: '/event/:slug/champions', builder: (_, state) =>
      ChampionsScreen(slug: state.pathParameters['slug']!)),
    GoRoute(path: '/scan', builder: (_, __) => QrScanScreen()),
    GoRoute(path: '/portal', builder: (_, __) => PortalScreen()),
    GoRoute(path: '/portal/upload', builder: (_, __) => UploadScreen()),
    GoRoute(path: '/portal/scores', builder: (_, __) => ScoreScreen()),
    GoRoute(path: '/portal/ranking', builder: (_, __) => RankingScreen()),
    GoRoute(path: '/portal/ticket', builder: (_, __) => TicketScreen()),
  ],
);

// Deep link — tangkap QR dari URL
// https://berbaris.com/qr/ABC123DEF
GoRouter(
  initialLocation: '/scan',
  routes: [...],
);
```

---

## 7. QR Flow

```
User tap "Scan QR" ───→ QrScanScreen
                            │
                       CameraX preview
                            │
                    [mobile_scanner] detect QR
                            │
                    Parse qr_token dari QR
                            │
                    POST /api/v1/qr/scan
                     body: {qr_token}
                            │
                    ┌───────┴────────┐
                    ▼                ▼
                Success           Error
                    │                │
            Simpan token di     "QR tidak valid"
            flutter_secure_storage ── retry
                    │
            Navigate ke PortalScreen
```

**Flutter packages:**
- `mobile_scanner` — scan QR realtime (CameraX + ML Kit)
- `qr_flutter` — generate QR (buat tiket)

---

## 8. Auth & Token Management

```dart
// ─── Simpan token setelah QR scan ───
class LocalStorage {
  static const _tokenKey = 'sanctum_token';
  static const _qrTokenKey = 'qr_token';
  static const _regIdKey = 'registration_id';

  Future<void> saveToken(String token) async {
    await FlutterSecureStorage().write(key: _tokenKey, value: token);
  }

  Future<String?> getToken() async {
    return await FlutterSecureStorage().read(key: _tokenKey);
  }

  Future<void> clearSession() async {
    await FlutterSecureStorage().deleteAll();
  }
}

// ─── Dio interceptor ───
class AuthInterceptor extends Interceptor {
  final LocalStorage _storage;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) async {
    final token = await _storage.getToken();
    if (token != null) {
      options.headers['Authorization'] = 'Bearer $token';
    }
    handler.next(options);
  }
}
```

**Flow:**
1. Scan QR → dapat Sanctum token
2. Simpan di FlutterSecureStorage
3. Setiap request portal pake Dio interceptor inject Bearer
4. Logout → hapus token → kembali ke home
5. Token persist — buka app besok masih login (cek expiry)

---

## 9. Upload Berkas — Camera + File

```dart
// ─── Pilih sumber ───
Future<void> pickPhoto(UploadType type) async {
  final source = await showModalBottomSheet(
    context: context,
    builder: (_) => Column(
      children: [
        ListTile(leading: Icon(Icons.camera_alt),
          title: Text('Ambil Foto'),
          onTap: () => Navigator.pop(context, ImageSource.camera)),
        ListTile(leading: Icon(Icons.photo_library),
          title: Text('Dari Galeri'),
          onTap: () => Navigator.pop(context, ImageSource.gallery)),
      ],
    ),
  );

  final photo = await ImagePicker().pickImage(source: source, imageQuality: 70);
  if (photo != null) {
    final compressed = await compressImage(photo.path);
    await uploadFile(compressed, type);
  }
}

// ─── Upload ───
Future<void> uploadFile(String filePath, UploadType type) async {
  final formData = FormData.fromMap({
    'file': await MultipartFile.fromFile(filePath,
      filename: '${type.name}_${DateTime.now().millisecondsSinceEpoch}.jpg'),
  });
  await dio.post('/api/v1/portal/upload/${type.endpoint}', data: formData);
}
```

**Jenis upload per screen:**
| Screen | Upload | Widget Picker |
|---|---|---|
| Upload Berkas | Logo sekolah | Galeri |
| Upload Berkas | Surat tugas | File manager (PDF) |
| Upload Berkas | Foto pelatih | Camera/Galeri |
| Upload Berkas | Foto danton | Camera/Galeri |
| Upload Peserta | Foto tiap anggota x12 | Camera (berurutan) |
| Pembayaran | Bukti bayar | Camera/Galeri |

---

## 10. Progress Nilai — Layar Detail

```dart
class ScoreScreen extends ConsumerWidget {
  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final scoresAsync = ref.watch(scoresProvider);

    return scoresAsync.when(
      loading: () => ShimmerList(),
      error: (e, _) => ErrorScreen(e.toString()),
      data: (scores) => Column(
        children: [
          // Ringkasan total
          TotalScoreCard(
            score: scores.totalSkor,
            maxScore: scores.maksSkor,
            percentage: scores.persentase,
          ),
          // Per kategori — expandable
          ExpansionPanelList(
            children: scores.categories.map((cat) =>
              ExpansionPanel(
                header: CategoryHeader(cat.nama, cat.skor, cat.maks),
                body: Column(
                  children: cat.subCategories.map((sub) =>
                    SubCategoryCard(
                      sub.nama,
                      sub.criterias,
                    ),
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
```

**Cek "Belum Dinilai":**
```dart
if (scores.categories.every((c) => c.skor == 0)) {
  return EmptyState(
    icon: Icons.rate_review_outlined,
    title: 'Belum Dinilai',
    subtitle: 'Nilai akan muncul setelah juri selesai menilai',
  );
}
```

---

## 11. Fitur Offline (Opsional)

| Data | Simpan | Kapan Sinkron |
|---|---|---|
| Daftar event | SQLite (sqflite) | Setiap buka app + pull refresh |
| Token login | FlutterSecureStorage | Permanen sampai logout |
| Draft upload | File lokal temp | Upload manual / WorkManager |

---

## 12. Navigation Flow

```
/ (Home)
 ├── /event/{slug} (Event Detail)
 │    ├── Vote Flow (bottom sheet)
 │    │    └── /event/{slug}/vote/payment (QRIS)
 │    ├── /event/{slug}/scoreboard
 │    └── /event/{slug}/champions
 │
 └── [Scan QR] → /scan
      └── Success → /portal (Dashboard)
           ├── /portal/upload
           ├── /portal/scores
           ├── /portal/ranking
           └── /portal/ticket
```

---

## 13. Packages Flutter

| Kebutuhan | Package |
|---|---|
| **State Management** | `flutter_riverpod` + `riverpod_annotation` |
| **Navigation** | `go_router` |
| **Network** | `dio` |
| **QR Scanner** | `mobile_scanner` |
| **QR Generate** | `qr_flutter` |
| **Camera/Galeri** | `image_picker` |
| **File picker** | `file_picker` (PDF) |
| **Compress gambar** | `flutter_image_compress` |
| **Secure Storage** | `flutter_secure_storage` |
| **Local DB** | `sqflite` (cache) |
| **Lottie** | `lottie` (animasi) |
| **Cached Network** | `cached_network_image` |
| **Pull Refresh** | `pull_to_refresh` (built-in) |
| **Shimmer** | `shimmer` |
| **Fluttertoast** | `fluttertoast` / `snackbar` |
| **FCM** | `firebase_messaging` |

---

## 14. Platform Target

| Platform | Target |
|---|---|
| **Android** | ✅ Primary — install APK, Play Store |
| **iOS** | ✅ Bonus — 1 codebase jalan |
| **Web** | ❌ Tidak — app ini fokus kamera + QR |

---

## 15. Timeline

| Fase | Fitur | Hari |
|---|---|---|
| **1** | Setup project + struktur + theme + router | 2 |
| **2** | API layer (Dio + model + repository) | 3 |
| **3** | Public: Home + Event Detail + Peserta | 4 |
| **4** | Vote flow + QRIS payment + polling | 5 |
| **5** | QR scan + auth + Portal dashboard | 4 |
| **6** | Upload berkas (camera + file + kompres) | 4 |
| **7** | Progress Nilai + Ranking | 3 |
| **8** | Tiket digital + push notif FCM | 3 |
| **9** | Polish, testing, release | 3 |
| **Total** | | **~31 hari** (1 dev) |
