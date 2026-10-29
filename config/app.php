<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Host Masuk Juri (tablet)
    |--------------------------------------------------------------------------
    |
    | Subdomain tetap milik platform untuk input nilai juri, mis.
    | "entry.berbaris.app". Semua juri dari event mana pun memakai host ini
    | (token di path yang menentukan juri + event). Karena subdomain eventner
    | unik per event, "entry" tidak boleh dipakai sebagai subdomain event.
    | Host lain yang menembak /juri/{token} di-redirect 301 ke sini.
    |
    */

    'entry_host' => env('ENTRY_HOST') ?: 'entry.' . parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST),

    /*
    |--------------------------------------------------------------------------
    | Host Event Organizer
    |--------------------------------------------------------------------------
    |
    | Subdomain tetap milik platform untuk halaman jasa penyelenggaraan lomba,
    | mis. "eo.berbaris.app". Isinya profil tim EO, lingkup kerja, alur, event,
    | tim, sponsor, dan formulir pengajuan — bukan satu event tertentu.
    |
    | Karena itu host ini TIDAK memakai middleware 'subdomain' (sama seperti
    | host entry): tidak ada Eventner yang perlu di-resolve, dan kalau ia ikut
    | grup {subdomain}.{root}, ResolveEventnerSubdomain akan mencari event
    | bernama "eo" lalu menjawab 404 untuk semua halaman di sini.
    |
    | Subdomain "eo" juga dicadangkan di Settings\Profile::reservedSubdomain()
    | supaya penyelenggara tidak bisa mengklaimnya sebagai subdomain event.
    |
    */

    'eo_host' => env('EO_HOST') ?: 'eo.' . parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST),

    /*
    |--------------------------------------------------------------------------
    | Host E-Sertifikat
    |--------------------------------------------------------------------------
    |
    | Subdomain tetap milik platform untuk mengunduh e-sertifikat lintas event,
    | mis. "e-sertifikat.berbaris.app". Peserta memasukkan NISN di sana dan
    | melihat semua sertifikatnya dari seluruh lomba yang pernah diikuti.
    |
    | Isinya bukan satu event tertentu, jadi — sama seperti host entry dan host
    | EO — host ini TIDAK memakai middleware 'subdomain': tidak ada Eventner
    | yang perlu di-resolve, dan kalau ia ikut grup {subdomain}.{root},
    | ResolveEventnerSubdomain akan mencari event bernama "e-sertifikat" lalu
    | menjawab 404 untuk semua halamannya.
    |
    | Label "e-sertifikat" tetap sah sebagai subdomain event (tanda hubung
    | diizinkan regex di Settings\Profile), jadi ia WAJIB ikut dicadangkan di
    | reservedSubdomain() — tanpa itu penyelenggara bisa mengklaimnya dan
    | beranda event mereka yang menang.
    |
    */

    'cert_host' => env('CERT_HOST') ?: 'e-sertifikat.' . parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'Asia/Jakarta',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'id'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'id'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
