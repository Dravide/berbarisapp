<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Eventner;
use Illuminate\Support\Facades\Cache;

/**
 * Sitemap.xml dinamis — satu file untuk seluruh platform.
 *
 * Isinya hanya halaman yang layak diindeks: root platform (landing, harga,
 * daftar penyelenggara, legal) dan halaman detail tiap event approved.
 * Halaman ber-token (scan, juri, magic link), halaman turunan event
 * (pendaftaran, tiket, hasil per registrasi), dan PDF sengaja dikecualikan —
 * semuanya bukan konten indeksable.
 *
 * URL event dibangun lewat publicUrl() (subdomain, absolut dari config
 * app.url) atau platform_url() (jalur /event/{slug}) — keduanya host-
 * independent, supaya sitemap tidak ikut host yang sedang mengakses.
 */
class SitemapController extends Controller
{
    public function index()
    {
        // Array polos, bukan Eloquent Collection: cache database store ini
        // memblokir unserialisasi kelas (config cache.serializable_classes =
        // false) — Collection yang di-cache kembali sebagai string mentah.
        $eventners = Cache::remember('sitemap.eventners', 3600, function () {
            return Eventner::approved()
                ->orderByDesc('tanggal')
                ->get(['slug', 'subdomain', 'updated_at', 'tanggal', 'diselenggarakan_oleh'])
                ->map(fn ($e) => [
                    'slug' => $e->slug,
                    'subdomain' => $e->subdomain,
                    'updated_at' => $e->updated_at?->toAtomString(),
                    'diselenggarakan_oleh' => $e->diselenggarakan_oleh,
                ])
                ->all();
        });

        $urls = [
            ['loc' => platform_url('/')],
            ['loc' => platform_url('pricing')],
            ['loc' => platform_url('fitur')],
            ['loc' => platform_url('eventner')],
            ['loc' => platform_url('privacy')],
            ['loc' => platform_url('terms')],
            ['loc' => platform_url('help')],
        ];

        foreach ($eventners as $eventner) {
            if ($eventner['subdomain']) {
                // Sama dengan Eventner::publicUrl('detail') tapi dari array
                // cache, bukan model — subdomain.{root}/ dari config app.url.
                $root = parse_url(config('app.url'), PHP_URL_HOST);
                $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'http';
                $loc = "{$scheme}://{$eventner['subdomain']}.{$root}/";
            } else {
                $loc = platform_url('event/' . $eventner['slug']);
            }

            $urls[] = [
                'loc' => $loc,
                'lastmod' => $eventner['updated_at'],
            ];
        }

        // Hall of Fame per penyelenggara — slug-nya nama penyelenggara.
        $penyelenggara = collect($eventners)
            ->map(fn ($e) => trim((string) $e['diselenggarakan_oleh']))
            ->filter()
            ->unique(fn ($nama) => mb_strtolower($nama));
        foreach ($penyelenggara as $nama) {
            $urls[] = [
                'loc' => platform_url('hall-of-fame/' . rawurlencode($nama)),
            ];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $url) {
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . e($url['loc']) . "</loc>\n";
            if (!empty($url['lastmod'])) {
                $xml .= "\t\t<lastmod>" . $url['lastmod'] . "</lastmod>\n";
            }
            $xml .= "\t</url>\n";
        }
        $xml .= '</urlset>' . "\n";

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
