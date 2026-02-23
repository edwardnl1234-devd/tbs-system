<?php

return [
    /*
    |--------------------------------------------------------------------------
    | TBS Price Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk pengambilan harga TBS secara online
    |
    */

    'price_source' => [
        /*
        |--------------------------------------------------------------------------
        | Default Price Source
        |--------------------------------------------------------------------------
        |
        | Sumber harga default yang digunakan:
        | - manual: Input manual (default)
        | - disbun: Dinas Perkebunan Provinsi
        | - ptpn: PTPN
        | - gapki: GAPKI
        | - custom: Custom API
        |
        */
        'default' => env('TBS_PRICE_SOURCE', 'manual'),

        /*
        |--------------------------------------------------------------------------
        | Province Code
        |--------------------------------------------------------------------------
        |
        | Kode provinsi untuk sumber Dinas Perkebunan:
        | riau, sumut, kaltim, kalbar, jambi, dll
        |
        */
        'province' => env('TBS_PRICE_PROVINCE', 'riau'),

        /*
        |--------------------------------------------------------------------------
        | Dinas Perkebunan URL
        |--------------------------------------------------------------------------
        |
        | URL API Dinas Perkebunan (jika tidak menggunakan default)
        |
        */
        'disbun_url' => env('TBS_DISBUN_URL'),

        /*
        |--------------------------------------------------------------------------
        | PTPN URL
        |--------------------------------------------------------------------------
        |
        | URL API PTPN
        |
        */
        'ptpn_url' => env('TBS_PTPN_URL'),

        /*
        |--------------------------------------------------------------------------
        | GAPKI URL
        |--------------------------------------------------------------------------
        |
        | URL API GAPKI
        |
        */
        'gapki_url' => env('TBS_GAPKI_URL'),

        /*
        |--------------------------------------------------------------------------
        | Custom API Configuration
        |--------------------------------------------------------------------------
        |
        | Konfigurasi untuk Custom API (sistem internal perusahaan)
        |
        */
        'custom_api_url' => env('TBS_CUSTOM_API_URL'),
        'custom_api_key' => env('TBS_CUSTOM_API_KEY'),

        /*
        |--------------------------------------------------------------------------
        | Custom Field Mapping
        |--------------------------------------------------------------------------
        |
        | Mapping field dari Custom API ke field sistem
        |
        */
        'custom_field_map' => [
            'date' => env('TBS_FIELD_DATE', 'effective_date'),
            'inti' => env('TBS_FIELD_INTI', 'price_inti'),
            'plasma' => env('TBS_FIELD_PLASMA', 'price_plasma'),
            'umum' => env('TBS_FIELD_UMUM', 'price_umum'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Update Schedule
    |--------------------------------------------------------------------------
    |
    | Jadwal update harga otomatis (dalam format cron)
    | Default: Setiap hari jam 06:00
    |
    */
    'auto_update' => [
        'enabled' => env('TBS_AUTO_UPDATE', false),
        'schedule' => env('TBS_UPDATE_SCHEDULE', '0 6 * * *'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Price Calculation
    |--------------------------------------------------------------------------
    |
    | Parameter untuk kalkulasi harga simulasi
    |
    */
    'calculation' => [
        'rendemen' => env('TBS_RENDEMEN', 0.22),        // OER default 22%
        'koefisien' => env('TBS_KOEFISIEN', 0.87),      // Koefisien K
        'biaya_proses' => env('TBS_BIAYA_PROSES', 200), // Biaya proses per kg
    ],
];
