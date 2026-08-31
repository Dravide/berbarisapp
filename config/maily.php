<?php

return [
    // Guard pengiriman. phpunit.xml set MAILY_ENABLED=false agar test
    // tidak mengirim email sungguhan ke alamat factory (@example.*).
    'enabled' => env('MAILY_ENABLED', true),

    'api_key' => env('MAILY_API_KEY'),

    'from' => env('MAILY_FROM', 'noreply@berbaris.app'),

    'endpoint' => env('MAILY_ENDPOINT', 'https://maily.id/api/v1/emails/send'),
];
