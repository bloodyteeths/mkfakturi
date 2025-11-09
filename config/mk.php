<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Macedonian XML Signing Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for digital signing of UBL XML documents for
    | Macedonian tax authority compliance
    |
    */

    'xml_signing' => [
        
        /*
        |--------------------------------------------------------------------------
        | Private Key Path
        |--------------------------------------------------------------------------
        |
        | Path to the private key file used for XML digital signatures.
        | This should be a RSA private key in PEM format.
        | Keep this file secure and never commit to version control.
        |
        */
        'private_key_path' => env('MK_XML_PRIVATE_KEY_PATH', storage_path('certificates/private.key')),

        /*
        |--------------------------------------------------------------------------
        | Certificate Path
        |--------------------------------------------------------------------------
        |
        | Path to the X.509 certificate file in PEM format.
        | This certificate will be embedded in the XML signature.
        |
        */
        'certificate_path' => env('MK_XML_CERTIFICATE_PATH', storage_path('certificates/certificate.pem')),

        /*
        |--------------------------------------------------------------------------
        | Private Key Passphrase
        |--------------------------------------------------------------------------
        |
        | Passphrase for the private key if it's password protected.
        | Leave empty if the private key has no passphrase.
        |
        */
        'passphrase' => env('MK_XML_PASSPHRASE', ''),

        /*
        |--------------------------------------------------------------------------
        | Signature Algorithm
        |--------------------------------------------------------------------------
        |
        | The signature algorithm to use for signing XML documents.
        | Supported: 'RSA_SHA256', 'RSA_SHA1'
        | SHA256 is recommended for better security.
        |
        */
        'algorithm' => env('MK_XML_SIGNATURE_ALGORITHM', 'RSA_SHA256'),

        /*
        |--------------------------------------------------------------------------
        | Canonicalization Method
        |--------------------------------------------------------------------------
        |
        | The canonicalization method for XML signatures.
        | This is usually EXC_C14N for most standards.
        |
        */
        'canonicalization_method' => 'EXC_C14N',

    ],

    /*
    |--------------------------------------------------------------------------
    | Macedonian Tax Authority Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to Macedonian tax authority requirements
    |
    */

    'tax_authority' => [
        
        /*
        |--------------------------------------------------------------------------
        | Standard VAT Rate
        |--------------------------------------------------------------------------
        |
        | The standard VAT rate in Macedonia (18%)
        |
        */
        'standard_vat_rate' => 18,

        /*
        |--------------------------------------------------------------------------
        | Reduced VAT Rate
        |--------------------------------------------------------------------------
        |
        | The reduced VAT rate in Macedonia (5%)
        |
        */
        'reduced_vat_rate' => 5,

        /*
        |--------------------------------------------------------------------------
        | Tax Scheme
        |--------------------------------------------------------------------------
        |
        | The tax scheme identifier for Macedonia
        |
        */
        'tax_scheme' => 'VAT',

        /*
        |--------------------------------------------------------------------------
        | Tax Scheme Name (Macedonian)
        |--------------------------------------------------------------------------
        |
        | The tax scheme name in Macedonian language
        |
        */
        'tax_scheme_name' => 'ДДВ',

        /*
        |--------------------------------------------------------------------------
        | Country Code
        |--------------------------------------------------------------------------
        |
        | ISO 3166-1 alpha-2 country code for Macedonia
        |
        */
        'country_code' => 'MK',

        /*
        |--------------------------------------------------------------------------
        | Currency Code
        |--------------------------------------------------------------------------
        |
        | ISO 4217 currency code for Macedonian Denar
        |
        */
        'currency_code' => 'MKD',

    ],

    /*
    |--------------------------------------------------------------------------
    | UBL Document Settings
    |--------------------------------------------------------------------------
    |
    | Settings for UBL document generation
    |
    */

    'ubl' => [
        
        /*
        |--------------------------------------------------------------------------
        | UBL Version
        |--------------------------------------------------------------------------
        |
        | The UBL version to use for document generation
        |
        */
        'version' => '2.1',

        /*
        |--------------------------------------------------------------------------
        | Customization ID
        |--------------------------------------------------------------------------
        |
        | The customization identifier for Macedonian UBL documents
        |
        */
        'customization_id' => 'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',

        /*
        |--------------------------------------------------------------------------
        | Profile ID
        |--------------------------------------------------------------------------
        |
        | The profile identifier for Macedonian business processes
        |
        */
        'profile_id' => 'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',

        /*
        |--------------------------------------------------------------------------
        | XSD Schema Path
        |--------------------------------------------------------------------------
        |
        | Path to UBL XSD schema files for validation
        |
        */
        'schema_path' => storage_path('schemas'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Bank Integration Settings
    |--------------------------------------------------------------------------
    |
    | Settings for bank PSD2 integrations
    |
    */

    'banks' => [

        /*
        |--------------------------------------------------------------------------
        | Supported Banks
        |--------------------------------------------------------------------------
        |
        | List of supported banks for PSD2 integration
        |
        */
        'supported' => ['stopanska', 'nlb'],

        /*
        |--------------------------------------------------------------------------
        | Rate Limits
        |--------------------------------------------------------------------------
        |
        | API rate limits for different banks (requests per minute)
        |
        */
        'rate_limits' => [
            'stopanska' => 15, // 15 requests per minute
            'nlb' => 15,       // 15 requests per minute
        ],

        /*
        |--------------------------------------------------------------------------
        | Default Sync Settings
        |--------------------------------------------------------------------------
        |
        | Default settings for bank transaction synchronization
        |
        */
        'sync_defaults' => [
            'days_back' => 30,      // Days to look back for transactions
            'max_transactions' => 100, // Maximum transactions per sync
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Stopanska Banka PSD2 Settings
    |--------------------------------------------------------------------------
    |
    | OAuth2 configuration for Stopanska Banka PSD2 API
    |
    */
    'stopanska' => [
        'client_id' => env('STOPANSKA_CLIENT_ID', ''),
        'client_secret' => env('STOPANSKA_CLIENT_SECRET', ''),
        'environment' => env('STOPANSKA_ENVIRONMENT', 'sandbox'),
        'sandbox_base_url' => env('STOPANSKA_SANDBOX_BASE_URL', 'https://sandbox-api.ob.stb.kibs.mk/xs2a/v1'),
        'production_base_url' => env('STOPANSKA_PRODUCTION_BASE_URL', 'https://api.ob.stb.kibs.mk/xs2a/v1'),
        'rate_limit_enabled' => env('STOPANSKA_RATE_LIMIT_ENABLED', true),
        'max_transactions_per_request' => env('STOPANSKA_MAX_TRANSACTIONS_PER_REQUEST', 200),
        'redirect_uri' => env('STOPANSKA_REDIRECT_URI', null), // Must be registered in Stopanska developer portal
    ],

    /*
    |--------------------------------------------------------------------------
    | NLB Banka PSD2 Settings
    |--------------------------------------------------------------------------
    |
    | OAuth2 configuration for NLB Banka PSD2 API
    |
    */
    'nlb' => [
        'client_id' => env('NLB_CLIENT_ID', ''),
        'client_secret' => env('NLB_CLIENT_SECRET', ''),
        'environment' => env('NLB_ENVIRONMENT', 'sandbox'),
        'sandbox_base_url' => env('NLB_SANDBOX_BASE_URL', 'https://developer-ob.nlb.mk/apis/xs2a/v1'),
        'production_base_url' => env('NLB_PRODUCTION_BASE_URL', 'https://developer-ob.nlb.mk/apis/xs2a/v1'),
        'auth_sandbox_base_url' => env('NLB_AUTH_SANDBOX_BASE_URL', 'https://auth.sandbox.mk.open-bank.io/v1/authentication/tenants/nlb'),
        'auth_production_base_url' => env('NLB_AUTH_PRODUCTION_BASE_URL', 'https://auth.mk.open-bank.io/v1/authentication/tenants/nlb'),
        'redirect_uri' => env('NLB_REDIRECT_URI', null), // Must be registered in NLB developer portal
        'scopes' => env('NLB_SCOPES', 'openid'), // OAuth scopes - NLB auto-grants PSD2 scopes
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Settings
    |--------------------------------------------------------------------------
    |
    | Settings for Macedonian payment gateways
    |
    */

    'payment_gateways' => [

        /*
        |--------------------------------------------------------------------------
        | CPay Settings
        |--------------------------------------------------------------------------
        |
        | Settings for CPay payment gateway (CASYS)
        |
        */
        'cpay' => [
            'merchant_id' => env('CPAY_MERCHANT_ID', ''),
            'secret_key' => env('CPAY_SECRET_KEY', ''),
            'payment_url' => env('CPAY_PAYMENT_URL', 'https://cpay.com.mk/payment'),
            'success_url' => env('CPAY_SUCCESS_URL', ''),
            'error_url' => env('CPAY_ERROR_URL', ''),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    |
    | Feature flags for Macedonian-specific features
    |
    */

    'features' => [
        'advanced_payments' => env('FEATURE_ADVANCED_PAYMENTS', false),
        'psd2_banking' => env('FEATURE_PSD2_BANKING', false),
    ],

];
// CLAUDE-CHECKPOINT
