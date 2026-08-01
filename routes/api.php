<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\TicketController;
use App\Http\Controllers\Api\V1\VoteController;
use App\Http\Controllers\Api\V1\QrController;
use App\Http\Controllers\Api\V1\PortalController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\ScoreboardController;
use App\Http\Controllers\Api\V1\ChampionController;
use App\Http\Controllers\Api\V1\EventContentController;

/*
|--------------------------------------------------------------------------
| API Routes — Berbaris Mobile App
|--------------------------------------------------------------------------
|
| Public endpoints: no auth required
| Private endpoints: require Sanctum token from QR scan
|
*/

Route::prefix('v1')->group(function () {

    // ─── Public: Event ──────────────────────────────────────
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{slug}', [EventController::class, 'show']);
    Route::get('/events/{slug}/categories', [EventController::class, 'categories']);
    Route::get('/events/{slug}/participants', [EventController::class, 'participants']);

    // ─── Public: Konten Event ────────────────────────────────
    Route::get('/events/{slug}/gallery', [EventContentController::class, 'gallery']);
    Route::get('/events/{slug}/faq', [EventContentController::class, 'faq']);
    Route::get('/events/{slug}/sponsors', [EventContentController::class, 'sponsors']);
    Route::get('/events/{slug}/tenants', [EventContentController::class, 'tenants']);
    Route::get('/events/{slug}/juknis', [EventContentController::class, 'juknis']);
    Route::get('/events/{slug}/drawing-results', [EventContentController::class, 'drawingResults']);

    // ─── Public: Vote ────────────────────────────────────────
    Route::post('/vote/calculate', [VoteController::class, 'calculate']);
    Route::get('/vote/status/{transactionId}', [VoteController::class, 'status']);
    Route::get('/vote/comments', [VoteController::class, 'comments']);

    // ─── Public: Ticket ───────────────────────────────────────
    Route::post('/ticket/purchase', [TicketController::class, 'purchase']);
    Route::get('/ticket/status/{orderCode}', [TicketController::class, 'status']);

    // ─── Public: Scoreboard & Champions ──────────────────────
    Route::get('/scoreboard/{scoringCode}', [ScoreboardController::class, 'index']);
    Route::get('/scoreboard/{scoringCode}/category/{categoryId}', [ScoreboardController::class, 'byCategory']);
    Route::get('/champions/{scoringCode}', [ChampionController::class, 'index']);

    // ─── QR Scan → Get Token ─────────────────────────────────
    Route::post('/qr/scan', [QrController::class, 'scan']);

    // ─── Private: Portal (Bearer token from QR scan) ──────────
    Route::prefix('portal')->group(function () {

        // Registration data
        Route::get('/registration', [PortalController::class, 'registration']);
        Route::put('/registration', [PortalController::class, 'update']);
        Route::post('/confirm', [PortalController::class, 'confirm']);
        Route::get('/participants', [PortalController::class, 'participants']);
        Route::put('/participants/{id}', [PortalController::class, 'updateParticipant']);

        // Device tokens (FCM)
        Route::post('/device-token', [PortalController::class, 'registerDeviceToken']);

        // Uploads
        Route::post('/upload/logo', [UploadController::class, 'logo']);
        Route::post('/upload/participant-photo', [UploadController::class, 'participantPhoto']);
        Route::post('/upload/surat-tugas', [UploadController::class, 'suratTugas']);
        Route::post('/upload/pelatih-foto', [UploadController::class, 'pelatih']);
        Route::post('/upload/danton-foto', [UploadController::class, 'danton']);
        Route::post('/upload/payment-proof', [UploadController::class, 'paymentProof']);

        // Scores & Ranking
        Route::get('/scores', [PortalController::class, 'scores']);
        Route::get('/ranking', [PortalController::class, 'ranking']);
        Route::get('/ticket', [PortalController::class, 'ticket']);
    });
});
