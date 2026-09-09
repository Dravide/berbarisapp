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
