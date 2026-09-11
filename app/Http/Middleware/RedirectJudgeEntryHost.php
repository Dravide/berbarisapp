<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menyentralisasi halaman input nilai juri ke host tetap platform
 * (config app.entry_host, mis. entry.berbaris.app).
 *
 * /juri/{token} dan /event/{slug}/juri/{token} tetap terdaftar di web.php
 * supaya QR/link lama yang sudah dicetak tidak mati; middleware ini yang
 * mengarahkannya 301 ke host entry. Path dinormalkan menjadi /juri/{token}
 * karena host entry hanya punya alamat itu (varian ber-slug akan 404 di sana).
 */
class RedirectJudgeEntryHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $entryHost = judge_entry_host();

        if (!$entryHost || strcasecmp($request->getHost(), $entryHost) === 0) {
            return $next($request);
        }

        $scheme = parse_url((string) config('app.entry_host'), PHP_URL_SCHEME)
            ?: (app()->environment('production') ? 'https' : $request->getScheme());

        $token = $request->route('token');

        $target = $token
            ? "{$scheme}://{$entryHost}/juri/" . rawurlencode($token)
            : "{$scheme}://{$entryHost}" . $request->getRequestUri();

        return redirect()->away($target, Response::HTTP_MOVED_PERMANENTLY);
    }
}
