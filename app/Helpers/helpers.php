<?php

use App\Models\Setting;

if (!function_exists('get_setting')) {
    /**
     * Helper to get setting value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function get_setting($key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('app_name')) {
    /**
     * Nama aplikasi dari Pengaturan Situs (site_title).
     */
    function app_name($default = 'Berbaris App')
    {
        return Setting::get('site_title', $default);
    }
}

if (!function_exists('judge_entry_host')) {
    /**
     * Host (tanpa skema) halaman input nilai juri, mis. "entry.berbaris.app".
     */
    function judge_entry_host(): ?string
    {
        $value = config('app.entry_host');

        if (!$value) {
            return null;
        }

        // config boleh ditulis dengan atau tanpa skema ("entry.berbaris.app").
        return parse_url(str_contains($value, '://') ? $value : 'http://' . $value, PHP_URL_HOST);
    }
}

if (!function_exists('judge_entry_url')) {
    /**
     * URL absolut halaman input nilai juri di host entry milik platform.
     *
     * Selalu dibangun dari config — bukan dari host request — supaya QR/link
     * yang dibuat panitia dari dashboard selalu menunjuk ke satu domain tetap,
     * walau dibuat dari subdomain event atau dari localhost.
     */
    function judge_entry_url(string $token): string
    {
        $scheme = parse_url((string) config('app.entry_host'), PHP_URL_SCHEME)
            ?: parse_url((string) config('app.url'), PHP_URL_SCHEME)
            ?: 'http';

        return $scheme . '://' . judge_entry_host() . '/juri/' . rawurlencode($token);
    }
}
