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

        // 419/404 yang lolos filter di atas tetap tak perlu baris sendiri;
        // di sini hanya error benar-benar tak terduga.
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
            'code' => $this->uniqueCode(),
        ];

        try {
            return ErrorLog::create($data);
        } catch (\Throwable $db) {
            // DB mati / migrasi belum jalan: jangan gagalkan pelaporan asli.
            error_log('[error_logs] gagal mencatat ' . $data['code'] . ': ' . $db->getMessage());
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

    private function uniqueCode(): string
    {
        for ($i = 0; $i < 5; $i++) {
            $code = ErrorCode::generate();

            if (! ErrorLog::where('code', $code)->exists()) {
                return $code;
            }
        }

        return 'ER-' . time();
    }
}
