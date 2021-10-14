<?php

return [
  'rajaongkir' => [
    'endpoint'  => env('RAJAONGKIR_CREDENTIAL_ENDPOINT'),
    'app_key'   => env('RAJAONGKIR_CREDENTIAL_APPKEY')
  ],
  'iconpay' => [
      'endpoint' => env('ICONPAY_CREDENTIAL_ENDPOINT'),
      'client_id' => env('ICONPAY_CREDENTIAL_CLIENT_ID'),
      'app_key' => env('ICONPAY_CREDENTIAL_APPKEY'),
      'product_id' => env('ICONPAY_CREDENTIAL_PRODUCT_ID'),
      'app_source' => env('ICONPAY_CREDENTIAL_APP_SOURCE')
  ]
];
