<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Eventner Feature Gates
    |--------------------------------------------------------------------------
    |
    | locked_free: true  → feature is gated for free-plan users (after trial)
    | locked_free: false → feature is available to all plans
    |
    | Features NOT listed here are always available (dashboard, profil, peserta,
    | juri, input nilai, rekap nilai, scoreboard).
    |
    */

    'tickets' => [
        'label' => 'Tiket Event',
        'locked_free' => true,
    ],
    'ticket_settings' => [
        'label' => 'Pengaturan Tiket',
        'locked_free' => true,
    ],
    'vote_settings' => [
        'label' => 'Pengaturan Vote',
        'locked_free' => true,
    ],
    'vote_booster' => [
        'label' => 'Vote Booster',
        'locked_free' => true,
    ],
    'vote_results' => [
        'label' => 'Hasil Voting',
        'locked_free' => true,
    ],
    'vote_transactions' => [
        'label' => 'Transaksi Voting',
        'locked_free' => true,
    ],
    'champion_categories' => [
        'label' => 'Kategori Juara',
        'locked_free' => true,
    ],
    'format_nilai' => [
        'label' => 'Format Penilaian',
        'locked_free' => true,
    ],
    'drawing' => [
        'label' => 'Drawing / Undian',
        'locked_free' => true,
    ],
    'rundown' => [
        'label' => 'Rundown Acara',
        'locked_free' => true,
    ],
    'livestream' => [
        'label' => 'Livestream Overlay',
        'locked_free' => true,
    ],
    'sponsors' => [
        'label' => 'Sponsor & Partner',
        'locked_free' => true,
    ],
    'tenants' => [
        'label' => 'Tenant / Stand',
        'locked_free' => true,
    ],
    'certificate' => [
        'label' => 'Sertifikat',
        'locked_free' => true,
    ],
];
