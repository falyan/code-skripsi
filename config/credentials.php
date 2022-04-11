<?php

return [
    'rajaongkir' => [
        'endpoint'  => env('RAJAONGKIR_CREDENTIAL_ENDPOINT'),
        'app_key1'   => env('RAJAONGKIR_CREDENTIAL_APPKEY1'),
        'app_key2'   => env('RAJAONGKIR_CREDENTIAL_APPKEY2')
    ],
    'iconpay' => [
        'endpoint'    => env('ICONPAY_CREDENTIAL_ENDPOINT'),
        'client_id'   => env('ICONPAY_CREDENTIAL_CLIENT_ID'),
        'app_key'     => env('ICONPAY_CREDENTIAL_APPKEY'),
        'product_id'  => env('ICONPAY_CREDENTIAL_PRODUCT_ID'),
        'app_source'  => env('ICONPAY_CREDENTIAL_APP_SOURCE')
    ],
    'iconcash' => [
        'endpoint'  => env('ICONCASH_CREDENTIAL_ENDPOINT'),
        'api_key'   => env('ICONCASH_CREDENTIAL_APIKEY'),
    ],
    'iconcash_topup' => [
        'endpoint'  => env('ICONCASH_TOPUP_CREDENTIAL_ENDPOINT'),
        'client_id' => env('ICONCASH_TOPUP_CREDENTIAL_CLIENT_ID'),
        'secret_key' => env('ICONCASH_TOPUP_CREDENTIAL_SECRET_KEY')
    ],
    'radagast' => [
        'endpoint' => env('RADAGAST_NOTIFICATION_ENDPOINT')
    ],
    'banner' => [
        'url_flash_popup' => env('BANNER_FLASH_POPUP_URL')
    ],
    'gamification' => [
        'endpoint' => env('GAMIFICATION_ENDPOINT'),
        'key_name' => 'pln_ubah_daya'
    ],
    'plnmobile' => [
        'endpoint' => env('PLNMOBILE_ENDPOINT')
    ]
];
