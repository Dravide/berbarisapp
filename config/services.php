<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'autogopay' => [
        'api_key' => env('AUTOGOPAY_API_KEY'),
        'base_url' => env('AUTOGOPAY_BASE_URL', 'https://v1-gateway.autogopay.site'),
    ],

    // Data wilayah BPS (provinsi/kabupaten/kecamatan) untuk field asal daerah
    // di form pendaftaran. Berkas JSON statis, tanpa API key sama sekali —
    // base_url disediakan supaya bisa dipindah ke cermin sendiri tanpa ubah kode.
    'datawilayah' => [
        'base_url' => env('DATAWILAYAH_BASE_URL', 'https://api.datawilayah.com'),
    ],

];
