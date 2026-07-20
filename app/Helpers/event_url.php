<?php

if (!function_exists('event_url')) {
    /**
     * Generate URL for event public page.
     * Uses subdomain if set, fallback to /event/{slug} route.
     *
     * @param  \App\Models\Eventner  $eventner
     * @param  string  $route  Route name without prefix (e.g. 'detail', 'vote', 'register')
     * @param  array  $params  Extra route parameters (e.g. confirmOrder for ticket)
     * @return string
     */
    function event_url($eventner, string $route = 'detail', array $params = []): string
    {
        if (method_exists($eventner, 'publicUrl')) {
            return $eventner->publicUrl($route, $params);
        }
        // Fallback: try slug property + default route
        $slug = is_object($eventner) ? ($eventner->slug ?? '') : $eventner;
        return route("event.{$route}", array_merge([$slug], $params));
    }
}

if (!function_exists('event_link')) {
    /**
     * Generate <a> tag with event URL.
     */
    function event_link($eventner, string $route, string $label, array $params = [], array $attrs = []): string
    {
        $url = event_url($eventner, $route, $params);
        $attrStr = '';
        foreach ($attrs as $key => $val) {
            $attrStr .= ' ' . $key . '="' . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . '"';
        }
        return '<a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"' . $attrStr . '>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</a>';
    }
}
