<?php

namespace App\Support;

use App\Models\ErrorLog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Mencatat error tak terduga ke tabel error_logs dengan kode publik
 * (ER-XXXXXX). Dipanggil dari $exceptions->report() di bootstrap/app.php.
 *
 * Kode diturunkan dari sidik jari (class+file+line): error yang sama
 * selalu memakai kode yang sama — baris lama dinaikkan `occurrences`
 * dan konteksnya (url/user/ip/trace) diperbarui. Error yang sudah
 * ditandai selesai tapi muncul lagi dibuka kembali otomatis.
 *
 * Aman rekursif: jika DB mati, fallback error_log() — jangan pernah lempar.
 */
class RecordsErrorReport
{
    public function record(Throwable $e): ?ErrorLog
    {
        if ($this->shouldSkip($e)) {
            return null;
        }

        $request = app()->bound('request') ? app(Request::class) : null;
        $user = $request?->user();

        $data = [
            'message' => mb_substr($e->getMessage() !== '' ? $e->getMessage() : get_class($e), 0, 2000),
            'exception_class' => get_class($e),
            'file' => $e->getFile() !== '' ? $e->getFile() : null,
            'line' => $e->getLine(),
            'http_status' => $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500,
            'url' => $request?->fullUrl(),
            'method' => $request?->getMethod(),
            'user_id' => $user?->id,
            'eventner_id' => $user?->eventner?->id,
            'user_agent' => $request?->userAgent(),
            'ip' => $request?->ip(),
            'trace' => mb_substr($e->getTraceAsString(), 0, 60000),
        ];

        try {
            // Sidik jari = class + file + line: error yang sama dari tempat
            // yang sama = satu baris, kode sama, occurrences bertambah.
            $fingerprint = $data['exception_class'] . '|' . ($data['file'] ?? '-') . '|' . ($data['line'] ?? 0);
            $code = ErrorCode::fromFingerprint($fingerprint);

            $log = ErrorLog::firstOrNew(['code' => $code]);

            if (! $log->exists) {
                $log->code = $code;
                $log->occurrences = 0;
            } elseif ($log->resolved_at !== null) {
                // Error yang dianggap selesai muncul lagi → aktif kembali.
                $log->resolved_at = null;
                $log->resolved_by = null;
            }

            $log->fill($data);
            $log->occurrences = ($log->occurrences ?? 0) + 1;
            $log->last_seen_at = now();
            $log->save();

            return $log;
        } catch (\Throwable $db) {
            // DB mati / migrasi belum jalan: jangan gagalkan pelaporan asli.
            error_log('[error_logs] gagal mencatat: ' . $db->getMessage());
            error_log('[error_logs] asli: ' . $e->getMessage());

            return null;
        }
    }

    private function shouldSkip(Throwable $e): bool
    {
        // Derau alur normal — bukan error server.
        return $e instanceof ValidationException
            || $e instanceof AuthenticationException
            || $e instanceof ModelNotFoundException
            || $e instanceof TokenMismatchException
            || $e instanceof HttpResponseException
            || $e instanceof NotFoundHttpException
            // HttpException di bawah 500 (403, 404 abort, dsb.) — alur normal.
            || ($e instanceof HttpExceptionInterface && $e->getStatusCode() < 500);
    }
}
