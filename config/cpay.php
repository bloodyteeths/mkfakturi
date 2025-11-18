<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CPay Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for CPay (CaSys) payment integration in Macedonia
    |
    */

    'merchant_id' => env('CPAY_MERCHANT_ID', ''),
    'secret_key' => env('CPAY_SECRET_KEY', ''),
    'payment_url' => env('CPAY_PAYMENT_URL', 'https://cpay.com.mk/payment'),

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'default_currency' => 'MKD',
    'default_language' => 'mk',

    /*
    |--------------------------------------------------------------------------
    | Return URLs
    |--------------------------------------------------------------------------
    */

    'success_url' => env('CPAY_SUCCESS_URL', '/payment/success'),
    'cancel_url' => env('CPAY_CANCEL_URL', '/payment/cancel'),
    'callback_url' => env('CPAY_CALLBACK_URL', '/payment/callback'),
];
