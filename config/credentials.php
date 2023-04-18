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
        'key_id' => env('GAMIFICATION_KEY_ID'),
        'secret_key' => env('GAMIFICATION_SECRET_KEY'),
    ],
    'plnmobile' => [
        'endpoint' => env('PLNMOBILE_ENDPOINT'),
    ],
    'digital-product' => [
        'endpoint' => env('DIGITAL_PRODUCT_CREDENTIAL_ENDPOINT'),
        'api_key' => env('DIGITAL_PRODUCT_CREDENTIAL_APIKEY'),
        'secret_key' => env('DIGITAL_PRODUCT_CREDENTIAL_SECRETKEY'),
    ],
    'hedwig' => [
        'endpoint' => env('HEDWIG_LOGISTIC_ENDPOINT'),
        'key' => env('HEDWIG_LOGISTIC_KEY'),
    ],
    'agent' => [
        'v2' => [
            'endpoint' => env('ICONPAY_AGENT_CREDENTIAL_ENDPOINT'),
            'username' => env('ICONPAY_AGENT_USERNAME'),
            'password' => env('ICONPAY_AGENT_PASSWORD'),
            'api_key' => env('ICONPAY_AGENT_APIKEY'),
        ],
        'v3' => [
            'endpoint' => env('ICONPAY_V3_AGENT_CREDENTIAL_ENDOPOINT'),
            'partner_id' => env('ICONPAY_V3_AGENT_PARTNER_ID'),
            'client_id' => env('ICONPAY_V3_AGENT_CLIENT_ID'),
            'client_secret' => env('ICONPAY_V3_AGENT_CLIENT_SECRET'),
            'channel_id' => env('ICONPAY_V3_AGENT_CHANNEL_ID'),
        ]
    ],
    'tiket' => [
        'api_hash' => env('TIKET_EVENT_HASH'),
    ],
    'evsubsidy' => [
        'endpoint_nik' => env('EV_SUBSIDY_CHECK_NIK_CREDENTIAL_ENDPOINT'),
        'username' => env('EV_SUBSIDY_CHECK_NIK_CREDENTIAL_USERNAME'),
        'password' => env('EV_SUBSIDY_CHECK_NIK_CREDENTIAL_PASSWORD'),
    ],
];
