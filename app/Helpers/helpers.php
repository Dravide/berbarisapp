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
